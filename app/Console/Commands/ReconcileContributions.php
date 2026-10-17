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
        $expired = Contribution::where('status', ContributionStatus::Pending)
            ->where('created_at', '<', now()->subHours(ContributionPayments::EXPIRES_AFTER_HOURS))
            ->get();

        // Dernière vérification avant expiration : un paiement tardif ne doit pas être perdu.
        foreach ($expired as $contribution) {
            try {
                $status = $payments->verify($contribution);
            } catch (PaymentUnavailable) {
                continue;
            }

            if ($status === ContributionStatus::Pending) {
                $contribution->update(['status' => ContributionStatus::Expired]);
            }
        }

        $checked = 0;

        Contribution::where('status', ContributionStatus::Pending)
            ->whereNotNull('payment_token')
            ->where('created_at', '>=', now()->subHours(ContributionPayments::EXPIRES_AFTER_HOURS))
            ->orderBy('id')
            ->each(function (Contribution $contribution) use ($payments, &$checked): void {
                try {
                    $payments->verify($contribution);
                    $checked++;
                } catch (PaymentUnavailable $e) {
                    $this->warn("Contribution {$contribution->id} : {$e->getMessage()}");
                }
            });

        $this->info("{$checked} contribution(s) revérifiée(s), {$expired->count()} expirée(s) examinée(s).");

        return self::SUCCESS;
    }
}
