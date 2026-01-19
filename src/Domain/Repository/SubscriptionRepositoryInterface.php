<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Subscription\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;

    public function findExpiringActiveWithAutoRenew(\DateTimeImmutable $date): array;

    public function findCancelable(\DateTimeImmutable $date): array;
}

