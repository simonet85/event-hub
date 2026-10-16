<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/e/{event}', [EventController::class, 'showPublic'])->name('events.public');

Route::get('/i/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/i/{guest:token}/rsvp', [RsvpController::class, 'store'])->middleware('throttle:20,1')->name('rsvp.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/events/{event}/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/events/{event}/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/events/{event}/schedule/{item}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    Route::patch('/events/{event}/details', [ScheduleController::class, 'updateDetails'])->name('events.details');
    Route::get('/events/{event}/guests', [GuestController::class, 'index'])->name('guests.index');
    Route::post('/events/{event}/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::post('/events/{event}/guests/import', [GuestController::class, 'import'])->name('guests.import');
    Route::delete('/events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('/events/{event}/invitations', [InvitationController::class, 'sendAll'])->name('invitations.send-all');
    Route::post('/events/{event}/guests/{guest}/invitation', [InvitationController::class, 'send'])->name('invitations.send');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

