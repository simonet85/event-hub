<?php

use App\Enums\RsvpStatus;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\Photo;
use App\Models\User;
use App\Services\GuestDataEraser;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create(['slug' => 'fete-donnees', 'event_date' => now()->addMonth()]);
    $this->guest = Guest::factory()->for($this->event)->create([
        'name' => 'Awa Koné',
        'email' => 'awa@example.test',
        'phone' => '+33612345678',
        'rsvp_status' => RsvpStatus::Accepted,
        'companions_count' => 1,
        'dietary_restrictions' => 'Arachides',
        'favorite_song' => 'Chanson',
        'rsvp_message' => 'À bientôt',
        'consent_at' => now(),
    ]);
});

function guestWithContent(Event $event): array
{
    $guest = Guest::factory()->for($event)->create(['rsvp_status' => RsvpStatus::Accepted, 'dietary_restrictions' => 'Sans gluten']);
    Storage::disk('local')->put('photos/test/a.jpg', 'x');
    $photo = Photo::factory()->for($guest)->create(['path' => 'photos/test/a.jpg']);
    $entry = GuestbookEntry::factory()->for($guest)->create(['message' => 'Un mot']);
    $contribution = Contribution::factory()->for($guest)->confirmed()->create(['amount' => 10000, 'message' => 'Avec amour']);

    return [$guest, $photo, $entry, $contribution];
}

// --- Consentement -------------------------------------------------------------------------------------------

test('un régime alimentaire exige un consentement explicite', function () {
    $guest = Guest::factory()->for($this->event)->create();

    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'accepted', 'dietary_restrictions' => 'Sans gluten'])->assertSessionHasErrors('consent');
    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'accepted', 'dietary_restrictions' => 'Sans gluten', 'consent' => false])->assertSessionHasErrors('consent');

    expect($guest->fresh()->dietary_restrictions)->toBeNull()->and($guest->fresh()->consent_at)->toBeNull();
});

test('avec le consentement, le régime est enregistré et la date du consentement conservée', function () {
    $guest = Guest::factory()->for($this->event)->create();

    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'accepted', 'dietary_restrictions' => 'Sans gluten', 'consent' => true])->assertSessionHasNoErrors();

    expect($guest->fresh()->dietary_restrictions)->toBe('Sans gluten')->and($guest->fresh()->consent_at)->not->toBeNull();
});

test('sans régime alimentaire, aucun consentement n\'est demandé', function () {
    $guest = Guest::factory()->for($this->event)->create();

    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'accepted'])->assertSessionHasNoErrors();
    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'declined', 'dietary_restrictions' => 'Ignoré'])->assertSessionHasNoErrors();

    expect($guest->fresh()->consent_at)->toBeNull()->and($guest->fresh()->dietary_restrictions)->toBeNull();
});

// --- Suppression par l'hôte, à la demande de l'invité -------------------------------------------------------------------------------

test('l\'hôte supprime les données d\'un invité : coordonnées, réponse, livre d\'or, photos et message de don', function () {
    [$guest, $photo, $entry, $contribution] = guestWithContent($this->event);
    $oldToken = $guest->token;

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$guest->id}/data")->assertRedirect()->assertSessionHas('guest_erased', true);

    $guest->refresh();
    expect($guest->name)->toBe(GuestDataEraser::ERASED_NAME)
        ->and($guest->email)->toBeNull()
        ->and($guest->phone)->toBeNull()
        ->and($guest->dietary_restrictions)->toBeNull()
        ->and($guest->favorite_song)->toBeNull()
        ->and($guest->rsvp_message)->toBeNull()
        ->and($guest->consent_at)->toBeNull()
        ->and($guest->erased_at)->not->toBeNull()
        ->and($guest->token)->not->toBe($oldToken);
    expect(GuestbookEntry::find($entry->id))->toBeNull();
    expect(Photo::find($photo->id))->toBeNull();
    Storage::disk('local')->assertMissing('photos/test/a.jpg');
    expect($contribution->fresh()->message)->toBeNull()->and($contribution->fresh()->amount)->toBe(10000);
});

test('les chiffres de l\'événement restent exacts après la suppression', function () {
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$this->guest->id}/data");

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page
        ->where('rsvp.accepted', 1)
        ->where('rsvp.headcount', 2)
        ->where('dietary', []));
});

test('l\'ancien lien d\'invitation ne fonctionne plus', function () {
    $oldToken = $this->guest->token;
    $this->get("/i/{$oldToken}")->assertOk();

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$this->guest->id}/data");

    $this->get("/i/{$oldToken}")->assertNotFound();
    $this->post("/i/{$oldToken}/data-request")->assertNotFound();
});

test('l\'invité supprimé ne figure plus dans le plan de table ni dans les exports avec ses coordonnées', function () {
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$this->guest->id}/data");

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.csv")->assertOk()
        ->assertDontSee('awa@example.test')->assertDontSee('Arachides')->assertDontSee('Awa Koné');
});

test('la suppression d\'un invité ne touche pas aux autres', function () {
    [$other, $photo, $entry] = guestWithContent($this->event);

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$this->guest->id}/data");

    expect(Photo::find($photo->id))->not->toBeNull()->and(GuestbookEntry::find($entry->id))->not->toBeNull();
    expect($other->fresh()->dietary_restrictions)->toBe('Sans gluten');
});

test('la page de l\'invitation annonce la durée de conservation', function () {
    config(['privacy.retention_days' => 45]);

    $this->get("/i/{$this->guest->token}")->assertInertia(fn (Assert $page) => $page->where('retention_days', 45));
});

test('un invité demande la suppression : rien n\'est effacé, l\'hôte est prévenu', function () {
    $this->post("/i/{$this->guest->token}/data-request")->assertRedirect()->assertSessionHas('erasure_requested', true);

    $guest = $this->guest->fresh();
    expect($guest->erasure_requested_at)->not->toBeNull()
        ->and($guest->erased_at)->toBeNull()
        ->and($guest->email)->toBe('awa@example.test');
});

test('redemander ne change pas la date de la première demande', function () {
    $this->post("/i/{$this->guest->token}/data-request");
    $first = $this->guest->fresh()->erasure_requested_at;

    $this->travel(2)->days();
    $this->post("/i/{$this->guest->token}/data-request");

    expect($this->guest->fresh()->erasure_requested_at->equalTo($first))->toBeTrue();
});

test('un invité ne peut plus supprimer lui-même ses données', function () {
    $this->delete("/i/{$this->guest->token}/data")->assertNotFound();

    expect($this->guest->fresh()->erased_at)->toBeNull();
});

test('l\'invitation indique si une demande est en cours', function () {
    $this->get("/i/{$this->guest->token}")->assertInertia(fn (Assert $page) => $page->where('guest.erasure_requested', false));

    $this->post("/i/{$this->guest->token}/data-request");

    $this->get("/i/{$this->guest->token}")->assertInertia(fn (Assert $page) => $page->where('guest.erasure_requested', true));
});

test('l\'hôte voit les demandes dans la liste des invités et sur le tableau de bord', function () {
    Guest::factory()->for($this->event)->create();
    $this->post("/i/{$this->guest->token}/data-request");

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/guests")->assertInertia(fn (Assert $page) => $page
        ->where('guests.data', fn ($guests) => collect($guests)->where('erasure_requested', true)->count() === 1));
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page->where('privacy.erasure_requests', 1));
});

test('la suppression par l\'hôte clôt la demande', function () {
    $this->post("/i/{$this->guest->token}/data-request");

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$this->guest->id}/data")->assertRedirect();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page->where('privacy.erasure_requests', 0));
    expect($this->guest->fresh()->erasure_requested_at)->toBeNull();
});

test('seul l\'hôte de l\'événement efface les données d\'un invité', function () {
    $url = "/events/{$this->event->slug}/guests/{$this->guest->id}/data";
    $other = Guest::factory()->create();

    $this->delete($url)->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->delete($url)->assertForbidden();
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guests/{$other->id}/data")->assertNotFound();

    expect($this->guest->fresh()->erased_at)->toBeNull()->and($other->fresh()->erased_at)->toBeNull();
});

// --- Suppression par l'hôte ---------------------------------------------------------------------------------

test('l\'hôte supprime les données de tous les invités une fois l\'événement passé', function () {
    [$guest, $photo] = guestWithContent($this->event);
    $this->event->forceFill(['event_date' => now()->subDay(), 'caterer_token' => 'jeton', 'caterer_link_expires_at' => now()->addDay()])->save();

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guest-data")->assertRedirect()->assertSessionHas('guest_data_purged', true);

    expect($this->guest->fresh()->erased_at)->not->toBeNull()
        ->and($guest->fresh()->name)->toBe(GuestDataEraser::ERASED_NAME)
        ->and(Photo::count())->toBe(0)
        ->and($this->event->fresh()->guest_data_purged_at)->not->toBeNull()
        ->and($this->event->fresh()->caterer_token)->toBeNull();
});

test('l\'hôte ne peut pas supprimer les données avant la fin de l\'événement', function () {
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/guest-data")->assertStatus(422);

    expect($this->guest->fresh()->erased_at)->toBeNull();
});

test('un autre hôte ou un visiteur ne peut pas supprimer les données d\'un événement', function () {
    $this->event->forceFill(['event_date' => now()->subDay()])->save();

    $this->delete("/events/{$this->event->slug}/guest-data")->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->delete("/events/{$this->event->slug}/guest-data")->assertForbidden();

    expect($this->guest->fresh()->erased_at)->toBeNull();
});

test('le tableau de bord indique la date de suppression automatique et si la suppression manuelle est possible', function () {
    config(['privacy.retention_days' => 30]);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page
        ->where('privacy.retention_days', 30)
        ->where('privacy.can_purge', false)
        ->where('privacy.purged_at', null));

    $this->event->forceFill(['event_date' => now()->subDay()])->save();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn (Assert $page) => $page->where('privacy.can_purge', true));
});

// --- Suppression automatique --------------------------------------------------------------------------------

test('la commande supprime les données des événements terminés depuis plus que la durée de conservation', function () {
    config(['privacy.retention_days' => 30]);
    [$guest, $photo] = guestWithContent($this->event);
    $this->event->forceFill(['event_date' => now()->subDays(31)])->save();
    $recent = Event::factory()->for($this->host)->create(['event_date' => now()->subDays(10)]);
    $recentGuest = Guest::factory()->for($recent)->create(['email' => 'garde@example.test']);

    $this->artisan('events:purge-guest-data')->assertSuccessful();

    expect($guest->fresh()->erased_at)->not->toBeNull()
        ->and($this->guest->fresh()->email)->toBeNull()
        ->and(Photo::count())->toBe(0)
        ->and($this->event->fresh()->guest_data_purged_at)->not->toBeNull()
        ->and($recentGuest->fresh()->email)->toBe('garde@example.test')
        ->and($recent->fresh()->guest_data_purged_at)->toBeNull();
});

test('la commande ne repasse pas sur un événement déjà purgé et ne touche pas un événement à venir', function () {
    $this->event->forceFill(['event_date' => now()->subDays(200), 'guest_data_purged_at' => now()->subDays(100)])->save();
    $this->guest->forceFill(['email' => 'reste@example.test'])->save();
    $future = Event::factory()->for($this->host)->create(['event_date' => now()->addDays(5)]);
    $futureGuest = Guest::factory()->for($future)->create(['email' => 'futur@example.test']);

    $this->artisan('events:purge-guest-data')->assertSuccessful();

    expect($this->guest->fresh()->email)->toBe('reste@example.test')->and($futureGuest->fresh()->email)->toBe('futur@example.test');
});

test('la suppression automatique est planifiée chaque jour', function () {
    $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command);

    expect($events->contains(fn ($c) => str_contains($c, 'events:purge-guest-data')))->toBeTrue();
});
