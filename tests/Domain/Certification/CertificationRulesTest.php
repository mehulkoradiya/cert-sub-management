<?php
declare(strict_types=1);

namespace Tests\Domain\Certification;

use App\Domain\Certification\Certification;
use App\Domain\Certification\CertificationStatus;
use App\Domain\Certification\Course;
use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;
use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class CertificationRulesTest extends TestCase
{
    private Certification $certification;

    protected function setUp(): void
    {
        $this->certification = new Certification(
            1,
            'Test Cert',
            'Description',
            CertificationStatus::Draft
        );
    }

    public function testCannotChangeRequirementTypeOncePublished(): void
    {
        // 1. Setup a valid certification
        $area = new RequirementArea('Area 1', RequirementType::CourseCount, 1);
        $course = new Course(1, 'PHP 101', 10, 'Backend');
        
        $this->certification->addRequirementArea($area);
        $this->certification->addCourseToArea('Area 1', $course);
        
        // 2. Publish it
        $this->certification->publish();
        $this->assertEquals(CertificationStatus::Active, $this->certification->getStatus());

        // 3. Try to add another requirement area (which changes structure)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot modify certification structure after it is published');
        
        $this->certification->addRequirementArea(
            new RequirementArea('Area 2', RequirementType::TotalDuration, 20)
        );
    }

    public function testCannotAddCourseOncePublished(): void
    {
        $area = new RequirementArea('Area 1', RequirementType::CourseCount, 1);
        $course = new Course(1, 'PHP 101', 10, 'Backend');
        $this->certification->addRequirementArea($area);
        $this->certification->addCourseToArea('Area 1', $course);
        
        $this->certification->publish();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot add courses to a published certification');

        $this->certification->addCourseToArea('Area 1', new Course(2, 'PHP 102', 10, 'Backend'));
    }

    public function testCourseDurationMustMeetRequirementValueOnPublish(): void
    {
        // Case A: Course Count Requirement
        $area1 = new RequirementArea('Count Area', RequirementType::CourseCount, 2);
        $this->certification->addRequirementArea($area1);
        
        // Add only 1 course (requires 2)
        $this->certification->addCourseToArea('Count Area', new Course(1, 'C1', 5, 'Cat'));

        try {
            $this->certification->publish();
            $this->fail('Should not publish if course count is not met');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('requires 2 courses, but has 1', $e->getMessage());
        }

        // Add 2nd course -> should pass
        $this->certification->addCourseToArea('Count Area', new Course(2, 'C2', 5, 'Cat'));
        $this->certification->publish();
        $this->assertEquals(CertificationStatus::Active, $this->certification->getStatus());
    }

    public function testTotalDurationMustMeetRequirementValueOnPublish(): void
    {
        $cert = new Certification(2, 'Duration Cert', 'Desc', CertificationStatus::Draft);
        
        // Case B: Total Duration Requirement (e.g., 20 hours)
        $area2 = new RequirementArea('Duration Area', RequirementType::TotalDuration, 20);
        $cert->addRequirementArea($area2);

        // Add 1 course of 10 hours (total 10 < 20)
        $cert->addCourseToArea('Duration Area', new Course(3, 'C3', 10, 'Cat'));

        try {
            $cert->publish();
            $this->fail('Should not publish if duration is not met');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('requires 20 hours duration, but has 10', $e->getMessage());
        }

        // Add another 10 hours -> total 20 -> should pass
        $cert->addCourseToArea('Duration Area', new Course(4, 'C4', 10, 'Cat'));
        $cert->publish();
        $this->assertEquals(CertificationStatus::Active, $cert->getStatus());
    }

    public function testSameCourseCannotBeAddedTwiceToSameArea(): void
    {
        $area = new RequirementArea('Area 1', RequirementType::CourseCount, 5);
        $this->certification->addRequirementArea($area);
        
        $course = new Course(1, 'Unique Course', 10, 'Cat');
        $this->certification->addCourseToArea('Area 1', $course);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Course already exists in this requirement area');

        // Add same course instance (or same ID)
        $this->certification->addCourseToArea('Area 1', $course);
    }

    public function testMax50CoursesPerCertification(): void
    {
        $area = new RequirementArea('Large Area', RequirementType::CourseCount, 1);
        $this->certification->addRequirementArea($area);

        // Add 50 courses -> OK
        for ($i = 1; $i <= 50; $i++) {
            $this->certification->addCourseToArea('Large Area', new Course($i, "Course $i", 1, 'Cat'));
        }

        $this->assertEquals(50, $this->certification->getTotalCourseCount());

        // Add 51st course -> Fail
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Maximum number of courses per certification exceeded');

        $this->certification->addCourseToArea('Large Area', new Course(51, 'Overflow Course', 1, 'Cat'));
    }
}
