<?php
declare(strict_types=1);

namespace App\Domain\Certification;

final class Course
{
    private ?int $id;
    private string $title;
    private int $durationHours;
    private string $category;

    public function __construct(?int $id, string $title, int $durationHours, string $category)
    {
        if ($durationHours <= 0) {
            throw new \InvalidArgumentException('Course duration must be positive');
        }

        $this->id = $id;
        $this->title = $title;
        $this->durationHours = $durationHours;
        $this->category = $category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new \LogicException('Cannot change course ID once set');
        }
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDurationHours(): int
    {
        return $this->durationHours;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}

