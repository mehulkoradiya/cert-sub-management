<?php
declare(strict_types=1);

namespace App\Domain\Factory;

use App\Domain\Certification\Certification;
use App\Domain\Certification\CertificationStatus;

final class CertificationFactory
{
    public function createDraft(string $name, string $description): Certification
    {
        return new Certification(null, $name, $description, CertificationStatus::Draft);
    }
}

