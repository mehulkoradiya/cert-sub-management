<?php
declare(strict_types=1);

namespace App\Domain\Subscription;

enum SubscriptionState: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}

