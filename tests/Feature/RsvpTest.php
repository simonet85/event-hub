<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->guest = Guest::factory()->for($this->event)->create(['max_companions' => 2]);
});

test('un invité peut accepter avec accompagnants, régime, chanson et message', function () {
    $this->post("/i/{$this->guest->token}/rsvp", [
        'rsvp_status' => 'accepted',
        'companions_count' => 2,
        'dietary_restrictions' => 'Végétarien',
        'favorite_song' => 'September',
        'rsvp_message' => 'Hâte d\'y être !',
    ])->assertRedirect()->assertSessionHas('rsvp_saved', true);

    $guest = $this->guest->fresh();
    expect($guest->rsvp_status)->toBe(RsvpStatus::Accepted)
        ->and($guest->companions_count)->toBe(2)
        ->and($guest->dietary_restrictions)->toBe('Végétarien')
        ->and($guest->favorite_song)->toBe('September')
        ->and($guest->responded_at)->not->toBeNull();
});

test('un refus remet les accompagnants, régime et chanson à zéro mais garde le message', function () {
    $this->guest->forceFill(['rsvp_status' => 'accepted', 'companions_count' => 2, 'dietary_restrictions' => 'Sans gluten', 'favorite_song' => 'X'])->save();

    $this->post("/i/{$this->guest->token}/rsvp", [
        'rsvp_status' => 'declined',
        'companions_count' => 2,
        'dietary_restrictions' => 'Sans gluten',
        'rsvp_message' => 'Désolé',
    ])->assertSessionHasNoErrors();

    $guest = $this->guest->fresh();
    expect($guest->rsvp_status)->toBe(RsvpStatus::Declined)
        ->and($guest->companions_count)->toBe(0)
        ->and($guest->dietary_restrictions)->toBeNull()
        ->and($guest->favorite_song)->toBeNull()
        ->and($guest->rsvp_message)->toBe('Désolé');
});

test('la réponse peut être modifiée plus tard', function () {
    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'declined']);
    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'accepted', 'companions_count' => 1]);

    expect($this->guest->fresh())->rsvp_status->toBe(RsvpStatus::Accepted)->companions_count->toBe(1);
});

test('le nombre d\'accompagnants ne peut pas dépasser le maximum autorisé', function () {
    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'accepted', 'companions_count' => 3])
        ->assertSessionHasErrors('companions_count');

    expect($this->guest->fresh()->rsvp_status)->toBe(RsvpStatus::Pending);
});

test('le statut "pending" ne peut pas être soumis et un statut invalide est refusé', function () {
    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'pending'])->assertSessionHasErrors('rsvp_status');
    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'peut-etre'])->assertSessionHasErrors('rsvp_status');
    $this->post("/i/{$this->guest->token}/rsvp", [])->assertSessionHasErrors('rsvp_status');
});

test('un jeton inconnu renvoie 404 sur le RSVP', function () {
    $this->post('/i/'.str_repeat('z', 40).'/rsvp', ['rsvp_status' => 'accepted'])->assertNotFound();
});

test('un invité ne peut répondre que pour lui-même', function () {
    $other = Guest::factory()->for($this->event)->create();

    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'accepted']);

    expect($other->fresh()->rsvp_status)->toBe(RsvpStatus::Pending);
});

test('la page d\'invitation expose la réponse existante', function () {
    $this->guest->forceFill(['rsvp_status' => 'accepted', 'companions_count' => 1])->save();

    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page
        ->where('token', $this->guest->token)
        ->where('guest.rsvp_status', 'accepted')
        ->where('guest.companions_count', 1));
});

test('le résumé de l\'hôte compte présents, absents, en attente et personnes attendues', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['rsvp_status' => 'accepted', 'companions_count' => 2]);
    Guest::factory()->for($event)->create(['rsvp_status' => 'accepted', 'companions_count' => 0]);
    Guest::factory()->for($event)->create(['rsvp_status' => 'declined']);
    Guest::factory()->for($event)->create();

    $this->actingAs($host)->get("/events/{$event->slug}/guests")->assertInertia(fn ($page) => $page
        ->where('summary.total', 4)
        ->where('summary.accepted', 2)
        ->where('summary.declined', 1)
        ->where('summary.pending', 1)
        ->where('summary.headcount', 4));
});

test('le RSVP est limité en fréquence', function () {
    foreach (range(1, 20) as $i) {
        $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'accepted']);
    }

    $this->post("/i/{$this->guest->token}/rsvp", ['rsvp_status' => 'accepted'])->assertStatus(429);
});
