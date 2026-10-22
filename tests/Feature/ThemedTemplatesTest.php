<?php

use App\Mail\InvitationMail;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Support\Color;

beforeEach(function () {
    $this->host = User::factory()->create();
});

test('l\'e-mail d\'invitation prend les couleurs du thème de l\'événement', function () {
    $event = Event::factory()->for($this->host)->create(['type' => 'baptism', 'theme_config' => []]);
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml('#42698f');
    $mail->assertSeeInHtml($event->title);
    $mail->assertSeeInHtml($guest->token);
});

test('une couleur choisie par l\'hôte remplace celle du préréglage dans l\'e-mail', function () {
    $event = Event::factory()->for($this->host)->create(['type' => 'wedding', 'theme_config' => ['primary_color' => '#a0432a', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif']]);
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml('#a0432a');
    $mail->assertDontSeeInHtml('#4e6b48');
});

test('le texte du bouton reste lisible : clair sur un accent sombre, foncé sur un accent clair', function () {
    $dark = Event::factory()->for($this->host)->create(['theme_config' => ['primary_color' => '#1d3b2a', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif']]);
    $light = Event::factory()->for($this->host)->create(['theme_config' => ['primary_color' => '#f0d78c', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif']]);

    (new InvitationMail(Guest::factory()->for($dark)->create()))->assertSeeInHtml('color:'.Color::ON_ACCENT_LIGHT);
    (new InvitationMail(Guest::factory()->for($light)->create()))->assertSeeInHtml('color:'.Color::ON_ACCENT_DARK);
});

test('un accent illisible sur le fond n\'est pas utilisé comme couleur de texte', function () {
    expect(Color::readableAccent('#f0d78c', '#fbf6ee'))->toBe(Color::INK)
        ->and(Color::readableAccent('#4e6b48', '#fbf6ee'))->toBe('#4e6b48');
});

test('une valeur de thème invalide ne casse pas l\'e-mail', function () {
    $event = Event::factory()->for($this->host)->create(['theme_config' => ['primary_color' => 'rouge', 'secondary_color' => '', 'font_family' => 'serif']]);
    $guest = Guest::factory()->for($event)->create();

    (new InvitationMail($guest))->assertSeeInHtml('Voir mon invitation');
});

test('l\'e-mail a aussi une version texte avec le lien', function () {
    $event = Event::factory()->for($this->host)->create();
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInText($guest->invitationUrl());
    $mail->assertSeeInText($event->title);
});

test('les contrastes calculés suivent la norme WCAG', function () {
    expect(round(Color::contrast('#000000', '#ffffff'), 1))->toBe(21.0)
        ->and(Color::contrast('#777777', '#777777'))->toBe(1.0)
        ->and(Color::onAccent('#7a3d6f'))->toBe(Color::ON_ACCENT_LIGHT)
        ->and(Color::onAccent('#e8dcc8'))->toBe(Color::ON_ACCENT_DARK);
});

test('le thème résolu combine préréglage et choix de l\'hôte', function () {
    $event = Event::factory()->for($this->host)->create(['type' => 'party', 'theme_config' => ['primary_color' => '#112233']]);

    expect($event->resolvedTheme())->toBe(['primary_color' => '#112233', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif']);
});

test('les PDF reprennent la couleur du thème, ont un pied de page et des marges d\'impression', function () {
    $event = Event::factory()->for($this->host)->create(['type' => 'birthday', 'theme_config' => []]);

    $html = view('pdf.caterer', [
        'event' => $event,
        'rsvp' => ['headcount' => 0, 'accepted' => 0, 'pending' => 0, 'declined' => 0],
        'dietary' => collect(),
        'dietarySeats' => 0,
    ])->render();

    expect($html)->toContain('#a0432a')->toContain('@page')->toContain('counter(page)')->toContain('class="footer"');
});

test('les PDF restent générés pour l\'hôte', function () {
    $event = Event::factory()->for($this->host)->create(['slug' => 'pdf-theme']);

    $this->actingAs($this->host)->get('/events/pdf-theme/exports/traiteur.pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($this->host)->get('/events/pdf-theme/exports/plan-de-table.pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('l\'e-mail affiche le lieu, l\'adresse, le dress code et le mot d\'accueil quand ils existent', function () {
    $event = Event::factory()->for($this->host)->create([
        'location_name' => 'Mairie du Plateau', 'location_address' => '1 avenue Franchet, Abidjan',
        'dress_code' => 'Tenue élégante', 'welcome_message' => 'Nous avons hâte de vous voir.',
    ]);
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml('Mairie du Plateau');
    $mail->assertSeeInHtml('1 avenue Franchet, Abidjan');
    $mail->assertSeeInHtml('Tenue élégante');
    $mail->assertSeeInHtml('Nous avons hâte de vous voir.');
    $mail->assertSeeInText('Tenue élégante');
    $mail->assertSeeInText('Nous avons hâte de vous voir.');
});

test('l\'e-mail reste correct sans lieu, dress code ni mot d\'accueil', function () {
    $event = Event::factory()->for($this->host)->create(['location_name' => null, 'location_address' => null, 'dress_code' => null, 'welcome_message' => null]);
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml('Voir mon invitation');
    $mail->assertDontSeeInHtml('Tenue souhaitée');
    $mail->assertDontSeeInHtml('>Lieu<');
});

test('l\'e-mail est envoyé avec l\'objet et le destinataire attendus', function () {
    Mail::fake();
    $event = Event::factory()->for($this->host)->create();
    $guest = Guest::factory()->for($event)->create(['email' => 'invite@example.test']);

    Mail::to($guest->email)->send(new InvitationMail($guest));

    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail) => $mail->hasTo('invite@example.test')
        && $mail->envelope()->subject === "Vous êtes invité(e) : {$event->title}");
});

test('l\'adresse géocodée ne répète pas le nom du lieu dans l\'e-mail', function () {
    $event = Event::factory()->for($this->host)->create(['location_name' => 'Mairie du Plateau', 'location_address' => 'Mairie du Plateau, Avenue Franchet, Abidjan']);
    $guest = Guest::factory()->for($event)->create();

    $mail = new InvitationMail($guest);

    $mail->assertSeeInHtml('Avenue Franchet, Abidjan');
    $mail->assertDontSeeInHtml('Mairie du Plateau, Avenue Franchet');
    $mail->assertSeeInText('(Avenue Franchet, Abidjan)');
});
