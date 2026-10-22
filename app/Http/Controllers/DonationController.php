<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Exceptions\GiftBudgetExceeded;
use App\Exceptions\PaymentUnavailable;
use App\Http\Requests\DonationRequest;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\Guest;
use App\Services\ContributionPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;

class DonationController extends Controller
{
    /** Contributions non terminées autorisées par invité sur la dernière heure. */
    public const MAX_OPEN_PER_HOUR = 3;

    /**
     * Crée la contribution puis redirige l'invité vers la page de paiement MoneyFusion.
     */
    public function store(DonationRequest $request, Guest $guest, ContributionPayments $payments): Response|RedirectResponse
    {
        $event = $guest->event;

        abort_unless($event->cagnotte_enabled, 403);

        $openRecently = $guest->contributions()
            ->where('status', ContributionStatus::Pending)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($openRecently >= self::MAX_OPEN_PER_HOUR) {
            return back()->withErrors(['amount' => __('Vous avez déjà des paiements en cours : terminez-les ou réessayez dans une heure.')]);
        }

        $gift = $request->integer('gift_id') ? Gift::find($request->integer('gift_id')) : null;

        try {
            $contribution = $payments->start($guest, $request->integer('amount'), $gift, $request->validated('message'), $request->validated('phone'));
        } catch (GiftBudgetExceeded $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        } catch (PaymentUnavailable $e) {
            return back()->withErrors(['amount' => $e->getMessage().' '.__('Merci de réessayer dans quelques instants.')]);
        }

        // Redirection externe vers MoneyFusion (compatible Inertia).
        return Inertia::location($contribution->payment_url);
    }

    /**
     * Retour de l'invité depuis MoneyFusion. Le paramètre de retour n'est jamais cru :
     * le statut est relu auprès de l'API.
     */
    public function return(Guest $guest, Contribution $contribution, ContributionPayments $payments): RedirectResponse
    {
        abort_unless($contribution->guest_id === $guest->id, 404);

        try {
            $status = $payments->verify($contribution);
        } catch (PaymentUnavailable) {
            $status = $contribution->status;
        }

        return to_route('invitations.show', $guest->token)->with('donation_status', $status->value);
    }
}
