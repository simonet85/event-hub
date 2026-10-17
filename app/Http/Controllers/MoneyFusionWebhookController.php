<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Jobs\VerifyContribution;
use App\Models\Contribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoneyFusionWebhookController extends Controller
{
    /**
     * Notification MoneyFusion (non signée) : simple signal. Seul `tokenPay` est lu ;
     * le contenu n'est jamais cru, la contribution est revérifiée auprès de l'API.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->input('tokenPay');

        if (! is_string($token) || $token === '') {
            return response()->json(['status' => 'error', 'message' => 'Missing tokenPay'], 400);
        }

        $contribution = Contribution::where('payment_token', $token)->first();

        if (! $contribution) {
            return response()->json(['status' => 'error', 'message' => 'Unknown payment'], 404);
        }

        if ($contribution->status === ContributionStatus::Pending) {
            VerifyContribution::dispatch($contribution);
        }

        return response()->json(['status' => 'ok']);
    }
}
