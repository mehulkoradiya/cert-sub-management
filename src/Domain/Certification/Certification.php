<?php
declare(strict_types=1);

namespace App\Domain\Certification;

use App\Domain\Certification\Specification\MaxCoursesPerCertificationSpecification;
use App\Domain\Exception\ValidationException;

final class Certification
{
    private ?int $id;
    private string $name;
    private string $description;
    private CertificationStatus $status;
    private array $requirementAreas = [];

    public function __construct(?int $id, string $name, string $description, CertificationStatus $status)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new ValidationException('Certification id cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): CertificationStatus
    {
        return $this->status;
    }

    public function publish(): void
    {
        if ($this->status === CertificationStatus::Active) {
            return;
        }

        if (count($this->requirementAreas) === 0) {
            throw new ValidationException('Certification must have at least one requirement area before publishing');
        }

        foreach ($this->requirementAreas as $area) {
            $courses = $area->getCourses();
            $type = $area->getRequirementType();
            $value = $area->getRequirementValue();

            if ($type === RequirementType::CourseCount) {
                if (count($courses) < $value) {
                    throw new ValidationException(
                        sprintf('Area "%s" requires %d courses, but has %d', $area->getName(), $value, count($courses))
                    );
                }
            } elseif ($type === RequirementType::TotalDuration) {
                $totalDuration = 0;
                foreach ($courses as $course) {
                    $totalDuration += $course->getDurationHours();
                }
                if ($totalDuration < $value) {
                    throw new ValidationException(
                        sprintf('Area "%s" requires %d hours duration, but has %d', $area->getName(), $value, $totalDuration)
                    );
                }
            }
        }

        $this->status = CertificationStatus::Active;
    }

    public function archive(): void
    {
        $this->status = CertificationStatus::Archived;
    }

    public function addRequirementArea(RequirementArea $area): void
    {
        if ($this->status !== CertificationStatus::Draft) {
            throw new ValidationException('Cannot modify certification structure after it is published');
        }

        $this->requirementAreas[] = $area;

        $specification = new MaxCoursesPerCertificationSpecification();
        if (!$specification->isSatisfiedBy($this)) {
            array_pop($this->requirementAreas);
            throw new ValidationException('Maximum number of courses per certification exceeded');
        }
    }

    public function addCourseToArea(string $areaName, Course $course): void
    {
        if ($this->status !== CertificationStatus::Draft) {
            throw new ValidationException('Cannot add courses to a published certification');
        }

        if ($this->getTotalCourseCount() >= 50) {
            throw new ValidationException('Maximum number of courses per certification exceeded (Max 50)');
        }

        $areaFound = false;
        foreach ($this->requirementAreas as $area) {
            if ($area->getName() === $areaName) {
                // Use AlwaysSatisfiedSpecification as we handle max courses check at certification level
                $area->addCourse($course, new \App\Domain\Certification\Specification\AlwaysSatisfiedSpecification());
                $areaFound = true;
                break;
            }
        }

        if (!$areaFound) {
            throw new ValidationException('Requirement area not found');
        }
    }

    public function getRequirementAreas(): array
    {
        return $this->requirementAreas;
    }

    public function getTotalCourseCount(): int
    {
        $count = 0;
        foreach ($this->requirementAreas as $area) {
            $count += count($area->getCourses());
        }
        return $count;
    }
}
