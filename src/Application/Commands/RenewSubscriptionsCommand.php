<?php
declare(strict_types=1);

namespace App\Application\Commands;

use App\Application\Services\SubscriptionService;

final class RenewSubscriptionsCommand implements CommandInterface
{
    private SubscriptionService $subscriptionService;
    private \DateTimeImmutable $referenceDate;

    public function __construct(SubscriptionService $subscriptionService, ?\DateTimeImmutable $referenceDate = null)
    {
        $this->subscriptionService = $subscriptionService;
        $this->referenceDate = $referenceDate ?? new \DateTimeImmutable('now');
    }

    public function execute(): void
    {
        $this->subscriptionService->renewSubscriptions($this->referenceDate);
    }
}

