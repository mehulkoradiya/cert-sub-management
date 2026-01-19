<?php
declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Notification\Event\DomainEventInterface;
use App\Domain\Notification\Observer\NotificationObserverInterface;
use App\Infrastructure\Logging\FileLogger;

final class EmailNotificationObserver implements NotificationObserverInterface
{
    private FileLogger $logger;

    public function __construct(FileLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(DomainEventInterface $event): void
    {
        $this->logger->info('Email notification sent for event ' . $event->getName());
    }
}

