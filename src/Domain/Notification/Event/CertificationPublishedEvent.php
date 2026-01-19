<?php
declare(strict_types=1);

namespace App\Domain\Notification\Event;

final class CertificationPublishedEvent implements DomainEventInterface
{
    private int $certificationId;

    public function __construct(int $certificationId)
    {
        $this->certificationId = $certificationId;
    }

    public function getCertificationId(): int
    {
        return $this->certificationId;
    }

    public function getName(): string
    {
        return 'certification.published';
    }
}

