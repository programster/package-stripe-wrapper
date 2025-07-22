<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\RecurringInterval;

class RecurringConfig implements \Programster\Stripe\Interfaces\Arrayable
{
    /**
     * Specifies billing frequency.
     * @param RecurringInterval $interval - Specifies billing frequency. Either day, week, month or year.
     * @param int $intervalCount - The number of intervals between subscription billings. For example, interval=month
     * and interval_count=3 bills every 3 months. Maximum of three years interval allowed (3 years, 36 months, or
     * 156 weeks).
     */
    public function __construct(private readonly RecurringInterval $interval, private readonly int $intervalCount)
    {

    }

    public function toArray(): array
    {
        return [
            'interval' => $this->interval->value,
            'interval_count' => $this->intervalCount,
        ];
    }
}