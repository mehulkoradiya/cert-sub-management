<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Certification\Course;
use App\Domain\Repository\CourseRepositoryInterface;
use PDO;

final class CourseRepository implements CourseRepositoryInterface
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = PDOConnection::getConnection();
    }

    public function save(Course $course): void
    {
        if ($course->getId() === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO courses (title, duration_hours, category) VALUES (:title, :duration, :category)'
            );
            $stmt->execute([
                ':title' => $course->getTitle(),
                ':duration' => $course->getDurationHours(),
                ':category' => $course->getCategory(),
            ]);
            $course->setId((int) $this->connection->lastInsertId());
        } else {
            $stmt = $this->connection->prepare(
                'UPDATE courses SET title = :title, duration_hours = :duration, category = :category WHERE id = :id'
            );
            $stmt->execute([
                ':id' => $course->getId(),
                ':title' => $course->getTitle(),
                ':duration' => $course->getDurationHours(),
                ':category' => $course->getCategory(),
            ]);
        }
    }

    public function findById(int $id): ?Course
    {
        $stmt = $this->connection->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Course(
            (int) $row['id'],
            $row['title'],
            (int) $row['duration_hours'],
            $row['category']
        );
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM courses ORDER BY title ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $courses = [];
        foreach ($rows as $row) {
            $courses[] = new Course(
                (int) $row['id'],
                $row['title'],
                (int) $row['duration_hours'],
                $row['category']
            );
        }

        return $courses;
    }
}
