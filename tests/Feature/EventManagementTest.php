<?php

use App\Enums\ModerationStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create([
        'slug' => 'mariage-fige',
        'title' => 'Mariage initial',
        'event_date' => now()->addMonths(2),
    ]);
});

function updatePayload(Event $event, array $overrides = []): array
{
    return array_merge([
        'title' => 'Mariage corrigé',
        'type' => 'wedding',
        'event_date' => $event->event_date->format('Y-m-d\TH:i'),
        'location_name' => 'Nouveau lieu',
    ], $overrides);
}

test('le propriétaire voit la page de modification préremplie', function () {
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Edit')
            ->where('event.title', 'Mariage initial')
            ->where('event.slug', 'mariage-fige')
            ->where('event.archived', false));
});

test('un autre hôte ne peut ni modifier, ni archiver, ni restaurer', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->get("/events/{$this->event->slug}/edit")->assertForbidden();
    $this->actingAs($other)->patch("/events/{$this->event->slug}", updatePayload($this->event))->assertForbidden();
    $this->actingAs($other)->post("/events/{$this->event->slug}/archive")->assertForbidden();
    $this->actingAs($other)->post("/events/{$this->event->slug}/restore")->assertForbidden();

    expect($this->event->fresh()->title)->toBe('Mariage initial')->and($this->event->fresh()->archived_at)->toBeNull();
});

test('un visiteur non connecté est renvoyé vers la connexion', function () {
    $this->get("/events/{$this->event->slug}/edit")->assertRedirect('/login');
    $this->post("/events/{$this->event->slug}/archive")->assertRedirect('/login');
});

test('la modification met à jour les champs mais le slug reste figé', function () {
    $this->actingAs($this->host)
        ->patch("/events/{$this->event->slug}", updatePayload($this->event, ['slug' => 'autre-slug']))
        ->assertRedirect(route('dashboard.show', $this->event))
        ->assertSessionHas('event_updated', true);

    $event = $this->event->fresh();
    expect($event->title)->toBe('Mariage corrigé')
        ->and($event->location_name)->toBe('Nouveau lieu')
        ->and($event->slug)->toBe('mariage-fige');
});

test('tous les champs modifiables sont enregistrés, et changer le type ne remplace pas le thème choisi', function () {
    $newDate = now()->addMonths(5)->startOfMinute();
    $originalTheme = $this->event->theme_config;

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", [
        'title' => 'Baptême de Léa',
        'type' => 'baptism',
        'event_date' => $newDate->format('Y-m-d\TH:i'),
        'location_name' => 'Église Saint-Louis',
        'location_address' => '12 rue de la Paix, Abidjan',
        'welcome_message' => 'Soyez les bienvenus !',
        'theme_config' => [
            'primary_color' => '#112233',
            'secondary_color' => '#f0f0f0',
            'font_family' => 'serif',
        ],
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard.show', $this->event));

    $event = $this->event->fresh();
    expect($event->title)->toBe('Baptême de Léa')
        ->and($event->type->value)->toBe('baptism')
        ->and($event->event_date->equalTo($newDate))->toBeTrue()
        ->and($event->location_name)->toBe('Église Saint-Louis')
        ->and($event->location_address)->toBe('12 rue de la Paix, Abidjan')
        ->and($event->welcome_message)->toBe('Soyez les bienvenus !')
        ->and($event->theme_config)->toBe([
            'primary_color' => '#112233',
            'secondary_color' => '#f0f0f0',
            'font_family' => 'serif',
        ])
        ->and($event->theme_config)->not->toBe($originalTheme)
        ->and($event->slug)->toBe('mariage-fige');
});

test('changer le type seul conserve le thème personnalisé existant', function () {
    $theme = $this->event->theme_config;

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", updatePayload($this->event, ['type' => 'party']))
        ->assertSessionHasNoErrors();

    $event = $this->event->fresh();
    expect($event->type->value)->toBe('party')->and($event->theme_config)->toBe($theme);
});

test('les champs facultatifs peuvent être vidés', function () {
    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", updatePayload($this->event, [
        'location_name' => null,
        'location_address' => null,
        'welcome_message' => null,
    ]))->assertSessionHasNoErrors();

    $event = $this->event->fresh();
    expect($event->location_name)->toBeNull()
        ->and($event->location_address)->toBeNull()
        ->and($event->welcome_message)->toBeNull();
});

test('le thème fourni est fusionné avec le thème existant', function () {
    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", updatePayload($this->event, [
        'theme_config' => ['primary_color' => '#112233'],
    ]))->assertRedirect();

    $theme = $this->event->fresh()->theme_config;
    expect($theme['primary_color'])->toBe('#112233')->and($theme)->toHaveKey('secondary_color');
});

test('une date inchangée reste valide même passée, une nouvelle date passée est refusée', function () {
    $past = Event::factory()->for($this->host)->create(['event_date' => now()->subWeek()]);

    $this->actingAs($this->host)->patch("/events/{$past->slug}", updatePayload($past, ['title' => 'Après coup']))
        ->assertSessionHasNoErrors();
    expect($past->fresh()->title)->toBe('Après coup');

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", updatePayload($this->event, [
        'event_date' => now()->subDay()->format('Y-m-d\TH:i'),
    ]))->assertSessionHasErrors('event_date');
});

test('une date invalide renvoie une erreur de validation et non une erreur serveur', function () {
    $this->actingAs($this->host)
        ->patch("/events/{$this->event->slug}", updatePayload($this->event, ['event_date' => 'pas-une-date']))
        ->assertSessionHasErrors('event_date');

    expect($this->event->fresh()->title)->toBe('Mariage initial');
});

test('la modification valide le titre et le type', function () {
    $this->actingAs($this->host)
        ->patch("/events/{$this->event->slug}", updatePayload($this->event, ['title' => '', 'type' => 'inconnu']))
        ->assertSessionHasErrors(['title', 'type']);
});

test('archiver retire l\'événement de la liste active et le range dans les archives', function () {
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/archive")
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('event_archived', 'Mariage initial');

    expect($this->event->fresh()->isArchived())->toBeTrue();

    $this->actingAs($this->host)->get('/events')->assertInertia(fn (Assert $page) => $page
        ->where('events.data', [])
        ->where('archivedCount', 1)
        ->where('showingArchived', false));

    $this->actingAs($this->host)->get('/events?archived=1')->assertInertia(fn (Assert $page) => $page
        ->has('events.data', 1)
        ->where('events.data.0.slug', 'mariage-fige')
        ->where('showingArchived', true));
});

test('restaurer remet l\'événement dans la liste active', function () {
    $this->event->forceFill(['archived_at' => now()])->save();

    $this->actingAs($this->host)->post("/events/{$this->event->slug}/restore")
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('event_restored');

    expect($this->event->fresh()->isArchived())->toBeFalse();
    $this->actingAs($this->host)->get('/events')->assertInertia(fn (Assert $page) => $page->has('events.data', 1));
});

test('un événement archivé reste consultable par son hôte', function () {
    $this->event->forceFill(['archived_at' => now()])->save();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertOk();
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/edit")
        ->assertInertia(fn (Assert $page) => $page->where('event.archived', true));
});

test('un événement archivé disparaît côté invités (page publique, invitation, RSVP, dons)', function () {
    $guest = Guest::factory()->for($this->event)->create();
    $this->event->forceFill(['archived_at' => now()])->save();

    $this->get("/e/{$this->event->slug}")->assertNotFound();
    $this->get("/i/{$guest->token}")->assertNotFound();
    $this->post("/i/{$guest->token}/rsvp", ['rsvp_status' => 'accepted'])->assertNotFound();
    $this->post("/i/{$guest->token}/donations", ['amount' => 1000])->assertNotFound();

    $this->event->forceFill(['archived_at' => null])->save();
    $this->get("/e/{$this->event->slug}")->assertOk();
    $this->get("/i/{$guest->token}")->assertOk();
});

test('une photo n\'est servie qu\'à l\'hôte de l\'événement', function () {
    Storage::fake('local');
    Storage::disk('local')->put('photos/a.jpg', 'x');
    $guest = Guest::factory()->for($this->event)->create();
    $photo = Photo::factory()->for($this->event)->for($guest)->create([
        'path' => 'photos/a.jpg',
        'status' => ModerationStatus::Approved,
    ]);

    $this->get("/photos/{$photo->id}")->assertNotFound();
    $this->actingAs(User::factory()->create())->get("/photos/{$photo->id}")->assertNotFound();
    $this->actingAs($this->host)->get("/photos/{$photo->id}")->assertOk();
});
