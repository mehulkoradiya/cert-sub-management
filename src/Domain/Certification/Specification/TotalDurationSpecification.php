<?php
declare(strict_types=1);

namespace App\Domain\Certification\Specification;

use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;

final class TotalDurationSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(object $candidate): bool
    {
        if (!$candidate instanceof RequirementArea) {
            return false;
        }

        if ($candidate->getRequirementType() !== RequirementType::TotalDuration) {
            return true;
        }

        $total = 0;
        foreach ($candidate->getCourses() as $course) {
            $total += $course->getDurationHours();
        }

        return $total >= $candidate->getRequirementValue();
    }
}

