<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => fn () => app()->getLocale(),
            // Le français est la langue source : seules les autres langues ont un fichier JSON.
            'translations' => fn () => $this->translations(),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'event_updated' => fn () => $request->session()->get('event_updated'),
                'event_archived' => fn () => $request->session()->get('event_archived'),
                'event_restored' => fn () => $request->session()->get('event_restored'),
                'erasure_requested' => fn () => $request->session()->get('erasure_requested'),
                'guest_erased' => fn () => $request->session()->get('guest_erased'),
                'gallery_added' => fn () => $request->session()->get('gallery_added'),
                'guest_data_purged' => fn () => $request->session()->get('guest_data_purged'),
                'sent' => fn () => $request->session()->get('sent'),
                'imported' => fn () => $request->session()->get('imported'),
                'details_saved' => fn () => $request->session()->get('details_saved'),
                'contribution_saved' => fn () => $request->session()->get('contribution_saved'),
                'donation_status' => fn () => $request->session()->get('donation_status'),
                'cagnotte_saved' => fn () => $request->session()->get('cagnotte_saved'),
                'rsvp_saved' => fn () => $request->session()->get('rsvp_saved'),
            ],
        ];
    }

    /**
     * Chaînes de l'interface pour la langue courante (lang/{locale}.json).
     *
     * @return array<string, string>
     */
    private function translations(): array
    {
        $path = lang_path(app()->getLocale().'.json');

        return is_file($path) ? json_decode(file_get_contents($path), true) ?? [] : [];
    }
}
