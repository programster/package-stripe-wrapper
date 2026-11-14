<?php

/*
 * A period of time between two unix timestamps, for filtering lists (e.g. by when something was created).
 * https://docs.stripe.com/api/subscriptions/list#list_subscriptions-created
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class TimePeriod implements Arrayable
{
    private int $m_start;
    private int $m_end;
    private bool $m_isStartInclusive;
    private bool $m_isEndInclusive;


    /**
     * @param int $start - unix timestamp for the start of the time period.
     * @param int $end - unix timestamp for the end of the time period.
     * @param bool $isStartInclusive - whether start timestamp is inclusive or not.
     * @param bool $isEndInclusive - whether end timestamp is inclusive or not.
     */
    public function __construct(int $start, int $end, bool $isStartInclusive = true, bool $isEndInclusive = true)
    {
        $this->m_start = $start;
        $this->m_end = $end;
        $this->m_isStartInclusive = $isStartInclusive;
        $this->m_isEndInclusive = $isEndInclusive;
    }


    public function toArray() : array
    {
        $startKey = ($this->m_isStartInclusive) ? 'gte' : 'gt';
        $endKey = ($this->m_isEndInclusive) ? 'lte' : 'lt';
        return [$startKey => $this->m_start, $endKey => $this->m_end];
    }
}
