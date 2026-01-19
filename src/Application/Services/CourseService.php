<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Certification\Course;
use App\Domain\Repository\CourseRepositoryInterface;

final class CourseService
{
    private CourseRepositoryInterface $courseRepository;

    public function __construct(CourseRepositoryInterface $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function createCourse(string $title, int $durationHours, string $category): Course
    {
        $course = new Course(null, $title, $durationHours, $category);
        $this->courseRepository->save($course);
        return $course;
    }

    public function getCourse(int $id): Course
    {
        $course = $this->courseRepository->findById($id);
        if (!$course) {
            throw new \RuntimeException("Course not found");
        }
        return $course;
    }

    public function getAllCourses(): array
    {
        return $this->courseRepository->findAll();
    }
}
