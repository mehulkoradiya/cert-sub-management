<?php
declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Domain\Exception\ValidationException;

final class Subscription
{
    private ?int $id;
    private int $userId;
    private int $certificationId;
    private SubscriptionType $type;
    private SubscriptionState $state;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private bool $autoRenew;

    public function __construct(
        ?int $id,
        int $userId,
        int $certificationId,
        SubscriptionType $type,
        SubscriptionState $state,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        bool $autoRenew
    ) {
        if ($endDate <= $startDate) {
            throw new ValidationException('Subscription end date must be after start date');
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->certificationId = $certificationId;
        $this->type = $type;
        $this->state = $state;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->autoRenew = $autoRenew;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCertificationId(): int
    {
        return $this->certificationId;
    }

    public function getType(): SubscriptionType
    {
        return $this->type;
    }

    public function getState(): SubscriptionState
    {
        return $this->state;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function activate(): void
    {
        if ($this->state === SubscriptionState::Cancelled || $this->state === SubscriptionState::Expired) {
            throw new ValidationException('Cancelled or expired subscription cannot be activated');
        }
        $this->state = SubscriptionState::Active;
    }

    public function pause(): void
    {
        if ($this->state !== SubscriptionState::Active) {
            throw new ValidationException('Only active subscriptions can be paused');
        }
        $this->state = SubscriptionState::Paused;
    }

    public function cancel(): void
    {
        if ($this->state === SubscriptionState::Expired) {
            throw new ValidationException('Expired subscription cannot be cancelled');
        }
        $this->state = SubscriptionState::Cancelled;
    }

    public function expire(): void
    {
        $this->state = SubscriptionState::Expired;
    }

    public function renew(): void
    {
        if (!$this->autoRenew) {
            throw new ValidationException('Subscription is not set to auto renew');
        }

        if (!in_array($this->state, [SubscriptionState::Active, SubscriptionState::Paused], true)) {
            throw new ValidationException('Only active or paused subscriptions can be renewed');
        }

        $this->state = SubscriptionState::Active;
        $this->startDate = $this->endDate;

        if ($this->type === SubscriptionType::Monthly) {
            $this->endDate = $this->endDate->modify('+1 month');
        } else {
            $this->endDate = $this->endDate->modify('+1 year');
        }
    }
}

