<?php

test('les fichiers d\'icône existent et ne sont pas vides', function () {
    foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $file) {
        expect(filesize(public_path($file)))->toBeGreaterThan(100, $file);
    }
});

test('favicon.ico est un vrai fichier ICO avec les tailles 16, 32 et 48', function () {
    $data = file_get_contents(public_path('favicon.ico'));
    $header = unpack('vreserved/vtype/vcount', $data);

    expect($header)->toBe(['reserved' => 0, 'type' => 1, 'count' => 3]);

    $sizes = [];
    foreach (range(0, 2) as $i) {
        $entry = unpack('Cwidth/Cheight', substr($data, 6 + 16 * $i, 2));
        $sizes[] = $entry['width'];
    }
    expect($sizes)->toBe([16, 32, 48]);

    // Chaque image de l'ICO est un PNG valide.
    $entry = unpack('Vsize/Voffset', substr($data, 6 + 8, 8));
    expect(substr($data, $entry['offset'], 8))->toBe("\x89PNG\r\n\x1a\n");
});

test('l\'icône d\'écran d\'accueil iOS est un PNG de 180 x 180', function () {
    [$width, $height, $type] = getimagesize(public_path('apple-touch-icon.png'));

    expect([$width, $height, $type])->toBe([180, 180, IMAGETYPE_PNG]);
});

test('le SVG est un document valide avec une variante pour le mode sombre', function () {
    $svg = simplexml_load_file(public_path('favicon.svg'));

    expect($svg)->not->toBeFalse();
    expect(file_get_contents(public_path('favicon.svg')))->toContain('prefers-color-scheme: dark');
});

test('chaque page déclare les icônes et la couleur du navigateur', function () {
    $this->get('/')->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico" sizes="48x48">', false)
        ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml" sizes="any">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false)
        ->assertSee('<meta name="theme-color" content="#fbf6ee">', false);
});
