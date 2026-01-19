<?php
declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Commands\RenewSubscriptionsCommand;
use App\Application\Services\CertificationService;
use App\Application\Services\CourseService;
use App\Application\Services\SubscriptionService;
use App\Domain\Certification\RequirementType;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Subscription\SubscriptionType;
use App\Infrastructure\Logging\FileLogger;

final class HttpKernel
{
    private SubscriptionService $subscriptionService;
    private CertificationService $certificationService;
    private CourseService $courseService;
    private FileLogger $logger;

    public function __construct(
        SubscriptionService $subscriptionService,
        CertificationService $certificationService,
        CourseService $courseService,
        FileLogger $logger
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->certificationService = $certificationService;
        $this->courseService = $courseService;
        $this->logger = $logger;
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Fix for subdirectory deployment
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }
        
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        try {
            if ($method === 'POST' && $path === '/api/subscriptions') {
                $this->createSubscription();
                return;
            }

            if ($method === 'POST' && $path === '/api/subscriptions/renew') {
                $this->renewSubscriptions();
                return;
            }

            if ($method === 'POST' && $path === '/api/certifications') {
                $this->createCertification();
                return;
            }

            if ($method === 'GET' && preg_match('#^/api/certifications/(\d+)$#', $path, $matches)) {
                $this->getCertification((int) $matches[1]);
                return;
            }

            if ($method === 'POST' && preg_match('#^/api/certifications/(\d+)/areas$#', $path, $matches)) {
                $this->addRequirementArea((int) $matches[1]);
                return;
            }

            if ($method === 'POST' && preg_match('#^/api/certifications/(\d+)/areas/([^/]+)/courses$#', $path, $matches)) {
                $this->addCourseToArea((int) $matches[1], urldecode($matches[2]));
                return;
            }

            if ($method === 'POST' && preg_match('#^/api/certifications/(\d+)/publish$#', $path, $matches)) {
                $this->publishCertification((int) $matches[1]);
                return;
            }

            if ($method === 'POST' && $path === '/api/courses') {
                $this->createCourse();
                return;
            }

            if ($method === 'GET' && preg_match('#^/api/courses/(\d+)$#', $path, $matches)) {
                $this->getCourse((int) $matches[1]);
                return;
            }

            if ($method === 'GET' && $path === '/api/health') {
                $this->sendJson(200, ['status' => 'ok']);
                return;
            }

            $this->sendJson(404, ['error' => 'Not found']);
        } catch (ValidationException $e) {
            $this->sendJson(400, ['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            $this->sendJson(404, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->sendJson(500, ['error' => 'Internal server error']);
        }
    }

    private function createSubscription(): void
    {
        $data = $this->decodeJson();

        $userId = $this->requireInt($data, 'user_id');
        $certificationId = $this->requireInt($data, 'certification_id');
        $typeValue = $this->requireString($data, 'type');
        $autoRenew = $this->requireBool($data, 'auto_renew');

        try {
            $type = SubscriptionType::from($typeValue);
        } catch (\ValueError) {
            throw new ValidationException('Invalid subscription type');
        }

        $subscription = $this->subscriptionService->createSubscription(
            $userId,
            $certificationId,
            $type,
            $autoRenew
        );

        $this->sendJson(201, [
            'status' => 'created',
            'subscription_state' => $subscription->getState()->value,
        ]);
    }

    private function renewSubscriptions(): void
    {
        $command = new RenewSubscriptionsCommand($this->subscriptionService);
        $command->execute();
        $this->sendJson(200, ['status' => 'renewal_executed']);
    }

    private function createCertification(): void
    {
        $data = $this->decodeJson();

        $name = $this->requireString($data, 'name');
        $description = $this->requireString($data, 'description');

        $certification = $this->certificationService->createDraft($name, $description);

        $this->sendJson(201, [
            'id' => $certification->getId(),
            'name' => $certification->getName(),
            'description' => $certification->getDescription(),
            'status' => $certification->getStatus()->value,
        ]);
    }

    private function getCertification(int $id): void
    {
        $certification = $this->certificationService->getCertification($id);

        $areas = [];
        foreach ($certification->getRequirementAreas() as $area) {
            $courses = [];
            foreach ($area->getCourses() as $course) {
                $courses[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'duration' => $course->getDurationHours(),
                    'category' => $course->getCategory(),
                ];
            }

            $areas[] = [
                'name' => $area->getName(),
                'requirement_type' => $area->getRequirementType()->value,
                'requirement_value' => $area->getRequirementValue(),
                'courses' => $courses,
            ];
        }

        $this->sendJson(200, [
            'id' => $certification->getId(),
            'name' => $certification->getName(),
            'description' => $certification->getDescription(),
            'status' => $certification->getStatus()->value,
            'requirement_areas' => $areas,
        ]);
    }

    private function addRequirementArea(int $id): void
    {
        $data = $this->decodeJson();

        $name = $this->requireString($data, 'name');
        $typeValue = $this->requireString($data, 'requirement_type');
        $requirementValue = $this->requireInt($data, 'requirement_value');

        try {
            $type = RequirementType::from($typeValue);
        } catch (\ValueError) {
            throw new ValidationException('Invalid requirement type');
        }

        $certification = $this->certificationService->addRequirementArea(
            $id,
            $name,
            $type,
            $requirementValue
        );

        $this->sendJson(201, [
            'id' => $certification->getId(),
            'status' => $certification->getStatus()->value,
        ]);
    }

    private function addCourseToArea(int $certId, string $areaName): void
    {
        $data = $this->decodeJson();
        $courseId = $this->requireInt($data, 'course_id');

        $certification = $this->certificationService->addCourseToArea($certId, $areaName, $courseId);

        $this->sendJson(200, [
            'id' => $certification->getId(),
            'status' => 'course_added'
        ]);
    }

    private function publishCertification(int $id): void
    {
        $certification = $this->certificationService->publish($id);

        $this->sendJson(200, [
            'id' => $certification->getId(),
            'status' => $certification->getStatus()->value,
        ]);
    }

    private function createCourse(): void
    {
        $data = $this->decodeJson();
        $title = $this->requireString($data, 'title');
        $duration = $this->requireInt($data, 'duration');
        $category = $this->requireString($data, 'category');

        $course = $this->courseService->createCourse($title, $duration, $category);

        $this->sendJson(201, [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'duration' => $course->getDurationHours(),
            'category' => $course->getCategory(),
        ]);
    }

    private function getCourse(int $id): void
    {
        try {
            $course = $this->courseService->getCourse($id);
            $this->sendJson(200, [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'duration' => $course->getDurationHours(),
                'category' => $course->getCategory(),
            ]);
        } catch (\RuntimeException $e) {
            $this->sendJson(404, ['error' => 'Course not found']);
        }
    }

    private function decodeJson(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new ValidationException('Invalid JSON payload');
        }

        return $data;
    }

    private function requireInt(array $data, string $key): int
    {
        if (!array_key_exists($key, $data)) {
            throw new ValidationException('Missing field ' . $key);
        }

        if (!is_int($data[$key]) && !is_string($data[$key])) {
            throw new ValidationException('Invalid integer field ' . $key);
        }

        if (is_string($data[$key]) && !ctype_digit($data[$key])) {
            throw new ValidationException('Invalid integer field ' . $key);
        }

        return (int) $data[$key];
    }

    private function requireString(array $data, string $key): string
    {
        if (!array_key_exists($key, $data)) {
            throw new ValidationException('Missing field ' . $key);
        }

        if (!is_string($data[$key])) {
            throw new ValidationException('Invalid string field ' . $key);
        }

        return $data[$key];
    }

    private function requireBool(array $data, string $key): bool
    {
        if (!array_key_exists($key, $data)) {
            throw new ValidationException('Missing field ' . $key);
        }

        if (is_bool($data[$key])) {
            return $data[$key];
        }

        if ($data[$key] === 1 || $data[$key] === 0) {
            return (bool) $data[$key];
        }

        if (is_string($data[$key])) {
            if ($data[$key] === '1' || strtolower($data[$key]) === 'true') {
                return true;
            }
            if ($data[$key] === '0' || strtolower($data[$key]) === 'false') {
                return false;
            }
        }

        throw new ValidationException('Invalid boolean field ' . $key);
    }

    private function sendJson(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
