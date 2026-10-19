<?php

use App\Jobs\GeocodeEvent;
use App\Models\Event;
use App\Models\User;
use App\Services\Geocoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

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

function runGeocodeJob(Event $event, ?string $address = null): void
{
    (new GeocodeEvent($event->id, $address ?? (string) $event->fresh()->location_address))->handle(app(Geocoder::class));
}

test("changer l'adresse invalide l'ancienne position et lance la recherche en file d'attente", function () {
    Queue::fake();
    Http::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'location_address' => 'Mairie du Plateau, Abidjan',
    ]))->assertRedirect();

    $event = $this->event->fresh();
    // Jamais la carte de l'ancien lieu sous la nouvelle adresse.
    expect($event->latitude)->toBeNull()->and($event->longitude)->toBeNull();
    Queue::assertPushed(GeocodeEvent::class, fn (GeocodeEvent $job) => $job->eventId === $event->id && $job->address === 'Mairie du Plateau, Abidjan');
    Http::assertNothingSent();
});

test("la requête de l'hôte n'appelle jamais le service de géocodage", function () {
    Queue::fake();
    Http::fake();

    $this->actingAs($this->host)->post('/events', [
        'title' => 'Fête au Plateau',
        'type' => 'party',
        'event_date' => now()->addMonth()->format('Y-m-d H:i:s'),
        'location_address' => 'Mairie du Plateau, Abidjan',
    ])->assertRedirect();

    Http::assertNothingSent();
    Queue::assertPushed(GeocodeEvent::class, fn (GeocodeEvent $job) => $job->address === 'Mairie du Plateau, Abidjan');
});

test('une adresse inchangée garde sa position et ne lance rien', function () {
    Queue::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, ['title' => 'Nouveau titre']))
        ->assertRedirect();

    Queue::assertNothingPushed();
    expect($this->event->fresh()->latitude)->toBe(48.8049);
});

test('une adresse vidée efface la position sans rien chercher', function () {
    Queue::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, ['location_address' => null]))
        ->assertRedirect();

    $event = $this->event->fresh();
    expect($event->latitude)->toBeNull()->and($event->longitude)->toBeNull()->and($event->location_address)->toBeNull();
    Queue::assertNothingPushed();
});

test("une adresse inchangée à l'espace près n'est pas considérée comme modifiée", function () {
    Queue::fake();

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}", eventPayload($this->event, [
        'location_address' => "  Place d'Armes, 78000 Versailles  ",
    ]))->assertRedirect();

    Queue::assertNothingPushed();
    expect($this->event->fresh()->latitude)->toBe(48.8049);
});

test('le job place la position trouvée sur la carte', function () {
    Http::fake([NOMINATIM => Http::response([['lat' => '5.3237274', 'lon' => '-4.0240233']])]);
    $this->event->forceFill(['location_address' => 'Mairie du Plateau, Abidjan', 'latitude' => null, 'longitude' => null])->save();

    runGeocodeJob($this->event);

    $event = $this->event->fresh();
    expect($event->latitude)->toBe(5.323727)->and($event->longitude)->toBe(-4.024023);
    Http::assertSent(fn ($request) => $request['q'] === 'Mairie du Plateau, Abidjan' && $request->hasHeader('User-Agent'));
});

test("le job laisse la carte vide quand l'adresse est introuvable ou que le service est en panne", function () {
    $this->event->forceFill(['location_address' => 'Avenue Charles Konan Banny, Abidjan', 'latitude' => null, 'longitude' => null])->save();

    Http::fake([NOMINATIM => Http::response([])]);
    runGeocodeJob($this->event);
    expect($this->event->fresh()->latitude)->toBeNull();

    Http::fake([NOMINATIM => Http::response('erreur', 500)]);
    runGeocodeJob($this->event);
    expect($this->event->fresh()->latitude)->toBeNull();
    // Un échec n'est jamais mémorisé : la prochaine tentative peut réussir.
    expect(Cache::get('geocode:'.sha1('Avenue Charles Konan Banny, Abidjan')))->toBeNull();
});

test("le job ignore un résultat devenu obsolète si l'adresse a encore changé", function () {
    Http::fake([NOMINATIM => Http::response([['lat' => '5.3', 'lon' => '-4.0']])]);
    $this->event->forceFill(['location_address' => 'Nouvelle adresse', 'latitude' => null, 'longitude' => null])->save();

    runGeocodeJob($this->event, 'Ancienne adresse');

    Http::assertNothingSent();
    expect($this->event->fresh()->latitude)->toBeNull();
});

test("le job ne plante pas si l'événement a disparu", function () {
    Http::fake();

    (new GeocodeEvent(999999, 'Quelque part'))->handle(app(Geocoder::class));

    Http::assertNothingSent();
});

test('le job est mis en file avec une limite de temps et un nombre de tentatives bornés', function () {
    $job = new GeocodeEvent($this->event->id, 'Abidjan');

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->timeout)->toBe(90)
        ->and($job->tries)->toBe(2);
});

test("le tableau de bord signale une adresse qui n'a pas (encore) de position", function () {
    $show = fn () => $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard");

    $show()->assertInertia(fn (Assert $page) => $page->where('event.map_missing', false));

    $this->event->forceFill(['latitude' => null, 'longitude' => null])->save();
    $show()->assertInertia(fn (Assert $page) => $page->where('event.map_missing', true));

    $this->event->forceFill(['location_address' => null])->save();
    $show()->assertInertia(fn (Assert $page) => $page->where('event.map_missing', false));
});

test('une adresse trop détaillée est retentée avec des variantes plus courtes', function () {
    Http::fake([
        NOMINATIM => Http::sequence()
            ->push([])
            ->push([['lat' => '5.3237274', 'lon' => '-4.0240233']]),
    ]);
    $address = 'Mairie du Plateau, Avenue Charles Konan Banny, Mairie du Plateau, Le Plateau, Abidjan, Côte d’Ivoire';
    $this->event->forceFill(['location_address' => $address, 'latitude' => null, 'longitude' => null])->save();

    runGeocodeJob($this->event);

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

test("le géocodage désactivé n'appelle jamais le service", function () {
    config(['services.geocoding.enabled' => false]);
    Http::fake();
    $this->event->forceFill(['latitude' => null, 'longitude' => null])->save();

    runGeocodeJob($this->event);

    Http::assertNothingSent();
    expect($this->event->fresh()->latitude)->toBeNull();
});
