<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Certification\Course;

interface CourseRepositoryInterface
{
    public function save(Course $course): void;
    public function findById(int $id): ?Course;
    public function findAll(): array;
}
