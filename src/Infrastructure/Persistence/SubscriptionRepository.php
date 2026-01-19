<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionType;
use PDO;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? PDOConnection::getConnection();
    }

    public function save(Subscription $subscription): void
    {
        if ($subscription->getId() === null) {
            $stmt = $this->connection->prepare(
                'INSERT INTO subscriptions (user_id, certification_id, type, state, start_date, end_date, auto_renew) VALUES (:user_id, :certification_id, :type, :state, :start_date, :end_date, :auto_renew)'
            );
            $stmt->execute([
                'user_id' => $subscription->getUserId(),
                'certification_id' => $subscription->getCertificationId(),
                'type' => $subscription->getType()->value,
                'state' => $subscription->getState()->value,
                'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
                'end_date' => $subscription->getEndDate()->format('Y-m-d H:i:s'),
                'auto_renew' => $subscription->isAutoRenew() ? 1 : 0,
            ]);
        } else {
            $stmt = $this->connection->prepare(
                'UPDATE subscriptions SET state = :state, start_date = :start_date, end_date = :end_date, auto_renew = :auto_renew WHERE id = :id'
            );
            $stmt->execute([
                'state' => $subscription->getState()->value,
                'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
                'end_date' => $subscription->getEndDate()->format('Y-m-d H:i:s'),
                'auto_renew' => $subscription->isAutoRenew() ? 1 : 0,
                'id' => $subscription->getId(),
            ]);
        }
    }

    public function findExpiringActiveWithAutoRenew(\DateTimeImmutable $date): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM subscriptions WHERE state = :state AND auto_renew = 1 AND end_date <= :reference'
        );
        $stmt->execute([
            'state' => SubscriptionState::Active->value,
            'reference' => $date->format('Y-m-d H:i:s'),
        ]);

        $rows = $stmt->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    public function findCancelable(\DateTimeImmutable $date): array
    {
        // Find subscriptions that should expire:
        // 1. Cancelled and past end_date
        // 2. Active, NOT auto-renewing, and past end_date
        $stmt = $this->connection->prepare(
            'SELECT * FROM subscriptions 
            WHERE end_date <= :reference 
            AND (
                state = :cancelled 
                OR (state = :active AND auto_renew = 0)
            )'
        );
        $stmt->execute([
            'cancelled' => SubscriptionState::Cancelled->value,
            'active' => SubscriptionState::Active->value,
            'reference' => $date->format('Y-m-d H:i:s'),
        ]);

        $rows = $stmt->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    private function hydrate(array $row): Subscription
    {
        return new Subscription(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['certification_id'],
            SubscriptionType::from($row['type']),
            SubscriptionState::from($row['state']),
            new \DateTimeImmutable($row['start_date']),
            new \DateTimeImmutable($row['end_date']),
            (bool) $row['auto_renew']
        );
    }
}

