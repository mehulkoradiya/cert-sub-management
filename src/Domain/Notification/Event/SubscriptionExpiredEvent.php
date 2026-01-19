<?php
declare(strict_types=1);

namespace App\Domain\Notification\Event;

final class SubscriptionExpiredEvent implements DomainEventInterface
{
    private int $subscriptionId;

    public function __construct(int $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getName(): string
    {
        return 'subscription.expired';
    }
}

