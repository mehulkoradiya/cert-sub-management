<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Factory\SubscriptionFactory;
use App\Domain\Notification\Event\SubscriptionActivatedEvent;
use App\Domain\Notification\Event\SubscriptionExpiredEvent;
use App\Domain\Notification\Event\SubscriptionRenewedEvent;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionState;

final class SubscriptionService
{
    private SubscriptionRepositoryInterface $subscriptionRepository;
    private SubscriptionFactory $subscriptionFactory;
    private NotificationSubject $notificationSubject;

    public function __construct(
        SubscriptionRepositoryInterface $subscriptionRepository,
        SubscriptionFactory $subscriptionFactory,
        NotificationSubject $notificationSubject
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->notificationSubject = $notificationSubject;
    }

    public function createSubscription(
        int $userId,
        int $certificationId,
        \App\Domain\Subscription\SubscriptionType $type,
        bool $autoRenew
    ): Subscription {
        $subscription = $this->subscriptionFactory->create($userId, $certificationId, $type, $autoRenew);
        $this->subscriptionRepository->save($subscription);

        if ($subscription->getId() !== null) {
            $this->notificationSubject->notify(new SubscriptionActivatedEvent($subscription->getId()));
        }

        return $subscription;
    }

    public function renewSubscriptions(\DateTimeImmutable $referenceDate): void
    {
        $expiring = $this->subscriptionRepository->findExpiringActiveWithAutoRenew($referenceDate);
        foreach ($expiring as $subscription) {
            if (!$subscription instanceof Subscription) {
                continue;
            }
            $subscription->renew();
            $this->subscriptionRepository->save($subscription);
            if ($subscription->getId() !== null) {
                $this->notificationSubject->notify(new SubscriptionRenewedEvent($subscription->getId()));
            }
        }

        $cancelable = $this->subscriptionRepository->findCancelable($referenceDate);
        foreach ($cancelable as $subscription) {
            if (!$subscription instanceof Subscription) {
                continue;
            }

            // Expire if it's Cancelled OR (Active and no auto-renew)
            // The repository query already filters by date and these conditions, so we can just expire.
            // But double check state to be safe.
            if (
                $subscription->getState() === SubscriptionState::Cancelled || 
                ($subscription->getState() === SubscriptionState::Active && !$subscription->isAutoRenew())
            ) {
                $subscription->expire();
                $this->subscriptionRepository->save($subscription);
                if ($subscription->getId() !== null) {
                    $this->notificationSubject->notify(new SubscriptionExpiredEvent($subscription->getId()));
                }
            }
        }
    }
}

