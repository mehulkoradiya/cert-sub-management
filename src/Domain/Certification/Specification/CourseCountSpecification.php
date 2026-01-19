<?php
declare(strict_types=1);

namespace App\Domain\Certification\Specification;

use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;

final class CourseCountSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(object $candidate): bool
    {
        if (!$candidate instanceof RequirementArea) {
            return false;
        }

        if ($candidate->getRequirementType() !== RequirementType::CourseCount) {
            return true;
        }

        return count($candidate->getCourses()) >= $candidate->getRequirementValue();
    }
}

