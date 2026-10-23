<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create();
    $this->guest = Guest::factory()->for($this->event)->create();
});

/** Photo de la galerie dont les deux fichiers existent sur le disque. */
function galleryPhoto(Event $event): Photo
{
    ob_start();
    imagejpeg(imagecreatetruecolor(60, 40));
    $bytes = (string) ob_get_clean();
    $photo = Photo::factory()->gallery()->for($event)->create();
    Storage::disk('local')->put($photo->path, $bytes);
    Storage::disk('local')->put($photo->thumb_path, $bytes);

    return $photo;
}

// --- Envoi par l'hôte -------------------------------------------------------------------------------------------

test('l\'hôte envoie des photos : réduites, avec miniature, publiées d\'emblée et sans invité auteur', function () {
    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/gallery", ['photos' => [
            UploadedFile::fake()->image('grande.jpg', 2600, 1300),
            UploadedFile::fake()->image('portrait.png', 1000, 1500),
        ]])
        ->assertRedirect()->assertSessionHas('gallery_added', 2);

    $photos = $this->event->galleryPhotos()->get();
    expect($photos)->toHaveCount(2)
        ->and($photos->every(fn (Photo $p) => $p->guest_id === null && $p->status->value === 'approved'))->toBeTrue();

    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($photos[0]->path));
    expect([$width, $height])->toBe([2400, 1200]);

    [$thumbWidth] = getimagesizefromstring(Storage::disk('local')->get($photos[0]->thumb_path));
    expect($thumbWidth)->toBe(480);

    // Une petite photo n'est jamais agrandie, et tout est ré-encodé en JPEG.
    [$w, $h, $type] = getimagesizefromstring(Storage::disk('local')->get($photos[1]->path));
    expect([$w, $h, $type])->toBe([1000, 1500, IMAGETYPE_JPEG]);
});

test('seules les images de taille raisonnable sont acceptées, vingt par envoi', function () {
    $post = fn (array $files) => $this->actingAs($this->host)->post("/events/{$this->event->slug}/gallery", ['photos' => $files]);

    $post([UploadedFile::fake()->create('script.php', 10, 'application/x-php')])->assertSessionHasErrors('photos.0');
    $post([UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('photos.0');
    $post([UploadedFile::fake()->image('enorme.jpg')->size(16000)])->assertSessionHasErrors('photos.0');
    $post([UploadedFile::fake()->image('trop-large.jpg', 7000, 100)])->assertSessionHasErrors('photos.0');
    $post(array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg", 20, 20), range(1, 21)))->assertSessionHasErrors('photos');
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/gallery", [])->assertSessionHasErrors('photos');

    expect(Photo::count())->toBe(0);
});

test('la galerie est limitée à 500 photos', function () {
    Photo::factory()->gallery()->for($this->event)->count(499)->create();

    $this->actingAs($this->host)
        ->post("/events/{$this->event->slug}/gallery", ['photos' => [UploadedFile::fake()->image('a.jpg', 20, 20), UploadedFile::fake()->image('b.jpg', 20, 20)]])
        ->assertSessionHasErrors('photos');

    expect($this->event->galleryPhotos()->count())->toBe(499);
});

test('seul l\'hôte de l\'événement gère sa galerie', function () {
    $file = fn () => ['photos' => [UploadedFile::fake()->image('a.jpg', 20, 20)]];
    $photo = galleryPhoto($this->event);
    $intruder = User::factory()->create();

    $this->post("/events/{$this->event->slug}/gallery", $file())->assertRedirect('/login');
    $this->actingAs($intruder)->get("/events/{$this->event->slug}/gallery")->assertForbidden();
    $this->actingAs($intruder)->post("/events/{$this->event->slug}/gallery", $file())->assertForbidden();
    $this->actingAs($intruder)->delete("/events/{$this->event->slug}/gallery/{$photo->id}")->assertForbidden();

    expect($this->event->galleryPhotos()->count())->toBe(1);
});

test('la page galerie de l\'hôte liste ses photos', function () {
    galleryPhoto($this->event);
    galleryPhoto($this->event);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/gallery")->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Gallery')->has('photos', 2)->where('limits.per_event', 500));
});

test('l\'hôte supprime une photo : les deux fichiers disparaissent', function () {
    $photo = galleryPhoto($this->event);

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/gallery/{$photo->id}")->assertRedirect();

    expect(Photo::count())->toBe(0);
    Storage::disk('local')->assertMissing($photo->path);
    Storage::disk('local')->assertMissing($photo->thumb_path);
});

test('on ne supprime pas la photo d\'un autre événement ni celle d\'un invité par la galerie', function () {
    $foreign = galleryPhoto(Event::factory()->create());
    $byGuest = Photo::factory()->for($this->guest)->create();

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/gallery/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/gallery/{$byGuest->id}")->assertNotFound();
});

test('l\'hôte voit ses photos, miniature comprise, mais pas un autre utilisateur', function () {
    $photo = galleryPhoto($this->event);

    $this->get("/photos/{$photo->id}")->assertNotFound();
    $this->actingAs(User::factory()->create())->get("/photos/{$photo->id}?taille=mini")->assertNotFound();
    $this->actingAs($this->host)->get("/photos/{$photo->id}?taille=mini")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

// --- Côté invité ------------------------------------------------------------------------------------------------

test('l\'invitation expose la galerie de l\'hôte, jamais les anciennes photos d\'invités', function () {
    galleryPhoto($this->event);
    galleryPhoto($this->event);
    Photo::factory()->for($this->guest)->create(['status' => 'approved']);

    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page
        ->has('gallery', 2)
        ->where('gallery.0.thumb', fn ($url) => str_contains($url, "/i/{$this->guest->token}/galerie/") && str_contains($url, 'taille=mini'))
        ->has('gallery.0.download'));
});

test('un invité voit une photo de la galerie avec son seul lien d\'invitation', function () {
    $photo = galleryPhoto($this->event);

    $this->get("/i/{$this->guest->token}/galerie/{$photo->id}")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get("/i/{$this->guest->token}/galerie/{$photo->id}?taille=mini")->assertOk();
    $this->get('/i/'.str_repeat('q', 40)."/galerie/{$photo->id}")->assertNotFound();
});

test('un invité ne voit ni la photo d\'un autre événement ni une photo d\'invité', function () {
    $foreign = galleryPhoto(Event::factory()->create());
    $byGuest = Photo::factory()->for($this->guest)->create(['status' => 'approved']);

    $this->get("/i/{$this->guest->token}/galerie/{$foreign->id}")->assertNotFound();
    $this->get("/i/{$this->guest->token}/galerie/{$byGuest->id}")->assertNotFound();
    $this->get("/i/{$this->guest->token}/galerie/{$foreign->id}/telecharger")->assertNotFound();
});

test('la galerie d\'un événement archivé n\'est plus accessible', function () {
    $photo = galleryPhoto($this->event);
    $this->event->forceFill(['archived_at' => now()])->save();

    $this->get("/i/{$this->guest->token}/galerie/{$photo->id}")->assertNotFound();
    $this->get("/i/{$this->guest->token}/galerie.zip")->assertNotFound();
});

test('un invité télécharge une photo en pièce jointe', function () {
    $photo = galleryPhoto($this->event);

    $this->get("/i/{$this->guest->token}/galerie/{$photo->id}/telecharger")->assertOk()
        ->assertDownload("{$this->event->slug}-{$photo->id}.jpg");
});

/** Noms des fichiers d'une archive renvoyée par la galerie. */
function zipEntries($response): array
{
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
    $zip->close();

    return $names;
}

test('un invité télécharge toute la galerie en .zip', function () {
    galleryPhoto($this->event);
    galleryPhoto($this->event);
    galleryPhoto($this->event);
    galleryPhoto(Event::factory()->create());

    $response = $this->get("/i/{$this->guest->token}/galerie.zip")->assertOk()->assertHeader('Content-Type', 'application/zip');
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store')->not->toContain('public');

    expect(zipEntries($response))->toBe(["{$this->event->slug}-001.jpg", "{$this->event->slug}-002.jpg", "{$this->event->slug}-003.jpg"]);
});

test('un invité télécharge une sélection, limitée à sa propre galerie', function () {
    $a = galleryPhoto($this->event);
    galleryPhoto($this->event);
    $c = galleryPhoto($this->event);
    $foreign = galleryPhoto(Event::factory()->create());

    $response = $this->get("/i/{$this->guest->token}/galerie.zip?".http_build_query(['ids' => [$a->id, $c->id, $foreign->id]]))->assertOk();

    expect(zipEntries($response))->toHaveCount(2);
});

test('une archive sans photo ou avec une sélection invalide est refusée', function () {
    $this->get("/i/{$this->guest->token}/galerie.zip")->assertNotFound();

    galleryPhoto($this->event);
    $this->get("/i/{$this->guest->token}/galerie.zip?ids[]=abc")->assertSessionHasErrors('ids.0');
    $this->get("/i/{$this->guest->token}/galerie.zip?ids[]=999999")->assertNotFound();
});
