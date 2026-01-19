<?php
declare(strict_types=1);

namespace App\Tests\Domain\Certification;

use App\Domain\Certification\Certification;
use App\Domain\Certification\CertificationStatus;
use App\Domain\Certification\Course;
use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;
use App\Domain\Certification\Specification\CourseCountSpecification;
use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class CertificationValidationTest extends TestCase
{
    public function testCannotPublishWithoutRequirementAreas(): void
    {
        $certification = new Certification(null, 'Name', 'Description', CertificationStatus::Draft);

        $this->expectException(ValidationException::class);
        $certification->publish();
    }

    public function testRequirementAreaRejectsDuplicateCourses(): void
    {
        $area = new RequirementArea('Core', RequirementType::CourseCount, 1);
        $spec = new CourseCountSpecification();

        $course = new Course(1, 'Course', 5, 'Category');
        $area->addCourse($course, $spec);

        $this->expectException(ValidationException::class);
        $area->addCourse($course, $spec);
    }
}

