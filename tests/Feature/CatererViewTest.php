<?php

use App\Enums\RsvpStatus;
use App\Http\Controllers\CatererViewController;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create(['slug' => 'mariage-traiteur']);
});

function createCatererLink(object $test, int $days = 7): string
{
    $test->actingAs($test->host)->post("/events/{$test->event->slug}/caterer-link", ['days' => $days])->assertRedirect();

    return $test->event->fresh()->caterer_token;
}

test('l\'hôte crée un lien traiteur qui expire à la date choisie', function () {
    $token = createCatererLink($this, 3);

    expect($token)->toHaveLength(48);
    expect($this->event->fresh()->caterer_link_expires_at->isSameDay(now()->addDays(3)))->toBeTrue();
    expect($this->event->fresh()->hasActiveCatererLink())->toBeTrue();
});

test('la durée du lien doit être l\'une de celles proposées', function () {
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/caterer-link", ['days' => 365])->assertSessionHasErrors('days');
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/caterer-link", [])->assertSessionHasErrors('days');

    expect($this->event->fresh()->caterer_token)->toBeNull();
});

test('seul l\'hôte de l\'événement peut créer ou révoquer le lien', function () {
    $other = User::factory()->create();

    $this->post("/events/{$this->event->slug}/caterer-link", ['days' => 7])->assertRedirect('/login');
    $this->actingAs($other)->post("/events/{$this->event->slug}/caterer-link", ['days' => 7])->assertForbidden();
    $this->actingAs($other)->delete("/events/{$this->event->slug}/caterer-link")->assertForbidden();

    expect($this->event->fresh()->caterer_token)->toBeNull();
});

test('le tableau de bord montre le lien actif et son expiration', function () {
    $token = createCatererLink($this);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page
        ->where('caterer_link.url', url("/traiteur/{$token}"))
        ->has('caterer_link.expires_at'));
});

test('sans lien, le tableau de bord n\'en montre pas', function () {
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page->where('caterer_link', null));
});

test('le traiteur voit les couverts, les tables et les régimes sans être connecté', function () {
    Guest::factory()->for($this->event)->create(['name' => 'Awa Koné', 'rsvp_status' => RsvpStatus::Accepted, 'companions_count' => 1, 'table_name' => 'Table 1', 'dietary_restrictions' => 'Sans gluten']);
    Guest::factory()->for($this->event)->create(['name' => 'Moussa', 'rsvp_status' => RsvpStatus::Accepted, 'companions_count' => 0, 'table_name' => 'Table 2']);
    Guest::factory()->for($this->event)->create(['rsvp_status' => RsvpStatus::Declined]);
    Guest::factory()->for($this->event)->create(['rsvp_status' => RsvpStatus::Pending]);
    $token = createCatererLink($this);

    auth()->logout();

    $this->get("/traiteur/{$token}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Caterer/Show')
        ->where('event.title', $this->event->title)
        ->where('counts', ['headcount' => 3, 'accepted' => 2, 'pending' => 1, 'declined' => 1])
        ->where('tables.0', ['table' => 'Table 1', 'headcount' => 2])
        ->where('dietary', [['guest' => 'Awa K.', 'table' => 'Table 1', 'restriction' => 'Sans gluten', 'seats' => 2]])
        ->has('expires_at'));
});

test('la vue traiteur ne contient aucune coordonnée ni nom complet d\'invité', function () {
    Guest::factory()->for($this->event)->create(['name' => 'Awa Koné', 'email' => 'awa.kone@example.test', 'phone' => '+33612345678', 'rsvp_status' => RsvpStatus::Accepted, 'dietary_restrictions' => 'Arachides', 'rsvp_message' => 'Message privé', 'favorite_song' => 'Chanson privée']);
    $token = createCatererLink($this);

    $html = $this->get("/traiteur/{$token}")->getContent();

    foreach (['awa.kone@example.test', '+33612345678', 'Awa Koné', 'Message privé', 'Chanson privée', $token.'/'] as $forbidden) {
        expect($html)->not->toContain($forbidden);
    }
    expect($html)->toContain('Awa K.');
});

test('la page n\'est ni indexée ni mise en cache', function () {
    $token = createCatererLink($this);

    $response = $this->get("/traiteur/{$token}")->assertOk();

    expect($response->headers->get('X-Robots-Tag'))->toContain('noindex');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('un lien expiré répond 410 et ne montre rien', function () {
    Guest::factory()->for($this->event)->create(['rsvp_status' => RsvpStatus::Accepted]);
    $token = createCatererLink($this);
    $this->travel(8)->days();

    $this->get("/traiteur/{$token}")->assertStatus(410);
});

test('un jeton inconnu, révoqué ou d\'un événement archivé répond 404', function () {
    $this->get('/traiteur/'.str_repeat('a', 48))->assertNotFound();

    $token = createCatererLink($this);
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/caterer-link")->assertRedirect();
    $this->get("/traiteur/{$token}")->assertNotFound();
    expect($this->event->fresh()->caterer_token)->toBeNull();

    $token = createCatererLink($this);
    $this->event->forceFill(['archived_at' => now()])->save();
    $this->get("/traiteur/{$token}")->assertNotFound();
});

test('créer un nouveau lien invalide l\'ancien', function () {
    $old = createCatererLink($this);
    $new = createCatererLink($this, 1);

    expect($new)->not->toBe($old);
    $this->get("/traiteur/{$old}")->assertNotFound();
    $this->get("/traiteur/{$new}")->assertOk();
});

test('la vue est en lecture seule : aucune route d\'écriture sur ce jeton', function () {
    $token = createCatererLink($this);

    $this->post("/traiteur/{$token}")->assertStatus(405);
    $this->patch("/traiteur/{$token}")->assertStatus(405);
    $this->delete("/traiteur/{$token}")->assertStatus(405);
});

test('les noms sont abrégés', function () {
    expect(CatererViewController::shortName('Awa Koné'))->toBe('Awa K.');
    expect(CatererViewController::shortName('Jean de la Fontaine'))->toBe('Jean F.');
    expect(CatererViewController::shortName('Moussa'))->toBe('Moussa');
    expect(CatererViewController::shortName('  élodie   émile '))->toBe('élodie É.');
    expect(CatererViewController::shortName(''))->toBe('');
});
