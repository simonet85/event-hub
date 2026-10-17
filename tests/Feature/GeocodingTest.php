<?php

use App\Models\Event;
use App\Models\User;
use App\Services\Geocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const NOMINATIM = 'nominatim.openstreetmap.org/search*';

beforeEach(function () {
    config(['services.geocoding.enabled' => true, 'services.geocoding.delay_ms' => 0]);
    Cache::flush();

    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create([
        'location_name' => 'Mairie du Plateau',
        'location_address' => "Place d'Armes, 78000 Versailles",
        'latitude' => 48.8049,
        'longitude' => 2.1204,
        'event_date' => now()->addMonths(2),
    ]);
});

function eventPayload(Event $event, array $overrides = []): array
{
    return array_merge([
        'title' => $event->title,
        'type' => $event->type->value,
        'event_date' => $event->event_date->format('Y-m-d\TH:i'),
        'location_name' => $event->location_name,
        'location_address' => $event->location_address,
    ], $overrides);
}

test("changer l'adresse met à jour la position sur la carte", function () {
    Http::fake([NOMINATIM => Http::response([['lat' => '5.3237274', 'lon' => '-4.0240233']])]);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'location_address' => 'Mairie du Plateau, Abidjan',
    ]))->assertRedirect()->assertSessionHas('map_not_found', false);

    $event = $this->event->fresh();
    expect($event->latitude)->toBe(5.323727)->and($event->longitude)->toBe(-4.024023);
    Http::assertSent(fn ($request) => $request['q'] === 'Mairie du Plateau, Abidjan' && $request->hasHeader('User-Agent'));
});

test('une adresse inchangée ne déclenche aucun appel de géocodage', function () {
    Http::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, ['title' => 'Nouveau titre']))
        ->assertRedirect();

    Http::assertNothingSent();
    expect($this->event->fresh()->latitude)->toBe(48.8049);
});

test("une adresse introuvable conserve la carte et prévient l'hôte", function () {
    Http::fake([NOMINATIM => Http::response([])]);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'location_address' => "Quelque part qui n'existe pas",
    ]))->assertRedirect()->assertSessionHas('map_not_found', true);

    expect($this->event->fresh()->latitude)->toBe(48.8049);
});

test('une panne du service de géocodage ne casse pas la modification et rien n\'est mis en cache', function () {
    Http::fake([NOMINATIM => Http::response('erreur', 500)]);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'title' => 'Titre conservé',
        'location_address' => 'Avenue Charles Konan Banny, Abidjan',
    ]))->assertRedirect()->assertSessionHas('map_not_found', true);

    expect($this->event->fresh()->title)->toBe('Titre conservé')
        ->and($this->event->fresh()->latitude)->toBe(48.8049)
        ->and(Cache::get('geocode:'.sha1('Avenue Charles Konan Banny, Abidjan')))->toBeNull();
});

test('une adresse trop détaillée est retentée avec des variantes plus courtes', function () {
    Http::fake([
        NOMINATIM => Http::sequence()
            ->push([])
            ->push([['lat' => '5.3237274', 'lon' => '-4.0240233']]),
    ]);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'location_address' => 'Mairie du Plateau, Avenue Charles Konan Banny, Mairie du Plateau, Le Plateau, Abidjan, Côte d’Ivoire',
    ]))->assertSessionHas('map_not_found', false);

    expect($this->event->fresh()->latitude)->toBe(5.323727);
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request['q'] === 'Mairie du Plateau, Abidjan');
});

test('les variantes écartent les doublons et les apostrophes typographiques', function () {
    $queries = app(Geocoder::class)->candidates(
        'Mairie du Plateau, Avenue Charles Konan Banny, Mairie du Plateau, Le Plateau, Abidjan, Côte d’Ivoire',
        'Mairie du Plateau',
    );

    expect($queries[0])->toBe("Mairie du Plateau, Avenue Charles Konan Banny, Le Plateau, Abidjan, Côte d'Ivoire")
        ->and($queries)->toContain('Mairie du Plateau, Abidjan')
        ->and($queries)->toHaveCount(count(array_unique($queries)));
});

test('une position trouvée est mise en cache', function () {
    Http::fake([NOMINATIM => Http::response([['lat' => '5.3', 'lon' => '-4.0']])]);

    $geocoder = app(Geocoder::class);
    $geocoder->locate('Abidjan');
    $geocoder->locate('Abidjan');

    Http::assertSentCount(1);
});

test("la création d'un événement le place sur la carte", function () {
    Http::fake([NOMINATIM => Http::response([['lat' => '5.3237274', 'lon' => '-4.0240233']])]);

    $this->actingAs($this->host)->post('/events', [
        'title' => 'Fête au Plateau',
        'type' => 'party',
        'event_date' => now()->addMonth()->format('Y-m-d H:i:s'),
        'location_address' => 'Mairie du Plateau, Abidjan',
    ])->assertRedirect();

    expect(Event::firstWhere('title', 'Fête au Plateau')->latitude)->toBe(5.323727);
});

test("le géocodage désactivé n'appelle jamais le service", function () {
    config(['services.geocoding.enabled' => false]);
    Http::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, ['location_address' => 'Abidjan']))
        ->assertRedirect();

    Http::assertNothingSent();
});
