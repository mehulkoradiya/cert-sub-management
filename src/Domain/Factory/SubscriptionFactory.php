<?php
declare(strict_types=1);

namespace App\Domain\Factory;

use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionType;

final class SubscriptionFactory
{
    public function create(
        int $userId,
        int $certificationId,
        SubscriptionType $type,
        bool $autoRenew
    ): Subscription {
        $start = new \DateTimeImmutable('now');
        $end = $type === SubscriptionType::Monthly
            ? $start->modify('+1 month')
            : $start->modify('+1 year');

        return new Subscription(
            null,
            $userId,
            $certificationId,
            $type,
            SubscriptionState::Active,
            $start,
            $end,
            $autoRenew
        );
    }
}

