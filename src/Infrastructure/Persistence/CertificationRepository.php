<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Certification\Certification;
use App\Domain\Certification\CertificationStatus;
use App\Domain\Certification\Course;
use App\Domain\Certification\RequirementArea;
use App\Domain\Certification\RequirementType;
use App\Domain\Certification\Specification\AlwaysSatisfiedSpecification;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\CertificationRepositoryInterface;
use PDO;

final class CertificationRepository implements CertificationRepositoryInterface
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? PDOConnection::getConnection();
    }

    public function save(Certification $certification): void
    {
        $this->connection->beginTransaction();

        try {
            if ($certification->getId() === null) {
                $stmt = $this->connection->prepare(
                    'INSERT INTO certifications (name, description, status) VALUES (:name, :description, :status)'
                );
                $stmt->execute([
                    'name' => $certification->getName(),
                    'description' => $certification->getDescription(),
                    'status' => $certification->getStatus()->value,
                ]);

                $id = (int) $this->connection->lastInsertId();
                $certification->setId($id);
            } else {
                $stmt = $this->connection->prepare(
                    'UPDATE certifications SET name = :name, description = :description, status = :status WHERE id = :id'
                );
                $stmt->execute([
                    'name' => $certification->getName(),
                    'description' => $certification->getDescription(),
                    'status' => $certification->getStatus()->value,
                    'id' => $certification->getId(),
                ]);

                // Delete existing links first
                $deleteLinksStmt = $this->connection->prepare(
                    'DELETE FROM certification_area_courses WHERE certification_id = :certification_id'
                );
                $deleteLinksStmt->execute(['certification_id' => $certification->getId()]);

                // Delete existing areas
                $deleteAreasStmt = $this->connection->prepare(
                    'DELETE FROM requirement_areas WHERE certification_id = :certification_id'
                );
                $deleteAreasStmt->execute(['certification_id' => $certification->getId()]);
            }

            foreach ($certification->getRequirementAreas() as $area) {
                $this->insertRequirementArea($certification, $area);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?Certification
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, description, status FROM certifications WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $status = CertificationStatus::from($row['status']);
        $certification = new Certification(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['description'],
            $status
        );

        $areasStmt = $this->connection->prepare(
            'SELECT name, requirement_type, requirement_value FROM requirement_areas WHERE certification_id = :certification_id'
        );
        $areasStmt->execute(['certification_id' => $id]);

        $areas = $areasStmt->fetchAll(PDO::FETCH_ASSOC);
        $requirementAreas = [];

        foreach ($areas as $areaRow) {
            $area = new RequirementArea(
                (string) $areaRow['name'],
                RequirementType::from($areaRow['requirement_type']),
                (int) $areaRow['requirement_value']
            );

            // Hydrate courses for this area
            $this->hydrateCourses($id, $area);

            $requirementAreas[] = $area;
        }

        // Use reflection to set requirementAreas to bypass validation logic (which prevents modification of active certs)
        $reflection = new \ReflectionClass($certification);
        $property = $reflection->getProperty('requirementAreas');
        $property->setAccessible(true);
        $property->setValue($certification, $requirementAreas);

        return $certification;
    }

    private function insertRequirementArea(Certification $certification, RequirementArea $area): void
    {
        if ($certification->getId() === null) {
            throw new DomainException('Certification id must be set before saving requirement areas');
        }

        $stmt = $this->connection->prepare(
            'INSERT INTO requirement_areas (certification_id, name, requirement_type, requirement_value) VALUES (:certification_id, :name, :requirement_type, :requirement_value)'
        );
        $stmt->execute([
            'certification_id' => $certification->getId(),
            'name' => $area->getName(),
            'requirement_type' => $area->getRequirementType()->value,
            'requirement_value' => $area->getRequirementValue(),
        ]);

        // Insert courses
        foreach ($area->getCourses() as $course) {
            $linkStmt = $this->connection->prepare(
                'INSERT INTO certification_area_courses (certification_id, area_name, course_id) VALUES (:certification_id, :area_name, :course_id)'
            );
            $linkStmt->execute([
                'certification_id' => $certification->getId(),
                'area_name' => $area->getName(),
                'course_id' => $course->getId(),
            ]);
        }
    }

    private function hydrateCourses(int $certificationId, RequirementArea $area): void
    {
        $sql = '
            SELECT c.* 
            FROM courses c
            JOIN certification_area_courses cac ON c.id = cac.course_id
            WHERE cac.certification_id = :certification_id AND cac.area_name = :area_name
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'certification_id' => $certificationId,
            'area_name' => $area->getName(),
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $spec = new AlwaysSatisfiedSpecification();

        foreach ($rows as $row) {
            $course = new Course(
                (int) $row['id'],
                $row['title'],
                (int) $row['duration_hours'],
                $row['category']
            );
            $area->addCourse($course, $spec);
        }
    }
}
