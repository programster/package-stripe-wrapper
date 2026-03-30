<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Enums\ExceptionInvalidValue;
use Programster\Stripe\Enums\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class BillingCycleAnchorConfig implements Arrayable
{

    public function __construct(
        private int  $dayOfMonth,
        private ?int $month = null,
        private ?int $hour = null,
        private ?int $minute = null,
        private ?int $second = null,
    )
    {
        if ($dayOfMonth < 1 || $dayOfMonth > 31)
        {
            throw new ExceptionInvalidValue("Day of month must be between 1 and 31");
        }

        if ($month < 1 || $month > 12)
        {
            throw new ExceptionInvalidValue("Month must be between 1 and 12");
        }

        if ($hour < 0 || $hour > 23)
        {
            throw new ExceptionInvalidValue("Hour must be between 0 and 23");
        }

        if ($minute < 0 || $minute > 59)
        {
            throw new ExceptionInvalidValue("Minute must be between 0 and 59");
        }

        if ($second < 0 || $second > 59)
        {
            throw new ExceptionInvalidValue("Second must be between 0 and 59");
        }
    }

    public function toArray(): array
    {
        $arrayForm = [
            'day_of_month' => $this->dayOfMonth,
        ];

        if ($this->month !== null) { $arrayForm['month'] = $this->month; }
        if ($this->hour !== null)  { $arrayForm['hour'] = $this->hour;}
        if ($this->minute !== null)  { $arrayForm['minute'] = $this->minute; }
        if ($this->second !== null)  { $arrayForm['second'] = $this->second; }

        return $arrayForm;
    }
}