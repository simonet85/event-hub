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
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
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
}
