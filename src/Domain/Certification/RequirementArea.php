<?php
declare(strict_types=1);

namespace App\Domain\Certification;

use App\Domain\Certification\Specification\SpecificationInterface;
use App\Domain\Exception\ValidationException;

final class RequirementArea
{
    private string $name;
    private RequirementType $requirementType;
    private int $requirementValue;
    private array $courses = [];

    public function __construct(string $name, RequirementType $requirementType, int $requirementValue)
    {
        if ($requirementValue <= 0) {
            throw new ValidationException('Requirement value must be positive');
        }

        $this->name = $name;
        $this->requirementType = $requirementType;
        $this->requirementValue = $requirementValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRequirementType(): RequirementType
    {
        return $this->requirementType;
    }

    public function getRequirementValue(): int
    {
        return $this->requirementValue;
    }

    public function getCourses(): array
    {
        return $this->courses;
    }

    public function addCourse(Course $course, SpecificationInterface $specification): void
    {
        foreach ($this->courses as $existing) {
            if ($existing->getId() === $course->getId()) {
                throw new ValidationException('Course already exists in this requirement area');
            }
        }

        $this->courses[] = $course;

        if (!$specification->isSatisfiedBy($this)) {
            array_pop($this->courses);
            throw new ValidationException('Requirement area validation failed after adding course');
        }
    }
}

