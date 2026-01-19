<?php
declare(strict_types=1);

namespace App\Domain\Certification\Specification;

interface SpecificationInterface
{
    public function isSatisfiedBy(object $candidate): bool;
}

