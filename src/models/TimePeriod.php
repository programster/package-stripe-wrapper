<?php

/*
 * A period of time between two times, for filtering lists (e.g. by when something was created).
 * https://docs.stripe.com/api/subscriptions/list#list_subscriptions-created
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class TimePeriod implements Arrayable
{
    private int $m_start;
    private int $m_end;
    private bool $m_isStartInclusive;
    private bool $m_isEndInclusive;


    /**
     * @param \DateTimeImmutable $start - the start of the time period.
     *
     * @param \DateTimeImmutable $end - the end of the time period.
     *
     * @param bool $isStartInclusive - whether the period includes its start.
     *
     * @param bool $isEndInclusive - whether the period includes its end.
     *
     * @throws ExceptionInvalidValue - if $end is before $start, which nothing could be in.
     */
    public function __construct(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        bool $isStartInclusive = true,
        bool $isEndInclusive = true
    )
    {
        if ($end < $start)
        {
            throw new ExceptionInvalidValue(
                "A time period's end (" . $end->format(DATE_ATOM) . ") can't be before its start (" .
                $start->format(DATE_ATOM) . ")."
            );
        }

        $this->m_start = $start->getTimestamp();
        $this->m_end = $end->getTimestamp();
        $this->m_isStartInclusive = $isStartInclusive;
        $this->m_isEndInclusive = $isEndInclusive;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $startKey = ($this->m_isStartInclusive) ? 'gte' : 'gt';
        $endKey = ($this->m_isEndInclusive) ? 'lte' : 'lt';
        return [$startKey => $this->m_start, $endKey => $this->m_end];
    }
}
