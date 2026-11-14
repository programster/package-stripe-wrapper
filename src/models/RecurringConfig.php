<?php

/*
 * How often a recurring price is charged.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data-recurring
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class RecurringConfig implements Arrayable
{
    private readonly RecurringInterval $m_interval;
    private readonly int $m_intervalCount;


    /**
     * Specifies billing frequency.
     *
     * @param RecurringInterval $interval - Specifies billing frequency. Either day, week, month or year.
     *
     * @param int $intervalCount - The number of intervals between subscription billings. For example, interval=month
     * and interval_count=3 bills every 3 months. Maximum of three years interval allowed (3 years, 36 months, or
     * 156 weeks).
     *
     * @throws ExceptionInvalidValue - if $intervalCount is less than 1.
     */
    public function __construct(RecurringInterval $interval, int $intervalCount)
    {
        if ($intervalCount < 1)
        {
            throw new ExceptionInvalidValue("The interval count must be at least 1, but {$intervalCount} was given.");
        }

        $this->m_interval = $interval;
        $this->m_intervalCount = $intervalCount;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'interval' => $this->m_interval->value,
            'interval_count' => $this->m_intervalCount,
        ];
    }
}
