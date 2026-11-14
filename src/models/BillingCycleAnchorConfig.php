<?php

/*
 * When a subscription's billing cycle should be anchored to, e.g. the 1st of each month.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor_config
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;

final readonly class BillingCycleAnchorConfig implements Arrayable
{
    private int $m_dayOfMonth;
    private ?int $m_month;
    private ?int $m_hour;
    private ?int $m_minute;
    private ?int $m_second;


    /**
     * Anchor the billing cycle to the next occurrence of a day of the month (and optionally month and time, in UTC).
     *
     * @param int $dayOfMonth - the day of the month, from 1 to 31. In a shorter month, the last day is used.
     *
     * @param int|null $month - optionally, the month to start full cycle billing periods, from 1 to 12.
     *
     * @param int|null $hour - optionally, the hour of the day, from 0 to 23.
     *
     * @param int|null $minute - optionally, the minute of the hour, from 0 to 59.
     *
     * @param int|null $second - optionally, the second of the minute, from 0 to 59.
     *
     * @throws ExceptionInvalidValue - if any value is out of its range.
     */
    public function __construct(
        int  $dayOfMonth,
        ?int $month = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
    )
    {
        if ($dayOfMonth < 1 || $dayOfMonth > 31)
        {
            throw new ExceptionInvalidValue("Day of month must be between 1 and 31");
        }

        if ($month !== null && ($month < 1 || $month > 12))
        {
            throw new ExceptionInvalidValue("Month must be between 1 and 12");
        }

        if ($hour !== null && ($hour < 0 || $hour > 23))
        {
            throw new ExceptionInvalidValue("Hour must be between 0 and 23");
        }

        if ($minute !== null && ($minute < 0 || $minute > 59))
        {
            throw new ExceptionInvalidValue("Minute must be between 0 and 59");
        }

        if ($second !== null && ($second < 0 || $second > 59))
        {
            throw new ExceptionInvalidValue("Second must be between 0 and 59");
        }

        $this->m_dayOfMonth = $dayOfMonth;
        $this->m_month = $month;
        $this->m_hour = $hour;
        $this->m_minute = $minute;
        $this->m_second = $second;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'day_of_month' => $this->m_dayOfMonth,
        ];

        if ($this->m_month !== null) { $arrayForm['month'] = $this->m_month; }
        if ($this->m_hour !== null) { $arrayForm['hour'] = $this->m_hour; }
        if ($this->m_minute !== null) { $arrayForm['minute'] = $this->m_minute; }
        if ($this->m_second !== null) { $arrayForm['second'] = $this->m_second; }

        return $arrayForm;
    }
}
