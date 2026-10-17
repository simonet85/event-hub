<?php

namespace App\Jobs;

use App\Models\Contribution;
use App\Services\ContributionPayments;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerifyContribution implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(public Contribution $contribution) {}

    /**
     * Si MoneyFusion est injoignable, l'exception relance le job (puis `contributions:reconcile` prend le relais).
     */
    public function handle(ContributionPayments $payments): void
    {
        $payments->verify($this->contribution->fresh());
    }
}
