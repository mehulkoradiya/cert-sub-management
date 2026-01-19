<?php
declare(strict_types=1);

namespace App\Domain\Notification\Event;

interface DomainEventInterface
{
    public function getName(): string;
}

