<?php
declare(strict_types=1);

namespace App\Tests\Domain\Subscription;

use App\Domain\Exception\ValidationException;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionType;
use PHPUnit\Framework\TestCase;

final class SubscriptionStateTransitionTest extends TestCase
{
    public function testPauseOnlyActive(): void
    {
        $start = new \DateTimeImmutable('now');
        $end = $start->modify('+1 month');
        $subscription = new Subscription(
            null,
            1,
            1,
            SubscriptionType::Monthly,
            SubscriptionState::Active,
            $start,
            $end,
            true
        );

        $subscription->pause();
        self::assertSame(SubscriptionState::Paused, $subscription->getState());

        $this->expectException(ValidationException::class);
        $subscription->pause();
    }

    public function testCancelThenExpire(): void
    {
        $start = new \DateTimeImmutable('now');
        $end = $start->modify('+1 month');
        $subscription = new Subscription(
            null,
            1,
            1,
            SubscriptionType::Monthly,
            SubscriptionState::Active,
            $start,
            $end,
            true
        );

        $subscription->cancel();
        self::assertSame(SubscriptionState::Cancelled, $subscription->getState());

        $subscription->expire();
        self::assertSame(SubscriptionState::Expired, $subscription->getState());
    }
}

