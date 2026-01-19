<?php
declare(strict_types=1);

namespace Tests\Application\Commands;

use App\Application\Commands\RenewSubscriptionsCommand;
use App\Application\Services\SubscriptionService;
use App\Domain\Factory\SubscriptionFactory;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Domain\Notification\Observer\NotificationObserverInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionType;
use PHPUnit\Framework\TestCase;

class RenewalCommandTest extends TestCase
{
    public function testRenewEligibleSubscriptions(): void
    {
        $repo = $this->createMock(SubscriptionRepositoryInterface::class);
        $factory = new SubscriptionFactory();
        $subject = new NotificationSubject(); // Use real subject
        
        // Attach mock observer to verify notification
        $observer = $this->createMock(NotificationObserverInterface::class);
        $observer->expects($this->once())->method('handle');
        $subject->attach($observer);

        $service = new SubscriptionService($repo, $factory, $subject);
        
        $sub = new Subscription(
            1, 1, 1, 
            SubscriptionType::Monthly, 
            SubscriptionState::Active, 
            new \DateTimeImmutable('-1 month'), 
            new \DateTimeImmutable('-1 day'), 
            true
        );

        $repo->method('findExpiringActiveWithAutoRenew')->willReturn([$sub]);
        $repo->method('findCancelable')->willReturn([]);

        $repo->expects($this->once())->method('save')->with($this->callback(function (Subscription $s) {
            return $s->getState() === SubscriptionState::Active && 
                   $s->getEndDate() > new \DateTimeImmutable('now');
        }));

        $command = new RenewSubscriptionsCommand($service);
        $command->execute();
    }

    public function testExpireCancelledSubscriptions(): void
    {
        $repo = $this->createMock(SubscriptionRepositoryInterface::class);
        $factory = new SubscriptionFactory();
        $subject = new NotificationSubject();
        
        $observer = $this->createMock(NotificationObserverInterface::class);
        $observer->expects($this->once())->method('handle');
        $subject->attach($observer);

        $service = new SubscriptionService($repo, $factory, $subject);

        $sub = new Subscription(
            1, 1, 1, 
            SubscriptionType::Monthly, 
            SubscriptionState::Cancelled, 
            new \DateTimeImmutable('-2 month'), 
            new \DateTimeImmutable('-1 day'), 
            true
        );

        $repo->method('findExpiringActiveWithAutoRenew')->willReturn([]);
        $repo->method('findCancelable')->willReturn([$sub]);

        $repo->expects($this->once())->method('save')->with($this->callback(function (Subscription $s) {
            return $s->getState() === SubscriptionState::Expired;
        }));

        $command = new RenewSubscriptionsCommand($service);
        $command->execute();
    }

    public function testExpireActiveNonRenewingSubscriptions(): void
    {
        $repo = $this->createMock(SubscriptionRepositoryInterface::class);
        $factory = new SubscriptionFactory();
        $subject = new NotificationSubject();
        
        $observer = $this->createMock(NotificationObserverInterface::class);
        $observer->expects($this->once())->method('handle');
        $subject->attach($observer);

        $service = new SubscriptionService($repo, $factory, $subject);

        $sub = new Subscription(
            1, 1, 1, 
            SubscriptionType::Monthly, 
            SubscriptionState::Active, 
            new \DateTimeImmutable('-2 month'), 
            new \DateTimeImmutable('-1 day'), 
            false // No auto renew
        );

        $repo->method('findExpiringActiveWithAutoRenew')->willReturn([]);
        $repo->method('findCancelable')->willReturn([$sub]);

        $repo->expects($this->once())->method('save')->with($this->callback(function (Subscription $s) {
            return $s->getState() === SubscriptionState::Expired;
        }));

        $command = new RenewSubscriptionsCommand($service);
        $command->execute();
    }
}
