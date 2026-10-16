<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\ScheduleItem;
use App\Models\User;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create();
});

test('l\'hôte peut ajouter une étape au programme', function () {
    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/schedule", ['starts_at' => $this->event->event_date->toDateTimeString(), 'title' => 'Cérémonie'])
        ->assertRedirect();

    expect($this->event->scheduleItems()->first()->title)->toBe('Cérémonie');
});

test('une étape exige un titre et une date valide', function () {
    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/schedule", ['starts_at' => 'pas-une-date'])
        ->assertSessionHasErrors(['title', 'starts_at']);
});

test('les étapes sont triées par heure', function () {
    ScheduleItem::factory()->for($this->event)->create(['title' => 'Dîner', 'starts_at' => '2027-06-01 20:00']);
    ScheduleItem::factory()->for($this->event)->create(['title' => 'Cérémonie', 'starts_at' => '2027-06-01 15:00']);

    expect($this->event->scheduleItems->pluck('title')->all())->toBe(['Cérémonie', 'Dîner']);
});

test('l\'hôte peut supprimer une étape, mais pas celle d\'un autre événement', function () {
    $item = ScheduleItem::factory()->for($this->event)->create();
    $foreign = ScheduleItem::factory()->create();

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/schedule/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/schedule/{$item->id}")->assertRedirect();

    expect(ScheduleItem::find($item->id))->toBeNull()->and(ScheduleItem::find($foreign->id))->not->toBeNull();
});

test('un autre utilisateur ne peut pas gérer le programme', function () {
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get("/events/{$this->event->slug}/schedule")->assertForbidden();
    $this->actingAs($intruder)->post("/events/{$this->event->slug}/schedule", ['starts_at' => $this->event->event_date->toDateTimeString(), 'title' => 'X'])->assertForbidden();
    $this->actingAs($intruder)->patch("/events/{$this->event->slug}/details", ['dress_code' => 'X'])->assertForbidden();
});

test('l\'hôte enregistre dress code et coordonnées', function () {
    $this->actingAs($this->host)
        ->patch("/events/{$this->event->slug}/details", ['dress_code' => 'Tenue de soirée', 'latitude' => 48.8049, 'longitude' => 2.1204])
        ->assertSessionHas('details_saved', true);

    $event = $this->event->fresh();
    expect($event->dress_code)->toBe('Tenue de soirée')->and($event->latitude)->toBe(48.8049)->and($event->longitude)->toBe(2.1204);
});

test('les coordonnées doivent être valides et fournies ensemble', function () {
    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/details", ['latitude' => 95, 'longitude' => 2])->assertSessionHasErrors('latitude');
    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/details", ['latitude' => 48.8])->assertSessionHasErrors('longitude');
});

test('la page publique expose programme, dress code et carte', function () {
    $this->event->update(['dress_code' => 'Blanc', 'latitude' => 48.8, 'longitude' => 2.3]);
    ScheduleItem::factory()->for($this->event)->create(['title' => 'Cérémonie']);

    $this->get("/e/{$this->event->slug}")->assertInertia(fn ($page) => $page
        ->where('event.dress_code', 'Blanc')
        ->has('event.schedule', 1)
        ->where('event.schedule.0.title', 'Cérémonie')
        ->where('event.map.latitude', 48.8)
        ->where('event.map.embed_url', fn ($url) => str_starts_with($url, 'https://www.openstreetmap.org/export/embed.html?bbox=') && str_contains($url, 'marker=48.8,2.3'))
        ->has('event.map_url'));
});

test('sans coordonnées, un lien de recherche est proposé à partir du lieu', function () {
    $this->event->update(['location_name' => 'Château de Versailles', 'location_address' => null, 'latitude' => null, 'longitude' => null]);

    $this->get("/e/{$this->event->slug}")->assertInertia(fn ($page) => $page
        ->where('event.map', null)
        ->where('event.map_url', 'https://www.openstreetmap.org/search?query='.rawurlencode('Château de Versailles')));
});

test('l\'invitation personnelle expose aussi le programme', function () {
    $guest = Guest::factory()->for($this->event)->create();
    ScheduleItem::factory()->for($this->event)->count(2)->create();

    $this->get("/i/{$guest->token}")->assertInertia(fn ($page) => $page->has('event.schedule', 2));
});

test('supprimer un événement supprime son programme', function () {
    ScheduleItem::factory()->for($this->event)->count(2)->create();

    $this->event->delete();

    expect(ScheduleItem::count())->toBe(0);
});

test('une étape doit rester dans la fenêtre de l\'événement', function () {
    $date = $this->event->event_date;
    $post = fn (string $startsAt) => $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/schedule", ['starts_at' => $startsAt, 'title' => 'Étape']);

    $post($date->copy()->subDays(3)->toDateTimeString())->assertSessionHasErrors('starts_at');
    $post($date->copy()->addDays(5)->toDateTimeString())->assertSessionHasErrors('starts_at');
    $post($date->copy()->subHours(12)->toDateTimeString())->assertSessionHasNoErrors();
    $post($date->copy()->addDay()->toDateTimeString())->assertSessionHasNoErrors();

    expect($this->event->scheduleItems()->count())->toBe(2);
});

test('deux étapes à la même heure sont autorisées', function () {
    $at = $this->event->event_date->toDateTimeString();

    foreach (['Cocktail', 'Séance photo'] as $title) {
        $this->actingAs($this->host)->post("/events/{$this->event->slug}/schedule", ['starts_at' => $at, 'title' => $title])->assertSessionHasNoErrors();
    }

    expect($this->event->scheduleItems()->count())->toBe(2);
});

test('le programme est plafonné à 100 étapes', function () {
    ScheduleItem::factory()->for($this->event)->count(100)->create(['starts_at' => $this->event->event_date]);

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/schedule", ['starts_at' => $this->event->event_date->toDateTimeString(), 'title' => 'En trop'])
        ->assertSessionHasErrors('title');

    expect($this->event->scheduleItems()->count())->toBe(100);
});
