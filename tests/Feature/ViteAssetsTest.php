<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Support\ViteAssets;
use Inertia\Testing\AssertableInertia as Assert;

/** Écrit un faux manifeste Vite et renvoie son chemin. */
function fakeManifest(array $manifest): string
{
    $path = tempnam(sys_get_temp_dir(), 'manifest');
    file_put_contents($path, json_encode($manifest));

    return $path;
}

$manifest = [
    'resources/js/app.ts' => ['file' => 'assets/app-abc.js', 'isEntry' => true, 'css' => ['assets/app-abc.css'], 'imports' => ['_runtime.js'], 'dynamicImports' => ['resources/js/Pages/Auth/Login.vue']],
    '_runtime.js' => ['file' => 'assets/runtime-111.js'],
    'resources/js/Pages/Invitations/Ticket.vue' => ['file' => 'assets/Ticket-def.js', 'imports' => ['_layout.js', '_qr.js', '_runtime.js'], 'css' => []],
    '_layout.js' => ['file' => 'assets/EventPublicLayout-222.js', 'imports' => ['_runtime.js']],
    '_qr.js' => ['file' => 'assets/QrCode-333.js'],
    'resources/js/Pages/Auth/Login.vue' => ['file' => 'assets/Login-444.js', 'imports' => ['_runtime.js']],
    'resources/js/Pages/Events/Dashboard.vue' => ['file' => 'assets/Dashboard-555.js'],
];

test('suit les dépendances statiques et les feuilles de style, sans doublon', function () use ($manifest) {
    $assets = new ViteAssets(fakeManifest($manifest), '/chemin/inexistant/hot');

    expect($assets->forEntries(['resources/js/app.ts', 'resources/js/Pages/Invitations/Ticket.vue']))->toBe([
        '/build/assets/app-abc.js',
        '/build/assets/app-abc.css',
        '/build/assets/runtime-111.js',
        '/build/assets/Ticket-def.js',
        '/build/assets/EventPublicLayout-222.js',
        '/build/assets/QrCode-333.js',
    ]);
});

test("n'embarque jamais les imports dynamiques ni les autres pages", function () use ($manifest) {
    $files = (new ViteAssets(fakeManifest($manifest), '/chemin/inexistant/hot'))->forEntries(['resources/js/app.ts', 'resources/js/Pages/Invitations/Ticket.vue']);

    expect(implode(' ', $files))->not->toContain('Login')->not->toContain('Dashboard');
});

test('ignore une entrée inconnue, et renvoie une liste vide sans manifeste ou en mode développement', function () use ($manifest) {
    expect((new ViteAssets(fakeManifest($manifest), '/chemin/inexistant/hot'))->forEntries(['inconnue.ts']))->toBe([]);
    expect((new ViteAssets('/chemin/inexistant/manifest.json', '/chemin/inexistant/hot'))->forEntries(['resources/js/app.ts']))->toBe([]);

    $hot = tempnam(sys_get_temp_dir(), 'hot');
    expect((new ViteAssets(fakeManifest($manifest), $hot))->forEntries(['resources/js/app.ts']))->toBe([]);

    $broken = tempnam(sys_get_temp_dir(), 'manifest');
    file_put_contents($broken, '{pas du json');
    expect((new ViteAssets($broken, '/chemin/inexistant/hot'))->forEntries(['resources/js/app.ts']))->toBe([]);
});

test('le billet reçoit la liste exacte des fichiers à garder, polices comprises', function () use ($manifest) {
    $this->app->instance(ViteAssets::class, new ViteAssets(fakeManifest($manifest), '/chemin/inexistant/hot'));
    $event = Event::factory()->for(User::factory())->create();
    $guest = Guest::factory()->for($event)->create();

    $this->get("/i/{$guest->token}/billet")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Invitations/Ticket')
        ->where('offlineAssets', [
            '/build/assets/app-abc.js',
            '/build/assets/app-abc.css',
            '/build/assets/runtime-111.js',
            '/build/assets/Ticket-def.js',
            '/build/assets/EventPublicLayout-222.js',
            '/build/assets/QrCode-333.js',
            '/fonts/dm-sans-normal-latin.woff2',
            '/fonts/cormorant-garamond-normal-latin.woff2',
            '/fonts/cormorant-garamond-italic-latin.woff2',
            '/fonts/pinyon-script-latin.woff2',
        ]));
});

test('les polices annoncées au billet existent dans public/fonts', function () {
    $event = Event::factory()->for(User::factory())->create();
    $guest = Guest::factory()->for($event)->create();

    $fonts = collect($this->get("/i/{$guest->token}/billet")->viewData('page')['props']['offlineAssets'])->filter(fn ($path) => str_starts_with($path, '/fonts/'));

    expect($fonts)->not->toBeEmpty();
    $fonts->each(fn ($path) => expect(is_file(public_path($path)))->toBeTrue());
});
