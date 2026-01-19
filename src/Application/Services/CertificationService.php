<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Certification\Certification;
use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;
use App\Domain\Certification\Specification\AlwaysSatisfiedSpecification;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\CertificationFactory;
use App\Domain\Notification\Event\CertificationPublishedEvent;
use App\Domain\Notification\Observer\NotificationSubject;
use App\Domain\Repository\CertificationRepositoryInterface;
use App\Domain\Repository\CourseRepositoryInterface;

final class CertificationService
{
    private CertificationRepositoryInterface $certificationRepository;
    private CourseRepositoryInterface $courseRepository;
    private CertificationFactory $certificationFactory;
    private NotificationSubject $notificationSubject;

    public function __construct(
        CertificationRepositoryInterface $certificationRepository,
        CourseRepositoryInterface $courseRepository,
        CertificationFactory $certificationFactory,
        NotificationSubject $notificationSubject
    ) {
        $this->certificationRepository = $certificationRepository;
        $this->courseRepository = $courseRepository;
        $this->certificationFactory = $certificationFactory;
        $this->notificationSubject = $notificationSubject;
    }

    public function createDraft(string $name, string $description): Certification
    {
        $name = trim($name);
        $description = trim($description);

        if ($name === '') {
            throw new ValidationException('Certification name cannot be empty');
        }

        $certification = $this->certificationFactory->createDraft($name, $description);
        $this->certificationRepository->save($certification);

        return $certification;
    }

    public function addRequirementArea(
        int $certificationId,
        string $name,
        RequirementType $type,
        int $requirementValue
    ): Certification {
        $certification = $this->getCertification($certificationId);

        $area = new RequirementArea($name, $type, $requirementValue);
        $certification->addRequirementArea($area);

        $this->certificationRepository->save($certification);

        return $certification;
    }

    public function addCourseToArea(int $certificationId, string $areaName, int $courseId): Certification
    {
        $certification = $this->getCertification($certificationId);
        $course = $this->courseRepository->findById($courseId);

        if ($course === null) {
            throw new ValidationException('Course not found');
        }

        $certification->addCourseToArea($areaName, $course);

        $this->certificationRepository->save($certification);

        return $certification;
    }

    public function publish(int $certificationId): Certification
    {
        $certification = $this->getCertification($certificationId);
        $certification->publish();
        $this->certificationRepository->save($certification);

        if ($certification->getId() !== null) {
            $this->notificationSubject->notify(
                new CertificationPublishedEvent($certification->getId())
            );
        }

        return $certification;
    }

    public function getCertification(int $id): Certification
    {
        $certification = $this->certificationRepository->findById($id);
        if ($certification === null) {
            throw new NotFoundException('Certification not found');
        }

        return $certification;
    }
}
