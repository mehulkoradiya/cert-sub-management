<?php
declare(strict_types=1);

namespace App\Domain\Certification\Specification;

use App\Domain\Certification\Certification;

final class MaxCoursesPerCertificationSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(object $candidate): bool
    {
        if (!$candidate instanceof Certification) {
            return false;
        }

        return $candidate->getTotalCourseCount() <= 50;
    }
}

