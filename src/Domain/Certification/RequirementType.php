<?php
declare(strict_types=1);

namespace App\Domain\Certification;

enum RequirementType: string
{
    case CourseCount = 'course_count';
    case TotalDuration = 'total_duration';
}

