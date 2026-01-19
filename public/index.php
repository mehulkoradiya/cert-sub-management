<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\CertificationService;
use App\Application\Services\CourseService;
use App\Application\Services\SubscriptionService;
use App\Domain\Factory\SubscriptionFactory;
use App\Domain\Factory\CertificationFactory;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Notification\EmailNotificationObserver;
use App\Infrastructure\Notification\LogNotificationObserver;
use App\Infrastructure\Persistence\CertificationRepository;
use App\Infrastructure\Persistence\CourseRepository;
use App\Infrastructure\Persistence\SubscriptionRepository;
use App\Presentation\HTTP\HttpKernel;

$logger = new FileLogger();

try {
    $subscriptionRepository = new SubscriptionRepository();
    $subscriptionFactory = new SubscriptionFactory();
    $certificationRepository = new CertificationRepository();
    $courseRepository = new CourseRepository();
    $certificationFactory = new CertificationFactory();

    $subject = new NotificationSubject();
    $subject->attach(new EmailNotificationObserver($logger));
    $subject->attach(new LogNotificationObserver($logger));

    $subscriptionService = new SubscriptionService(
        $subscriptionRepository,
        $subscriptionFactory,
        $subject
    );

    $certificationService = new CertificationService(
        $certificationRepository,
        $courseRepository,
        $certificationFactory,
        $subject
    );

    $courseService = new CourseService($courseRepository);

    $kernel = new HttpKernel($subscriptionService, $certificationService, $courseService, $logger);
    $kernel->handle();
} catch (\Throwable $e) {
    $logger->error($e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Internal server error']);
}
