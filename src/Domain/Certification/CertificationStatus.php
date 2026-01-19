<?php
declare(strict_types=1);

namespace App\Domain\Certification;

enum CertificationStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}

