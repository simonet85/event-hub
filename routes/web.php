<?php

use App\Http\Controllers\CagnotteController;
use App\Http\Controllers\CatererLinkController;
use App\Http\Controllers\CatererViewController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestDataController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\MoneyFusionWebhookController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShareImageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Point d'entrée après connexion : l'espace hôte est la liste des événements.
Route::get('/dashboard', fn () => redirect()->route('events.index'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Un événement archivé n'est plus visible des invités (404) ; le retour de paiement reste accessible.
Route::middleware(['event.active', 'noindex'])->group(function () {
    Route::get('/e/{event}', [EventController::class, 'showPublic'])->name('events.public');

    Route::get('/i/{token}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::get('/i/{guest:token}/billet', [InvitationController::class, 'ticket'])->middleware('cache.headers:private;no_store')->name('invitations.ticket');
    Route::get('/i/{guest:token}/billet/manifest.webmanifest', [InvitationController::class, 'manifest'])->name('invitations.ticket.manifest');
    Route::post('/i/{guest:token}/rsvp', [RsvpController::class, 'store'])->middleware('throttle:20,1')->name('rsvp.store');
    Route::post('/i/{guest:token}/guestbook', [ContributionController::class, 'storeEntry'])->middleware('throttle:guest-contributions')->name('guestbook.store');
    Route::delete('/i/{guest:token}/data', [GuestDataController::class, 'erase'])->middleware('throttle:10,1')->name('guest-data.erase');
    Route::post('/i/{guest:token}/donations', [DonationController::class, 'store'])->middleware('throttle:10,1')->name('donations.store');
});

// Vue traiteur en lecture seule : accessible avec le seul lien, jusqu'à son expiration. Jamais mise en cache ni indexée.
Route::get('/traiteur/{token}', [CatererViewController::class, 'show'])
    ->middleware(['throttle:60,1', 'noindex', 'cache.headers:private;no_store'])
    ->name('caterer.show');

// Image de partage : sans donnée d'invité, indexable pour les aperçus de liens ; masquée dès que l'événement est archivé.
Route::get('/e/{event}/share.png', [ShareImageController::class, 'show'])->middleware('event.active')->name('events.share-image');
Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
Route::get('/i/{guest:token}/donations/{contribution}/return', [DonationController::class, 'return'])->name('donations.return');
Route::post('/webhooks/moneyfusion', MoneyFusionWebhookController::class)->middleware('throttle:120,1')->name('webhooks.moneyfusion');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/events/{event}/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/events/{event}/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/events/{event}/schedule/{item}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    Route::patch('/events/{event}/details', [ScheduleController::class, 'updateDetails'])->name('events.details');
    Route::get('/events/{event}/dashboard', [DashboardController::class, 'show'])->name('dashboard.show');
    Route::delete('/events/{event}/guest-data', [GuestDataController::class, 'purge'])->name('guest-data.purge');
    Route::post('/events/{event}/caterer-link', [CatererLinkController::class, 'store'])->name('caterer-link.store');
    Route::delete('/events/{event}/caterer-link', [CatererLinkController::class, 'destroy'])->name('caterer-link.destroy');
    Route::get('/events/{event}/exports/guests.xlsx', [ExportController::class, 'xlsx'])->name('exports.xlsx');
    Route::get('/events/{event}/exports/guests.csv', [ExportController::class, 'csv'])->name('exports.csv');
    Route::get('/events/{event}/exports/traiteur.pdf', [ExportController::class, 'caterer'])->name('exports.caterer');
    Route::get('/events/{event}/exports/plan-de-table.pdf', [ExportController::class, 'seating'])->name('exports.seating');
    Route::patch('/events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
    Route::get('/events/{event}/cagnotte', [CagnotteController::class, 'index'])->name('cagnotte.index');
    Route::patch('/events/{event}/cagnotte', [CagnotteController::class, 'updateSettings'])->name('cagnotte.settings');
    Route::post('/events/{event}/gifts', [CagnotteController::class, 'storeGift'])->name('gifts.store');
    Route::delete('/events/{event}/gifts/{gift}', [CagnotteController::class, 'destroyGift'])->name('gifts.destroy');
    Route::get('/events/{event}/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::patch('/events/{event}/guestbook/{entry}', [ModerationController::class, 'updateEntry'])->name('moderation.entries.update');
    Route::delete('/events/{event}/guestbook/{entry}', [ModerationController::class, 'destroyEntry'])->name('moderation.entries.destroy');
    Route::get('/events/{event}/guests', [GuestController::class, 'index'])->name('guests.index');
    Route::post('/events/{event}/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::post('/events/{event}/guests/import', [GuestController::class, 'import'])->name('guests.import');
    Route::delete('/events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('/events/{event}/invitations', [InvitationController::class, 'sendAll'])->name('invitations.send-all');
    Route::post('/events/{event}/guests/{guest}/invitation', [InvitationController::class, 'send'])->name('invitations.send');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::patch('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::post('/events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
    Route::post('/events/{event}/restore', [EventController::class, 'restore'])->name('events.restore');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
