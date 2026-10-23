<?php

namespace App\Http\Controllers;

use App\Enums\ModerationStatus;
use App\Jobs\SendInvitation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\Photo;
use App\Services\ShareImage;
use App\Support\ViteAssets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Invitation personnalisée d'un invité, accessible sans compte via son jeton.
     */
    public function show(string $token): Response
    {
        $guest = Guest::with('event')->where('token', $token)->firstOrFail();
        $event = $guest->event;

        return Inertia::render('Invitations/Show', [
            'token' => $guest->token,
            'guest' => [
                'name' => $guest->name,
                'max_companions' => $guest->max_companions,
                'url' => $guest->invitationUrl(),
                'rsvp_status' => $guest->rsvp_status->value,
                'companions_count' => $guest->companions_count,
                'dietary_restrictions' => $guest->dietary_restrictions,
                'favorite_song' => $guest->favorite_song,
                'rsvp_message' => $guest->rsvp_message,
            ],
            'event' => $event->load('scheduleItems')->publicPayload(),
            'retention_days' => (int) config('privacy.retention_days'),
            'guestbook' => $event->guestbookEntries()->where('status', ModerationStatus::Approved)->with('guest:id,name')->latest()->get()
                ->map(fn (GuestbookEntry $e) => ['id' => $e->id, 'author' => $e->guest->name, 'message' => $e->message]),
            'gallery' => $event->galleryPhotos()->get()->map(fn (Photo $p) => [
                'id' => $p->id,
                'thumb' => route('guest-gallery.show', [$guest, $p, 'taille' => 'mini']),
                'url' => route('guest-gallery.show', [$guest, $p]),
                'download' => route('guest-gallery.download', [$guest, $p]),
            ]),
            'cagnotte' => $event->cagnottePayload($guest),
            'mine' => [
                'pending_entries' => $guest->guestbookEntries()->where('status', ModerationStatus::Pending)->count(),
            ],
            // Aperçu de lien (WhatsApp…) : informations de l'événement seulement, jamais le nom de l'invité.
        ])->withViewData(['meta' => $event->shareMeta()]);
    }

    /**
     * Billet de l'invité, utilisable hors ligne. Il ne porte que les informations de l'invité lui-même et de
     * l'événement (pas de livre d'or, de photos, de paiements ni d'autres invités) : c'est tout ce que le
     * service worker peut garder sur l'appareil.
     */
    /**
     * Polices du billet (nom en manuscrit, titres, texte) : seulement leur sous-ensemble latin, chargé en premier.
     *
     * @var list<string>
     */
    private const OFFLINE_FONTS = [
        '/fonts/dm-sans-normal-latin.woff2',
        '/fonts/cormorant-garamond-normal-latin.woff2',
        '/fonts/cormorant-garamond-italic-latin.woff2',
        '/fonts/pinyon-script-latin.woff2',
    ];

    public function ticket(Guest $guest, ViteAssets $assets): Response
    {
        $event = $guest->event;

        return Inertia::render('Invitations/Ticket', [
            'guest' => ['name' => $guest->name, 'url' => $guest->invitationUrl()],
            'event' => array_merge($event->load('scheduleItems')->publicPayload(), [
                'schedule' => [], 'map' => null, 'map_url' => null, 'dress_code' => null, 'welcome_message' => null,
            ]),
            'ticketPath' => route('invitations.ticket', $guest, absolute: false),
            'manifestUrl' => route('invitations.ticket.manifest', $guest),
            // Exactement ce qu'il faut pour réafficher le billet sans réseau : jamais les autres pages de l'application.
            'offlineAssets' => [
                ...$assets->forEntries(['resources/js/app.ts', 'resources/js/Pages/Invitations/Ticket.vue']),
                ...self::OFFLINE_FONTS,
            ],
        ]);
    }

    /**
     * Manifeste d'application web du billet : permet de l'ajouter à l'écran d'accueil.
     */
    public function manifest(Guest $guest): JsonResponse
    {
        $event = $guest->event;
        $palette = app(ShareImage::class)->palette($event);

        return response()->json([
            'name' => "Billet · {$event->title}",
            'short_name' => 'Billet',
            'lang' => 'fr',
            'start_url' => route('invitations.ticket', $guest, absolute: false),
            'scope' => route('invitations.ticket', $guest, absolute: false),
            'display' => 'standalone',
            'background_color' => $palette['surface'],
            'theme_color' => $palette['accent'],
            'icons' => [
                ['src' => asset('icons/ticket-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => asset('icons/ticket-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /**
     * Envoie par email les invitations pas encore envoyées.
     */
    public function sendAll(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $guests = $event->guests()->whereNotNull('email')->whereNull('invited_at')->get();

        foreach ($guests as $guest) {
            SendInvitation::dispatch($guest);
        }

        return back()->with('sent', $guests->count());
    }

    public function send(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        if (! $guest->email) {
            return back()->withErrors(['email' => __("Cet invité n'a pas d'adresse email.")]);
        }

        SendInvitation::dispatch($guest, resend: true);

        return back()->with('sent', 1);
    }
}
