<?php

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

const MF_CREATE = 'https://www.pay.moneyfusion.net/*';
const MF_CHECK = 'https://pay.moneyfusion.net/paiementNotif/*';

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create(['cagnotte_enabled' => true]);
    $this->guest = Guest::factory()->for($this->event)->create(['name' => 'Alice', 'phone' => '+225 07 07 07 07 07']);
});

function fakeCreate(string $token = 'tok_1'): void
{
    Http::fake([MF_CREATE => Http::response([
        'statut' => true, 'token' => $token, 'message' => 'paiement en cours',
        'url' => "https://payin.moneyfusion.net/payment/{$token}/Marchand",
    ], 200)]);
}

function fakeCheck(string $statut, ?int $montant = null, string $token = 'tok_1'): void
{
    Http::fake([MF_CHECK => Http::response([
        'statut' => true,
        'data' => array_filter(['tokenPay' => $token, 'statut' => $statut, 'Montant' => $montant, 'frais' => 5], fn ($v) => $v !== null),
        'message' => 'details paiement',
    ], 200)]);
}

function pendingContribution(Guest $guest, int $amount = 5000, string $token = 'tok_1', array $attrs = []): Contribution
{
    return Contribution::factory()->for($guest)->create(array_merge(['amount' => $amount, 'payment_token' => $token], $attrs));
}

// ───────────── Hôte : réglages et cadeaux ─────────────

test('l\'hôte active la cagnotte et fixe un objectif', function () {
    $this->actingAs($this->host)
        ->patch("/events/{$this->event->slug}/cagnotte", ['cagnotte_enabled' => false, 'cagnotte_goal' => 250000, 'cagnotte_message' => 'Merci !'])
        ->assertSessionHas('cagnotte_saved', true);

    $event = $this->event->fresh();
    expect($event->cagnotte_enabled)->toBeFalse()->and($event->cagnotte_goal)->toBe(250000)->and($event->cagnotte_message)->toBe('Merci !');
});

test('les réglages de la cagnotte sont validés', function () {
    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/cagnotte", ['cagnotte_enabled' => 'peut-etre', 'cagnotte_goal' => 5])
        ->assertSessionHasErrors(['cagnotte_enabled', 'cagnotte_goal']);
});

test('l\'hôte gère la liste de cadeaux', function () {
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/gifts", ['name' => 'Voyage de noces', 'target_amount' => 500000])->assertRedirect();
    $gift = $this->event->gifts()->first();
    expect($gift->name)->toBe('Voyage de noces')->and($gift->target_amount)->toBe(500000);

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/gifts/{$gift->id}")->assertRedirect();
    expect(Gift::count())->toBe(0);
});

test('un cadeau exige un nom et un montant valide', function () {
    $this->actingAs($this->host)->post("/events/{$this->event->slug}/gifts", ['name' => '', 'target_amount' => 10])
        ->assertSessionHasErrors(['name', 'target_amount']);
});

test('on ne peut pas supprimer le cadeau d\'un autre événement', function () {
    $foreign = Gift::factory()->create();

    $this->actingAs($this->host)->delete("/events/{$this->event->slug}/gifts/{$foreign->id}")->assertNotFound();
    expect(Gift::find($foreign->id))->not->toBeNull();
});

test('un autre utilisateur ne peut pas gérer la cagnotte', function () {
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get("/events/{$this->event->slug}/cagnotte")->assertForbidden();
    $this->actingAs($intruder)->patch("/events/{$this->event->slug}/cagnotte", ['cagnotte_enabled' => false])->assertForbidden();
    $this->actingAs($intruder)->post("/events/{$this->event->slug}/gifts", ['name' => 'X', 'target_amount' => 1000])->assertForbidden();
});

test('la page hôte ne compte que les contributions confirmées', function () {
    Contribution::factory()->for($this->guest)->confirmed()->create(['amount' => 10000]);
    Contribution::factory()->for($this->guest)->create(['amount' => 99999, 'status' => 'pending']);
    Contribution::factory()->for($this->guest)->create(['amount' => 77777, 'status' => 'mismatch']);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/cagnotte")->assertInertia(fn ($page) => $page
        ->component('Events/Cagnotte')
        ->where('stats.raised', 10000)->where('stats.confirmed', 1)->where('stats.pending', 1)->where('stats.to_review', 1)
        ->has('contributions.data', 3));
});

// ───────────── Invité : ce qu'il voit ─────────────

test('sans cagnotte activée, l\'invitation n\'expose rien', function () {
    $this->event->update(['cagnotte_enabled' => false]);

    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page->where('cagnotte', null));
});

test('l\'invitation expose objectif, total confirmé et cadeaux sans les montants des autres invités', function () {
    $this->event->update(['cagnotte_goal' => 100000]);
    $gift = Gift::factory()->for($this->event)->create(['target_amount' => 50000]);
    $other = Guest::factory()->for($this->event)->create();
    Contribution::factory()->for($other)->confirmed()->create(['amount' => 20000, 'gift_id' => $gift->id]);
    Contribution::factory()->for($other)->create(['amount' => 30000, 'gift_id' => $gift->id, 'status' => 'pending']);
    Contribution::factory()->for($this->guest)->create(['amount' => 1500]);

    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page
        ->where('cagnotte.goal', 100000)
        ->where('cagnotte.raised', 20000)
        ->where('cagnotte.gifts.0.raised', 20000)
        ->where('cagnotte.gifts.0.remaining', 30000)
        ->has('cagnotte.mine', 1)->where('cagnotte.mine.0.amount', 1500));
});

// ───────────── Invité : démarrer un paiement ─────────────

test('une contribution crée un paiement MoneyFusion et redirige vers sa page de paiement', function () {
    fakeCreate();

    $response = $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
        ->post("/i/{$this->guest->token}/donations", ['amount' => 5000, 'message' => 'Bravo']);

    $response->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://payin.moneyfusion.net/payment/tok_1/Marchand');

    $contribution = Contribution::first();
    expect($contribution->status)->toBe(ContributionStatus::Pending)
        ->and($contribution->amount)->toBe(5000)
        ->and($contribution->payment_token)->toBe('tok_1')
        ->and($contribution->guest_id)->toBe($this->guest->id)
        ->and($contribution->event_id)->toBe($this->event->id);

    Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://www.pay.moneyfusion.net/')
        && $r['totalPrice'] === '5000'
        && $r['nomclient'] === 'Alice'
        && $r['numeroSend'] === '2250707070707'
        && $r['personal_Info'][0]['orderId'] === $contribution->id
        && $r['return_url'] === route('donations.return', [$this->guest->token, $contribution])
        && $r['webhook_url'] === route('webhooks.moneyfusion'));
});

test('une contribution à un cadeau est rattachée à ce cadeau', function () {
    fakeCreate();
    $gift = Gift::factory()->for($this->event)->create(['name' => 'Lune de miel', 'target_amount' => 50000]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 10000, 'gift_id' => $gift->id]);

    expect(Contribution::first()->gift_id)->toBe($gift->id);
    Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://www.pay.moneyfusion.net/') && $r['article'][0]['nom'] === 'Lune de miel');
});

test('le montant est validé', function (mixed $amount) {
    Http::fake();

    $this->post("/i/{$this->guest->token}/donations", ['amount' => $amount])->assertSessionHasErrors('amount');

    expect(Contribution::count())->toBe(0);
    Http::assertNothingSent();
})->with([[50], [0], [-1000], ['abc'], [12.5], [5_000_001], [null]]);

test('un cadeau d\'un autre événement est refusé', function () {
    Http::fake();
    $foreign = Gift::factory()->create();

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 1000, 'gift_id' => $foreign->id])->assertSessionHasErrors('gift_id');
    Http::assertNothingSent();
});

test('on ne peut pas dépasser ce qu\'il reste à financer pour un cadeau', function () {
    Http::fake();
    $gift = Gift::factory()->for($this->event)->create(['target_amount' => 10000]);
    Contribution::factory()->for($this->guest)->confirmed()->create(['amount' => 8000, 'gift_id' => $gift->id]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 3000, 'gift_id' => $gift->id])->assertSessionHasErrors('amount');
    Http::assertNothingSent();
});

test('la cagnotte désactivée refuse les contributions', function () {
    Http::fake();
    $this->event->update(['cagnotte_enabled' => false]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 5000])->assertForbidden();
    Http::assertNothingSent();
});

test('un jeton inconnu ne peut pas contribuer', function () {
    $this->post('/i/'.str_repeat('z', 40).'/donations', ['amount' => 5000])->assertNotFound();
});

test('si MoneyFusion refuse le paiement, la contribution échoue proprement', function () {
    Http::fake([MF_CREATE => Http::response(['statut' => false, 'message' => 'IP non autorisée'], 200)]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 5000])->assertSessionHasErrors('amount');

    expect(Contribution::first()->status)->toBe(ContributionStatus::Failed);
});

test('si MoneyFusion est injoignable, la contribution échoue proprement', function () {
    Http::fake([MF_CREATE => Http::response('boom', 500)]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 5000])->assertSessionHasErrors('amount');

    expect(Contribution::first()->status)->toBe(ContributionStatus::Failed);
});

test('le nombre de paiements en cours par invité est limité', function () {
    Http::fake();
    Contribution::factory()->for($this->guest)->count(3)->create(['status' => 'pending']);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 5000])->assertSessionHasErrors('amount');
    Http::assertNothingSent();
});

// ───────────── Vérification : jamais de crédit sans l'API ─────────────

test('le retour navigateur confirme une contribution payée, montant vérifié', function () {
    $contribution = pendingContribution($this->guest, 5000);
    fakeCheck('paid', 5000);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")
        ->assertRedirect(route('invitations.show', $this->guest->token))
        ->assertSessionHas('donation_status', 'confirmed');

    $contribution->refresh();
    expect($contribution->status)->toBe(ContributionStatus::Confirmed)->and($contribution->confirmed_at)->not->toBeNull();
});

test('le retour navigateur ne confirme rien si l\'API dit en attente', function () {
    $contribution = pendingContribution($this->guest);
    fakeCheck('pending');

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")->assertSessionHas('donation_status', 'pending');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('un paramètre de retour falsifié ne confirme rien', function () {
    $contribution = pendingContribution($this->guest);
    fakeCheck('pending');

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return?status=paid&statut=paid&token=tok_1")
        ->assertSessionHas('donation_status', 'pending');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('un échec de paiement est enregistré', function () {
    $contribution = pendingContribution($this->guest);
    fakeCheck('failure');

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")->assertSessionHas('donation_status', 'failed');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Failed);
});

test('un montant confirmé différent met la contribution à vérifier sans la compter', function () {
    $contribution = pendingContribution($this->guest, 5000);
    fakeCheck('paid', 100);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return");

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Mismatch);
    $this->get("/i/{$this->guest->token}")->assertInertia(fn ($page) => $page->where('cagnotte.raised', 0));
});

test('un statut « payé » issu de la base locale n\'est jamais cru quand l\'API est injoignable', function () {
    $contribution = pendingContribution($this->guest, 5000);
    // Webhook forgé qui aurait marqué le paiement local « payé » :
    MoneyFusionPayment::factory()->create(['token_pay' => 'tok_1', 'montant' => 5000, 'statut' => 'paid', 'paid_at' => now()]);
    Http::fake([MF_CHECK => Http::response('down', 503)]);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")->assertSessionHas('donation_status', 'pending');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('on ne peut pas utiliser le retour d\'une contribution d\'un autre invité', function () {
    $other = Guest::factory()->for($this->event)->create();
    $contribution = pendingContribution($other);
    fakeCheck('paid', 5000);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")->assertNotFound();
    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('une contribution confirmée ne change plus', function () {
    $contribution = Contribution::factory()->for($this->guest)->confirmed()->create(['payment_token' => 'tok_1', 'amount' => 5000]);
    fakeCheck('failure');

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return");

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Confirmed);
});

// ───────────── Webhook : un signal, jamais une preuve ─────────────

test('un webhook falsifié « payé » ne confirme rien quand l\'API dit en attente', function () {
    $contribution = pendingContribution($this->guest);
    fakeCheck('pending');

    $this->postJson('/webhooks/moneyfusion', ['event' => 'payin.session.completed', 'tokenPay' => 'tok_1', 'Montant' => 5000, 'statut' => 'paid'])->assertOk();

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('un webhook réel confirme la contribution après vérification auprès de l\'API', function () {
    $contribution = pendingContribution($this->guest, 5000);
    fakeCheck('paid', 5000);

    $this->postJson('/webhooks/moneyfusion', ['event' => 'payin.session.completed', 'tokenPay' => 'tok_1'])->assertOk();

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Confirmed);
    Http::assertSent(fn ($r) => $r->url() === 'https://pay.moneyfusion.net/paiementNotif/tok_1');
});

test('un webhook sans jeton ou avec un jeton inconnu est refusé', function () {
    $this->postJson('/webhooks/moneyfusion', ['event' => 'payin.session.completed'])->assertStatus(400);
    $this->postJson('/webhooks/moneyfusion', ['tokenPay' => 'inconnu'])->assertNotFound();
});

test('un webhook en double ne reconfirme pas et n\'appelle pas l\'API', function () {
    Contribution::factory()->for($this->guest)->confirmed()->create(['payment_token' => 'tok_1']);
    Http::fake();

    $this->postJson('/webhooks/moneyfusion', ['tokenPay' => 'tok_1'])->assertOk();

    Http::assertNothingSent();
});

// ───────────── Réconciliation ─────────────

test('la réconciliation confirme un paiement dont le webhook a été perdu', function () {
    $contribution = pendingContribution($this->guest, 5000);
    fakeCheck('paid', 5000);

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Confirmed);
});

test('la réconciliation fait expirer une contribution jamais payée après 48 h', function () {
    $old = pendingContribution($this->guest, 5000, 'tok_old', ['created_at' => now()->subHours(49)]);
    fakeCheck('pending', null, 'tok_old');

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect($old->fresh()->status)->toBe(ContributionStatus::Expired);
});

test('un paiement tardif est confirmé plutôt qu\'expiré', function () {
    $old = pendingContribution($this->guest, 5000, 'tok_old', ['created_at' => now()->subHours(60)]);
    fakeCheck('paid', 5000, 'tok_old');

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect($old->fresh()->status)->toBe(ContributionStatus::Confirmed);
});

test('si l\'API est injoignable, rien n\'expire ni ne se confirme', function () {
    $old = pendingContribution($this->guest, 5000, 'tok_old', ['created_at' => now()->subHours(60)]);
    $recent = pendingContribution($this->guest, 5000, 'tok_new');
    Http::fake([MF_CHECK => Http::response('down', 503)]);

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect($old->fresh()->status)->toBe(ContributionStatus::Pending)->and($recent->fresh()->status)->toBe(ContributionStatus::Pending);
});

test('supprimer un événement supprime cadeaux et contributions', function () {
    Gift::factory()->for($this->event)->create();
    Contribution::factory()->for($this->guest)->create();

    $this->event->delete();

    expect(Gift::count())->toBe(0)->and(Contribution::count())->toBe(0);
});

// ───────────── Course sur le budget d'un cadeau ─────────────

test('un paiement en attente réserve sa part : un second don concurrent ne peut pas dépasser la cible', function () {
    fakeCreate();
    $gift = Gift::factory()->for($this->event)->create(['target_amount' => 10000]);
    $other = Guest::factory()->for($this->event)->create();
    Contribution::factory()->for($other)->create(['amount' => 8000, 'gift_id' => $gift->id, 'status' => 'pending']);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 3000, 'gift_id' => $gift->id])->assertSessionHasErrors('amount');
    expect($this->guest->contributions()->count())->toBe(0);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 2000, 'gift_id' => $gift->id])->assertSessionHasNoErrors();
    expect($this->guest->contributions()->count())->toBe(1);
});

test('un paiement en attente depuis plus d\'une heure ne réserve plus rien', function () {
    fakeCreate();
    $gift = Gift::factory()->for($this->event)->create(['target_amount' => 10000]);
    $other = Guest::factory()->for($this->event)->create();
    Contribution::factory()->for($other)->create(['amount' => 8000, 'gift_id' => $gift->id, 'status' => 'pending', 'created_at' => now()->subHours(2)]);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 9000, 'gift_id' => $gift->id])->assertSessionHasNoErrors();
});

test('un paiement échoué ou expiré ne réserve rien', function () {
    fakeCreate();
    $gift = Gift::factory()->for($this->event)->create(['target_amount' => 10000]);
    $other = Guest::factory()->for($this->event)->create();
    Contribution::factory()->for($other)->create(['amount' => 6000, 'gift_id' => $gift->id, 'status' => 'failed']);
    Contribution::factory()->for($other)->create(['amount' => 4000, 'gift_id' => $gift->id, 'status' => 'expired']);

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 10000, 'gift_id' => $gift->id])->assertSessionHasNoErrors();
});

// ───────────── Réconciliation par lots ─────────────

test('la réconciliation traite aussi les volumes supérieurs à un lot sans rien sauter', function () {
    Contribution::factory()->for($this->guest)->count(230)->sequence(fn ($s) => ['payment_token' => 'tok_'.$s->index, 'amount' => 5000])->create();
    fakeCheck('paid', 5000);

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect(Contribution::where('status', 'confirmed')->count())->toBe(230)->and(Contribution::where('status', 'pending')->count())->toBe(0);
});

test('l\'expiration traite aussi plusieurs lots', function () {
    Contribution::factory()->for($this->guest)->count(230)->sequence(fn ($s) => ['payment_token' => 'old_'.$s->index, 'created_at' => now()->subHours(60)])->create();
    fakeCheck('pending');

    $this->artisan('contributions:reconcile')->assertSuccessful();

    expect(Contribution::where('status', 'expired')->count())->toBe(230);
});

// ───────────── Contraintes MoneyFusion : montant et téléphone ─────────────

test('MoneyFusion refuse 200 F ou moins : le minimum est de 250 F', function () {
    fakeCreate();

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 200])->assertSessionHasErrors('amount');
    $this->post("/i/{$this->guest->token}/donations", ['amount' => 250])->assertSessionHasNoErrors();
});

test('un invité sans téléphone doit indiquer un numéro, transmis à MoneyFusion sans être enregistré', function () {
    fakeCreate();
    $guest = Guest::factory()->for($this->event)->create(['phone' => null]);

    $this->post("/i/{$guest->token}/donations", ['amount' => 500])->assertSessionHasErrors('phone');
    $this->post("/i/{$guest->token}/donations", ['amount' => 500, 'phone' => 'abc'])->assertSessionHasErrors('phone');

    $this->post("/i/{$guest->token}/donations", ['amount' => 500, 'phone' => '+225 01 01 01 01 01'])->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => ($request['numeroSend'] ?? null) === '2250101010101');
    expect($guest->fresh()->phone)->toBeNull();
});

test('un invité qui a déjà un téléphone n\'a pas à le ressaisir', function () {
    fakeCreate();

    $this->post("/i/{$this->guest->token}/donations", ['amount' => 500])->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => ($request['numeroSend'] ?? null) === '2250707070707');
});

test('MoneyFusion renvoie le montant net de ses frais : 250 F payés (242,5 + 7,5 de frais) sont confirmés', function () {
    $contribution = pendingContribution($this->guest, 250);
    Http::fake([MF_CHECK => Http::response([
        'statut' => true,
        'data' => ['tokenPay' => 'tok_1', 'statut' => 'paid', 'Montant' => 242.5, 'frais' => 7.5, 'moyen' => 'wave-ci'],
        'message' => 'details paiement',
    ], 200)]);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return")->assertSessionHas('donation_status', 'confirmed');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Confirmed);
});

test('un montant net + frais inférieur à la contribution reste à vérifier', function () {
    $contribution = pendingContribution($this->guest, 250);
    Http::fake([MF_CHECK => Http::response([
        'statut' => true,
        'data' => ['tokenPay' => 'tok_1', 'statut' => 'paid', 'Montant' => 142.5, 'frais' => 7.5],
        'message' => 'details paiement',
    ], 200)]);

    $this->get("/i/{$this->guest->token}/donations/{$contribution->id}/return");

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Mismatch);
});
