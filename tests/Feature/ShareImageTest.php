<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\ShareImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->event = Event::factory()->for(User::factory())->create([
        'title' => 'Mariage de Clara & Thomas',
        'slug' => 'mariage-og',
        'type' => EventType::Wedding,
        'theme_config' => EventType::Wedding->defaultTheme(),
        'location_name' => 'Mairie du Plateau',
        'event_date' => Carbon::create(2026, 12, 12, 16, 0),
    ]);
});

/** Contenu binaire d'une réponse BinaryFileResponse. */
function fileBody($response): string
{
    return file_get_contents($response->baseResponse->getFile()->getPathname());
}

/** Balises <meta> de l'en-tête de la page (hors données Inertia du corps). */
function metaTags(string $html): string
{
    preg_match('#<head>(.*?)</head>#s', $html, $m);
    preg_match_all('#<meta[^>]+>#', $m[1] ?? '', $tags);

    return implode("\n", $tags[0]);
}

test("l'image de partage est un PNG de 1200 × 630", function () {
    $response = $this->get('/e/mariage-og/share.png')->assertOk()->assertHeader('Content-Type', 'image/png');

    [$width, $height, $type] = getimagesizefromstring(fileBody($response));

    expect($width)->toBe(1200)->and($height)->toBe(630)->and($type)->toBe(IMAGETYPE_PNG);
    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=86400');
});

test("l'image est mise en cache puis régénérée quand l'événement change", function () {
    $first = $this->get('/e/mariage-og/share.png');
    $path = $first->baseResponse->getFile()->getPathname();
    $mtime = filemtime($path);
    clearstatcache();

    $this->get('/e/mariage-og/share.png');
    expect(filemtime($path))->toBe($mtime);

    $this->event->update(['title' => 'Nouveau titre']);
    $second = $this->get('/e/mariage-og/share.png');

    expect($second->baseResponse->getFile()->getPathname())->not->toBe($path)
        ->and(file_exists($path))->toBeFalse()
        ->and(count(Storage::disk('local')->files('share-images')))->toBe(1);
});

test('un titre très long, sans lieu, ne fait pas échouer la génération', function () {
    $this->event->update(['title' => str_repeat('Célébration extraordinaire ', 9), 'location_name' => null]);

    $response = $this->get('/e/mariage-og/share.png')->assertOk();

    expect(getimagesizefromstring(fileBody($response)))->not->toBeFalse();
});

test("un événement archivé ou inconnu n'a plus d'image", function () {
    $this->get('/e/inconnu/share.png')->assertNotFound();

    $this->event->forceFill(['archived_at' => now()])->save();
    $this->get('/e/mariage-og/share.png')->assertNotFound();
});

test("les pages publique et d'invitation exposent un aperçu Open Graph sans donnée d'invité", function () {
    $guest = Guest::factory()->for($this->event)->create(['name' => 'Aminata Zéhoré', 'email' => 'aminata@example.test']);

    foreach (['/e/mariage-og', "/i/{$guest->token}"] as $url) {
        $meta = metaTags($this->get($url)->assertOk()->getContent());

        expect($meta)->toContain('property="og:title" content="Mariage de Clara &amp; Thomas"')
            ->toContain('property="og:image" content="'.url('/e/mariage-og/share.png'))
            ->toContain('og:image:width" content="1200"')
            ->toContain('twitter:card" content="summary_large_image"')
            ->toContain('name="robots" content="noindex, nofollow, noarchive"')
            ->toContain('Mairie du Plateau')
            ->not->toContain('Aminata')
            ->not->toContain('Zéhoré')
            ->not->toContain('aminata@example.test')
            ->not->toContain($guest->token);
    }
});

test("l'aperçu décrit l'événement en français", function () {
    $meta = $this->event->shareMeta();

    expect($meta['description'])->toBe('Mariage · Samedi 12 décembre 2026 · 16 h 00 · Mairie du Plateau')
        ->and($meta['noindex'])->toBeTrue();
});

test("les pages d'invitation ne sont pas indexables mais l'image reste lisible par les robots d'aperçu", function () {
    $guest = Guest::factory()->for($this->event)->create();

    $this->get('/e/mariage-og')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $this->get("/i/{$guest->token}")->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $this->get('/e/mariage-og/share.png')->assertHeaderMissing('X-Robots-Tag');
});

test('robots.txt laisse les robots d\'aperçu lire /e/ et /i/ tout en fermant l\'espace hôte', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /events')->toContain('Disallow: /login')
        ->not->toMatch('#^Disallow: /(i|e)/#m');
});

test('la palette suit le thème : préréglage clair, Fête sombre, couleurs de l\'hôte prioritaires', function () {
    $images = app(ShareImage::class);

    expect($images->palette($this->event))->toMatchArray(['surface' => '#fbf6ee', 'ink' => '#2a1f17', 'accent' => '#4e6b48']);

    $party = Event::factory()->for(User::factory())->create(['type' => EventType::Party, 'theme_config' => EventType::Party->defaultTheme()]);
    expect($images->palette($party))->toMatchArray(['surface' => '#1d1711', 'ink' => '#f4ece0', 'accent' => '#d3a3c8']);

    $custom = Event::factory()->for(User::factory())->create(['type' => EventType::Party, 'theme_config' => ['primary_color' => '#112233', 'secondary_color' => '#101010', 'font_family' => 'serif']]);
    $palette = $images->palette($custom);
    expect($palette['surface'])->toBe('#101010')->and($palette['accent'])->toBe('#112233')->and($palette['ink'])->toBe('#f4ece0');
});

test('la date est écrite en français', function () {
    expect(app(ShareImage::class)->dateLine($this->event))->toBe('Samedi 12 décembre 2026 · 16 h 00');
});
