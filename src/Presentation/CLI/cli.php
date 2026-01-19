<?php
declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use App\Application\Commands\RenewSubscriptionsCommand;
use App\Application\Services\SubscriptionService;
use App\Domain\Factory\SubscriptionFactory;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Notification\EmailNotificationObserver;
use App\Infrastructure\Notification\LogNotificationObserver;
use App\Infrastructure\Persistence\SubscriptionRepository;

$logger = new FileLogger();

try {
    $subscriptionRepository = new SubscriptionRepository();
    $subscriptionFactory = new SubscriptionFactory();

    $subject = new NotificationSubject();
    $subject->attach(new EmailNotificationObserver($logger));
    $subject->attach(new LogNotificationObserver($logger));

    $subscriptionService = new SubscriptionService(
        $subscriptionRepository,
        $subscriptionFactory,
        $subject
    );

    $commandName = $argv[1] ?? null;

    if ($commandName === 'subscriptions:renew') {
        $command = new RenewSubscriptionsCommand($subscriptionService);
        $command->execute();
        echo "Renewal command executed\n";
    } else {
        echo "Usage: php cli.php subscriptions:renew\n";
    }
} catch (\Throwable $e) {
    $logger->error($e->getMessage());
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

