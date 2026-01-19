<?php
declare(strict_types=1);

namespace App\Domain\Certification\Specification;

final class AlwaysSatisfiedSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(object $candidate): bool
    {
        return true;
    }
}
