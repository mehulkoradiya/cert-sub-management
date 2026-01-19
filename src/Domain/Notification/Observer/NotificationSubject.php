<?php
declare(strict_types=1);

namespace App\Domain\Notification\Observer;

use App\Domain\Notification\Event\DomainEventInterface;

final class NotificationSubject
{
    /**
     * @var NotificationObserverInterface[]
     */
    private array $observers = [];

    public function attach(NotificationObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(DomainEventInterface $event): void
    {
        foreach ($this->observers as $observer) {
            $observer->handle($event);
        }
    }
}

