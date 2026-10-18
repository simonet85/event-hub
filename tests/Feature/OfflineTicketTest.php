<?php

use App\Enums\ModerationStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->event = Event::factory()->for(User::factory())->create([
        'title' => 'Mariage de Clara & Thomas',
        'slug' => 'mariage-billet',
        'dress_code' => 'Tenue de soirée',
        'latitude' => 5.32,
        'longitude' => -4.02,
    ]);
    $this->guest = Guest::factory()->for($this->event)->create(['name' => 'Aminata Zéhoré']);
    $this->other = Guest::factory()->for($this->event)->create(['name' => 'Yao Konan', 'email' => 'yao@example.test']);
});

test("le billet n'expose que l'invité lui-même et l'événement", function () {
    $this->get("/i/{$this->guest->token}/billet")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Invitations/Ticket')
            ->where('guest', ['name' => 'Aminata Zéhoré', 'url' => $this->guest->invitationUrl()])
            ->where('event.title', 'Mariage de Clara & Thomas')
            ->where('event.schedule', [])
            ->where('event.map', null)
            ->where('event.dress_code', null)
            ->where('ticketPath', "/i/{$this->guest->token}/billet")
            ->missing('guestbook')
            ->missing('photos')
            ->missing('cagnotte')
            ->missing('mine'));
});

test("le billet ne contient aucune donnée d'un autre invité", function () {
    GuestbookEntry::factory()->for($this->event)->for($this->other, 'guest')->create(['message' => 'Message secret de Yao', 'status' => ModerationStatus::Approved]);

    $content = $this->get("/i/{$this->guest->token}/billet")->assertOk()->getContent();

    expect($content)->not->toContain('Yao Konan')
        ->not->toContain('Message secret')
        ->not->toContain('yao@example.test')
        ->not->toContain($this->other->token);
});

test('le billet est servi sans mise en cache partagée et sans indexation', function () {
    $response = $this->get("/i/{$this->guest->token}/billet")->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
});

test('un billet inconnu ou d\'un événement archivé renvoie 404', function () {
    $this->get('/i/inconnu/billet')->assertNotFound();

    $this->event->forceFill(['archived_at' => now()])->save();
    $this->get("/i/{$this->guest->token}/billet")->assertNotFound();
    $this->get("/i/{$this->guest->token}/billet/manifest.webmanifest")->assertNotFound();
});

test("le manifeste permet d'ajouter le billet à l'écran d'accueil, sans nom d'invité", function () {
    $response = $this->get("/i/{$this->guest->token}/billet/manifest.webmanifest")->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/manifest+json');

    $manifest = $response->json();
    expect($manifest['start_url'])->toBe("/i/{$this->guest->token}/billet")
        ->and($manifest['scope'])->toBe("/i/{$this->guest->token}/billet")
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['name'])->toContain('Mariage de Clara & Thomas')
        ->and($manifest['icons'])->toHaveCount(2)
        ->and($manifest['theme_color'])->toMatch('/^#[0-9a-f]{6}$/i');

    expect($response->getContent())->not->toContain('Aminata')->not->toContain('Zéhoré');
});

test('les icônes du manifeste existent', function () {
    foreach ([192, 512] as $size) {
        [$width, $height] = getimagesize(public_path("icons/ticket-{$size}.png"));
        expect([$width, $height])->toBe([$size, $size]);
    }
});

test('le service worker est limité au billet et aux fichiers statiques, et ne met rien en cache de lui-même', function () {
    // On analyse le code, pas les commentaires qui décrivent justement ce que le service worker ne touche pas.
    $sw = preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', file_get_contents(public_path('billet-sw.js')));

    expect($sw)->toContain('const TICKET_PATH = /^\\/i\\/[^/]+\\/billet$/;')
        ->toContain('const STATIC_PATH = /^\\/(build\\/assets|fonts)\\//;')
        ->toContain("request.method !== 'GET'")
        ->toContain('url.origin !== self.location.origin')
        // Aucun ajout automatique : le cache n'est rempli que par le clic de l'invité (lib/offline.ts).
        ->not->toContain('cache.add')
        ->not->toContain('addAll')
        // Rien qui touche au livre d'or, aux photos, aux paiements ou à l'espace hôte.
        ->not->toContain('guestbook')
        ->not->toContain('photos')
        ->not->toContain('donations')
        ->not->toContain('/events');
});
