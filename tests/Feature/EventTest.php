<?php

use App\Models\Event;
use App\Models\User;
use App\Policies\EventPolicy;

function validPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Mariage Clara & Thomas',
        'type' => 'wedding',
        'event_date' => now()->addMonths(3)->toDateTimeString(),
        'location_name' => 'Château de Versailles',
    ], $overrides);
}

test('un hôte authentifié peut créer un événement avec un slug unique', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/events', validPayload(['slug' => 'mariage-clara-thomas-2026']))
        ->assertRedirect(route('events.index'));

    $event = Event::firstWhere('slug', 'mariage-clara-thomas-2026');
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe($user->id)
        ->and($event->theme_config['primary_color'])->toBe('#b08968');
});

test('un slug est généré automatiquement quand il est absent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/events', validPayload())->assertRedirect();

    expect($user->events()->first()->slug)->toStartWith('mariage-clara-thomas-');
});

test('le slug doit être unique', function () {
    Event::factory()->create(['slug' => 'deja-pris']);

    $this->actingAs(User::factory()->create())
        ->post('/events', validPayload(['slug' => 'deja-pris']))
        ->assertSessionHasErrors('slug');
});

test('la création échoue si le type est invalide', function () {
    $this->actingAs(User::factory()->create())
        ->post('/events', validPayload(['type' => 'funerailles']))
        ->assertSessionHasErrors('type');
});

test('la création échoue si la date est passée', function () {
    $this->actingAs(User::factory()->create())
        ->post('/events', validPayload(['event_date' => now()->subDay()->toDateTimeString()]))
        ->assertSessionHasErrors('event_date');
});

test('la création échoue si la couleur du thème est invalide', function () {
    $this->actingAs(User::factory()->create())
        ->post('/events', validPayload(['theme_config' => ['primary_color' => 'rouge']]))
        ->assertSessionHasErrors('theme_config.primary_color');
});

test('un visiteur non connecté ne peut pas créer un événement', function () {
    $this->post('/events', validPayload())->assertRedirect('/login');
});

test('la page publique est accessible sans authentification', function () {
    $event = Event::factory()->create(['slug' => 'fete-publique']);

    $this->get('/e/fete-publique')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/ShowPublic')
            ->where('event.slug', 'fete-publique')
            ->where('event.title', $event->title));
});

test('la page publique renvoie 404 pour un slug inconnu', function () {
    $this->get('/e/inconnu')->assertNotFound();
});

test('la liste ne montre que les événements de l\'hôte', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create();
    Event::factory()->create();

    $this->actingAs($user)->get('/events')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Index')->has('events.data', 1));
});

test('la policy refuse la modification à un non-propriétaire', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $policy = new EventPolicy;

    expect($policy->update($owner, $event))->toBeTrue()
        ->and($policy->update($other, $event))->toBeFalse()
        ->and($policy->delete($other, $event))->toBeFalse()
        ->and($policy->view($other, $event))->toBeFalse();
});
