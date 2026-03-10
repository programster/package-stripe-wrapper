<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class TimePeriod implements Arrayable
{
    /**
     * @param int $start - unix timestamp for the start of the time period.
     * @param int $end - unix timestamp for the end of the time period.
     * @param bool $isStartInclusive - whether start timestamp is inclusive or not.
     * @param bool $isEndInclusive - whether end timestamp is inclusive or not.
     */
    public function __construct(
        private int  $start,
        private int  $end,
        private bool $isStartInclusive=true,
        private bool $isEndInclusive=true
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->isStartInclusive)
        {
            $arrayForm['gt'] = $this->start;
        }
        else
        {
            $arrayForm['gte'] = $this->start;
        }

        if ($this->isEndInclusive)
        {
            $arrayForm['lt'] = $this->end;
        }
        else
        {
            $arrayForm['lte'] = $this->end;
        }

        return $arrayForm;
    }
}