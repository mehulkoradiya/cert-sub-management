<?php
declare(strict_types=1);

namespace App\Domain\Subscription;

enum SubscriptionType: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}

