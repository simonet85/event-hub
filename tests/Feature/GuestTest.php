<?php

use App\Mail\InvitationMail;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create();
});

test('un jeton unique de 40 caractères est généré à la création', function () {
    $a = Guest::factory()->for($this->event)->create();
    $b = Guest::factory()->for($this->event)->create();

    expect($a->token)->toHaveLength(40)->and($a->token)->not->toBe($b->token);
});

test('l\'hôte peut ajouter un invité', function () {
    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/guests", ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => '+33 6 12 34 56 78'])
        ->assertRedirect();

    $guest = $this->event->guests()->first();
    expect($guest->name)->toBe('Alice')->and($guest->max_companions)->toBe(0);
});

test('l\'email d\'un invité est unique par événement', function () {
    Guest::factory()->for($this->event)->create(['email' => 'alice@example.com']);

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/guests", ['name' => 'Alice 2', 'email' => 'alice@example.com'])
        ->assertSessionHasErrors('email');
});

test('un autre utilisateur ne peut pas gérer les invités', function () {
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get("/events/{$this->event->slug}/guests")->assertForbidden();
    $this->actingAs($intruder)
        ->post("/events/{$this->event->slug}/guests", ['name' => 'X'])
        ->assertForbidden();
    expect($this->event->guests()->count())->toBe(0);
});

test('la liste des invités est visible par l\'hôte', function () {
    Guest::factory()->for($this->event)->count(2)->create();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/guests")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Guests')->has('guests', 2));
});

test('l\'import CSV crée les invités valides et ignore les doublons et lignes invalides', function () {
    Guest::factory()->for($this->event)->create(['email' => 'deja@example.com']);

    $csv = "name,email,phone,max_companions\nAlice,alice@example.com,+33612345678,2\nDoublon,deja@example.com,,0\n,sans-nom@example.com,,0\nBob,pas-un-email,,0\nCarl,,,1\n";
    $file = UploadedFile::fake()->createWithContent('invites.csv', $csv);

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/guests/import", ['file' => $file])
        ->assertSessionHas('imported', 2);

    expect($this->event->guests()->pluck('name')->all())->toContain('Alice', 'Carl')->not->toContain('Bob');
    expect($this->event->guests()->where('name', 'Alice')->first()->max_companions)->toBe(2);
});

test('l\'envoi groupé n\'envoie que les invitations en attente avec un email', function () {
    Mail::fake();
    $pending = Guest::factory()->for($this->event)->create(['email' => 'a@example.com']);
    Guest::factory()->for($this->event)->create(['email' => 'b@example.com', 'invited_at' => now()]);
    Guest::factory()->for($this->event)->create(['email' => null]);

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/invitations")
        ->assertSessionHas('sent', 1);

    Mail::assertSent(InvitationMail::class, 1);
    Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('a@example.com'));
    expect($pending->fresh()->invited_at)->not->toBeNull();
});

test('le contenu du mail contient le lien d\'invitation personnel', function () {
    $guest = Guest::factory()->for($this->event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml($guest->token);
    $mail->assertSeeInHtml($this->event->title);
});

test('l\'envoi individuel échoue sans email', function () {
    Mail::fake();
    $guest = Guest::factory()->for($this->event)->create(['email' => null]);

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/guests/{$guest->id}/invitation")
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

test('on ne peut pas envoyer l\'invitation d\'un invité d\'un autre événement', function () {
    $other = Guest::factory()->create();

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/guests/{$other->id}/invitation")
        ->assertNotFound();
});

test('l\'invitation est accessible publiquement via le jeton', function () {
    $guest = Guest::factory()->for($this->event)->create(['name' => 'Alice']);

    $this->get("/i/{$guest->token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Invitations/Show')
            ->where('guest.name', 'Alice')
            ->where('guest.url', route('invitations.show', $guest->token))
            ->where('event.title', $this->event->title));
});

test('un jeton inconnu renvoie 404', function () {
    $this->get('/i/'.str_repeat('x', 40))->assertNotFound();
});

test('le lien WhatsApp est normalisé et encode le message', function () {
    $guest = Guest::factory()->for($this->event)->create(['phone' => '+33 (6) 12-34-56-78']);

    $url = $guest->whatsappUrl();

    expect($url)->toStartWith('https://wa.me/33612345678?text=')->and($url)->toContain(rawurlencode($guest->token));
});

test('pas de lien WhatsApp sans numéro', function () {
    expect(Guest::factory()->for($this->event)->create(['phone' => null])->whatsappUrl())->toBeNull();
});

test('supprimer un événement supprime ses invités', function () {
    Guest::factory()->for($this->event)->count(2)->create();

    $this->event->delete();

    expect(Guest::count())->toBe(0);
});
