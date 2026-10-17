<?php

namespace App\Console\Commands;

use App\Enums\ContributionStatus;
use App\Exceptions\PaymentUnavailable;
use App\Models\Contribution;
use App\Services\ContributionPayments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contributions:reconcile')]
#[Description('Revérifie auprès de MoneyFusion les contributions en attente et fait expirer celles jamais payées')]
class ReconcileContributions extends Command
{
    public function handle(ContributionPayments $payments): int
    {
        $expired = 0;
        $checked = 0;

        // chunkById (et non chunk/each) : les statuts changent pendant le parcours, un décalage (offset) sauterait des lignes.
        Contribution::where('status', ContributionStatus::Pending)
            ->where('created_at', '<', now()->subHours(ContributionPayments::EXPIRES_AFTER_HOURS))
            ->chunkById(100, function ($contributions) use ($payments, &$expired): void {
                foreach ($contributions as $contribution) {
                    // Dernière vérification avant expiration : un paiement tardif ne doit pas être perdu.
                    try {
                        $status = $payments->verify($contribution);
                    } catch (PaymentUnavailable) {
                        continue;
                    }

                    if ($status === ContributionStatus::Pending) {
                        $contribution->update(['status' => ContributionStatus::Expired]);
                        $expired++;
                    }
                }
            });

        Contribution::where('status', ContributionStatus::Pending)
            ->whereNotNull('payment_token')
            ->where('created_at', '>=', now()->subHours(ContributionPayments::EXPIRES_AFTER_HOURS))
            ->chunkById(100, function ($contributions) use ($payments, &$checked): void {
                foreach ($contributions as $contribution) {
                    try {
                        $payments->verify($contribution);
                        $checked++;
                    } catch (PaymentUnavailable $e) {
                        $this->warn("Contribution {$contribution->id} : {$e->getMessage()}");
                    }
                }
            });

        $this->info("{$checked} contribution(s) revérifiée(s), {$expired} expirée(s).");

        return self::SUCCESS;
    }
}
