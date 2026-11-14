<?php

/*
 * The start of an invoice item's period. Create one with one of the static create methods.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period-start
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\PeriodStartType;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class PeriodStart implements Arrayable
{
    private PeriodStartType $m_type;
    private ?int $m_timestamp;


    /**
     * @param PeriodStartType $type - how the start is set.
     * @param int|null $timestamp - the unix timestamp of the start, which is only given for PeriodStartType::TIMESTAMP.
     */
    private function __construct(PeriodStartType $type, ?int $timestamp)
    {
        $this->m_type = $type;
        $this->m_timestamp = $timestamp;
    }


    /**
     * Start the period at the latest start of the subscription's items' current billing periods.
     * @return PeriodStart
     */
    public static function createMaxItemPeriodStart() : PeriodStart
    {
        return new PeriodStart(PeriodStartType::MAX_ITEM_PERIOD_START, null);
    }


    /**
     * Start the period now.
     * @return PeriodStart
     */
    public static function createNow() : PeriodStart
    {
        return new PeriodStart(PeriodStartType::NOW, null);
    }


    /**
     * Start the period at a given time.
     * @param int $timestamp - the unix timestamp of the start.
     * @return PeriodStart
     */
    public static function createTimestamp(int $timestamp) : PeriodStart
    {
        return new PeriodStart(PeriodStartType::TIMESTAMP, $timestamp);
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = ['type' => $this->m_type->value];

        if ($this->m_timestamp !== null)
        {
            $arrayForm['timestamp'] = $this->m_timestamp;
        }

        return $arrayForm;
    }
}
