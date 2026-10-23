<?php

use App\Enums\ModerationStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create();
    $this->guest = Guest::factory()->for($this->event)->create();
});

test('un invité signe le livre d\'or : le message est en attente de modération', function () {
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Félicitations !'])
        ->assertRedirect()->assertSessionHas('contribution_saved', 'entry');

    $entry = GuestbookEntry::first();
    expect($entry->status)->toBe(ModerationStatus::Pending)
        ->and($entry->event_id)->toBe($this->event->id)
        ->and($entry->guest_id)->toBe($this->guest->id);
});

test('le message est validé', function () {
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => ''])->assertSessionHasErrors('message');
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => str_repeat('a', 1001)])->assertSessionHasErrors('message');
});

test('le nombre de messages par invité est limité', function () {
    GuestbookEntry::factory()->for($this->guest)->count(5)->create();

    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Un de trop'])->assertSessionHasErrors('message');

    expect($this->guest->guestbookEntries()->count())->toBe(5);
});

test('un jeton inconnu ne peut pas écrire', function () {
    $this->post('/i/'.str_repeat('q', 40).'/guestbook', ['message' => 'Salut'])->assertNotFound();
});

test('l\'invitation n\'expose que les contributions approuvées', function () {
    GuestbookEntry::factory()->for($this->guest)->create(['message' => 'Visible', 'status' => 'approved']);
    GuestbookEntry::factory()->for($this->guest)->create(['message' => 'En attente', 'status' => 'pending']);
    GuestbookEntry::factory()->for($this->guest)->create(['message' => 'Refusé', 'status' => 'rejected']);

    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page
        ->has('guestbook', 1)->where('guestbook.0.message', 'Visible')
        ->missing('photos')
        ->where('mine.pending_entries', 1)
        ->missing('mine.pending_photos'));
});

test('l\'hôte modère : publier, refuser, supprimer un message', function () {
    $entry = GuestbookEntry::factory()->for($this->guest)->create();
    $url = "/events/{$this->event->slug}/guestbook/{$entry->id}";

    $this->actingAs($this->host)->patch($url, ['status' => 'approved'])->assertRedirect();
    expect($entry->fresh()->status)->toBe(ModerationStatus::Approved);

    $this->actingAs($this->host)->patch($url, ['status' => 'rejected']);
    expect($entry->fresh()->status)->toBe(ModerationStatus::Rejected);

    $this->actingAs($this->host)->patch($url, ['status' => 'nimporte-quoi'])->assertSessionHasErrors('status');

    $this->actingAs($this->host)->delete($url)->assertRedirect();
    expect(GuestbookEntry::count())->toBe(0);
});

test('un autre utilisateur ne peut pas modérer', function () {
    $entry = GuestbookEntry::factory()->for($this->guest)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get("/events/{$this->event->slug}/moderation")->assertForbidden();
    $this->actingAs($intruder)->patch("/events/{$this->event->slug}/guestbook/{$entry->id}", ['status' => 'approved'])->assertForbidden();

    expect($entry->fresh()->status)->toBe(ModerationStatus::Pending);
});

test('on ne peut pas modérer le contenu d\'un autre événement', function () {
    $foreignEntry = GuestbookEntry::factory()->create();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/guestbook/{$foreignEntry->id}", ['status' => 'approved'])->assertNotFound();
});

test('la page de modération liste les messages', function () {
    GuestbookEntry::factory()->for($this->guest)->count(2)->create();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/moderation")->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Moderation')->has('entries', 2)->missing('photos'));
});

test('supprimer un événement supprime ses contributions', function () {
    GuestbookEntry::factory()->for($this->guest)->create();

    $this->event->delete();

    expect(GuestbookEntry::count())->toBe(0);
});

// --- Anti-spam ------------------------------------------------------------------------------------------------

test('les liens sont refusés dans le livre d\'or et les messages de don', function (string $text) {
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => $text])->assertSessionHasErrors('message');
    $this->post("/i/{$this->guest->token}/donations", ['amount' => 5000, 'message' => $text])->assertSessionHasErrors('message');

    expect(GuestbookEntry::count())->toBe(0);
})->with([
    'https://spam.example/promo',
    'Visitez www.gains-faciles.com maintenant',
    'gagnez de l\'argent sur casino-gratuit.xyz',
    'HTTP://MAJUSCULES.TEST',
]);

test('un message normal, même avec de la ponctuation et des points, reste accepté', function () {
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Bravo ! Tous nos voeux... À très vite, Awa et Koffi.'])
        ->assertSessionHasNoErrors();

    expect(GuestbookEntry::count())->toBe(1);
});

test('un même message envoyé deux fois par le même invité est ignoré', function () {
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Félicitations !'])->assertSessionHasNoErrors();
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => '  félicitations !  '])->assertSessionHasErrors('message');

    expect(GuestbookEntry::count())->toBe(1);
});

test('un autre invité peut envoyer le même message', function () {
    $other = Guest::factory()->for($this->event)->create();

    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Félicitations !'])->assertSessionHasNoErrors();
    $this->post("/i/{$other->token}/guestbook", ['message' => 'Félicitations !'])->assertSessionHasNoErrors();

    expect(GuestbookEntry::count())->toBe(2);
});

test('la file de modération d\'un événement est plafonnée', function () {
    GuestbookEntry::factory()->count(200)->for(Guest::factory()->for($this->event))->create(['status' => 'pending']);

    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Un de trop'])->assertSessionHasErrors('message');

    expect($this->guest->guestbookEntries()->count())->toBe(0);
});

test('les messages déjà publiés ne comptent pas dans le plafond de la file', function () {
    GuestbookEntry::factory()->count(200)->for(Guest::factory()->for($this->event))->create(['status' => 'approved']);

    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Bienvenue'])->assertSessionHasNoErrors();
});

test('le nombre d\'envois par minute est limité par invité, pas pour toute la salle', function () {
    $other = Guest::factory()->for($this->event)->create();

    foreach (range(1, 5) as $i) {
        $this->post("/i/{$this->guest->token}/guestbook", ['message' => "Message numéro {$i}"]);
    }
    $this->post("/i/{$this->guest->token}/guestbook", ['message' => 'Message de trop'])->assertStatus(429);

    // Même adresse IP, autre invité : il n'est pas bloqué.
    $this->post("/i/{$other->token}/guestbook", ['message' => 'Mon tour'])->assertSessionHasNoErrors();
});
