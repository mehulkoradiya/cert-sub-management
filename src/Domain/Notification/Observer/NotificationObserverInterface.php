<?php
declare(strict_types=1);

namespace App\Domain\Notification\Observer;

use App\Domain\Notification\Event\DomainEventInterface;

interface NotificationObserverInterface
{
    public function handle(DomainEventInterface $event): void;
}

