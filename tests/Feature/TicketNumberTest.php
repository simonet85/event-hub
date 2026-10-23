<?php

use App\Models\Event;
use App\Models\Guest;
use Inertia\Testing\AssertableInertia as Assert;

test('chaque invité a un numéro de billet lisible, stable et distinct, sans lien avec son jeton', function () {
    $event = Event::factory()->create();
    [$a, $b] = Guest::factory()->for($event)->count(2)->create();

    expect($a->ticketNumber())->toMatch('/^[A-F0-9]{4}-[A-F0-9]{4}$/')
        ->and($a->ticketNumber())->toBe($a->ticketNumber())
        ->and($a->ticketNumber())->not->toBe($b->ticketNumber())
        ->and(str_replace('-', '', $a->ticketNumber()))->not->toBeIn([substr($a->token, 0, 8), strtoupper(substr($a->token, 0, 8))]);
});

test('l\'invitation transmet le numéro de billet de l\'invité', function () {
    $guest = Guest::factory()->for(Event::factory()->create())->create();

    $this->get("/i/{$guest->token}")->assertInertia(fn (Assert $page) => $page
        ->where('guest.ticket_number', $guest->ticketNumber()));
});
