<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Certification\Certification;

interface CertificationRepositoryInterface
{
    public function save(Certification $certification): void;

    public function findById(int $id): ?Certification;
}

