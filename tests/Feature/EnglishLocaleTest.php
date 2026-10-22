<?php

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create(['slug' => 'english-event', 'type' => 'wedding', 'cagnotte_enabled' => true]);
    $this->guest = Guest::factory()->for($this->event)->create();
});

test('la page d\'invitation partage les traductions anglaises et le libellé du type en anglais', function () {
    $this->withSession(['locale' => 'en'])->get("/i/{$this->guest->token}")->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'en')
        ->where('event.type_label', 'Wedding')
        ->where('translations.Confirmer ma présence', 'Confirm my attendance'));
});

test('en français, le libellé reste français et aucune traduction n\'est partagée', function () {
    $this->withSession(['locale' => 'fr'])->get("/i/{$this->guest->token}")->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'fr')
        ->where('event.type_label', 'Mariage')
        ->where('translations', []));
});

test('les messages de validation et d\'erreur suivent la langue', function () {
    $this->withSession(['locale' => 'en'])->post("/i/{$this->guest->token}/guestbook", ['message' => 'Visit https://spam.example'])
        ->assertSessionHasErrors(['message' => 'Links are not permitted in messages.']);

    $this->withSession(['locale' => 'fr'])->post("/i/{$this->guest->token}/guestbook", ['message' => 'Visitez https://spam.example'])
        ->assertSessionHasErrors(['message' => 'Les liens ne sont pas autorisés dans les messages.']);
});

test('les limites par invité s\'affichent en anglais', function () {
    $this->withSession(['locale' => 'en'])->post("/i/{$this->guest->token}/donations", ['amount' => 10])
        ->assertSessionHasErrors(['amount' => 'The minimum amount is 250 FCFA.']);
});

test('les statuts de contribution envoyés à l\'hôte sont traduits', function () {
    Contribution::factory()->for($this->guest)->confirmed()->create(['amount' => 5000]);

    $this->actingAs($this->host)->withSession(['locale' => 'en'])->get("/events/{$this->event->slug}/cagnotte")
        ->assertInertia(fn (Assert $page) => $page->where('contributions.data.0.status_label', 'Confirmed'));
});

test('chaque libellé dynamique envoyé par le serveur a une traduction anglaise', function () {
    $english = json_decode(file_get_contents(lang_path('en.json')), true);

    foreach (['Mariage', 'Baptême', 'Anniversaire', 'Fête', 'Autre', 'En attente', 'Confirmée', 'Échouée', 'Expirée', 'À vérifier', 'Présent(e)', 'Absent(e)'] as $label) {
        expect($english)->toHaveKey($label);
    }
});

test("les exports restent en français quelle que soit la langue de l'hôte", function () {
    $response = $this->actingAs($this->host)->withSession(['locale' => 'en'])->get("/events/{$this->event->slug}/exports/guests.csv");

    $response->assertOk();
    expect($response->streamedContent())->toContain('Nom')->toContain('Régime alimentaire');
});
