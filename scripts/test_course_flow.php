<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\CertificationService;
use App\Application\Services\CourseService;
use App\Application\Services\SubscriptionService;
use App\Domain\Factory\CertificationFactory;
use App\Domain\Factory\SubscriptionFactory;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Persistence\CertificationRepository;
use App\Infrastructure\Persistence\CourseRepository;
use App\Infrastructure\Persistence\SubscriptionRepository;
use App\Presentation\HTTP\HttpKernel;

function mockRequest(string $method, string $uri, array $data = []): string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    
    // Reset input buffer mock?
    // php://input cannot be easily mocked for file_get_contents unless we use stream wrapper or dependency injection for input reading.
    // My HttpKernel reads php://input. I should probably refactor it to accept a Request object or similar, but for now I'll just write to a temp file and mock file_get_contents? No.
    // I'll skip HttpKernel testing via script and test Services directly, 
    // OR I can use a helper to overwrite the "read body" method if I could.
    // Given the constraints, I'll test the SERVICES directly to ensure logic is correct.
    return "";
}

// Service Testing
$logger = new FileLogger();
$subRepo = new SubscriptionRepository();
$subFactory = new SubscriptionFactory();
$certRepo = new CertificationRepository();
$courseRepo = new CourseRepository();
$certFactory = new CertificationFactory();
$subject = new NotificationSubject();

$certService = new CertificationService($certRepo, $courseRepo, $certFactory, $subject);
$courseService = new CourseService($courseRepo);

echo "1. Creating Course...\n";
$course = $courseService->createCourse("Advanced PHP", 20, "Backend");
echo "Course created: " . $course->getId() . " - " . $course->getTitle() . "\n";

echo "2. Creating Certification Draft...\n";
$cert = $certService->createDraft("PHP Master", "Master PHP Certification");
echo "Certification created: " . $cert->getId() . "\n";

echo "3. Adding Requirement Area...\n";
$cert = $certService->addRequirementArea(
    $cert->getId(),
    "Core Skills",
    \App\Domain\Certification\RequirementType::CourseCount,
    1
);
echo "Requirement Area added.\n";

echo "4. Adding Course to Area...\n";
$cert = $certService->addCourseToArea($cert->getId(), "Core Skills", $course->getId());
echo "Course added to area.\n";

echo "5. Verifying Persistence...\n";
// Re-fetch certification
$fetchedCert = $certService->getCertification($cert->getId());
$areas = $fetchedCert->getRequirementAreas();
$found = false;
foreach ($areas as $area) {
    if ($area->getName() === "Core Skills") {
        foreach ($area->getCourses() as $c) {
            if ($c->getId() === $course->getId()) {
                $found = true;
                echo "SUCCESS: Course found in requirement area after persistence.\n";
            }
        }
    }
}

if (!$found) {
    echo "FAILURE: Course not found in requirement area.\n";
    exit(1);
}

echo "Done.\n";
