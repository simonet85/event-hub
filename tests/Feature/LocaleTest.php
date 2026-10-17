<?php

use Inertia\Testing\AssertableInertia as Assert;

it('détecte la langue depuis Accept-Language', function () {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en')->where('translations.Connexion', 'Log in'));
});

it('sert le français par défaut sans chaînes à traduire', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr')->where('translations', []));
});

it('retombe sur la langue par défaut quand la langue demandée n\'est pas gérée', function () {
    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
});

it('mémorise le choix explicite de langue, prioritaire sur le navigateur', function () {
    $this->withHeader('Accept-Language', 'en-US')->post(route('locale.update'), ['locale' => 'fr'])->assertRedirect();

    $this->withHeader('Accept-Language', 'en-US')
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
});

it('refuse une langue non gérée', function () {
    $this->post(route('locale.update'), ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

it('traduit les messages de validation', function () {
    $this->withHeader('Accept-Language', 'fr')
        ->post('/login', ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => 'Le champ adresse email est obligatoire.']);
});
