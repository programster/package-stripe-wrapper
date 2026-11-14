<?php

/*
 * The end of an invoice item's period. Create one with one of the static create methods.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period-end
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\PeriodEndType;
use Programster\Stripe\Interfaces\Arrayable;


readonly class PeriodEnd implements Arrayable
{
    private PeriodEndType $m_type;
    private ?int $m_timestamp;


    /**
     * @param PeriodEndType $type - how the end is set.
     * @param int|null $timestamp - the unix timestamp of the end, which is only given for PeriodEndType::TIMESTAMP.
     */
    private function __construct(PeriodEndType $type, ?int $timestamp)
    {
        $this->m_type = $type;
        $this->m_timestamp = $timestamp;
    }


    /**
     * End the period at the earliest end of the subscription's items' current billing periods.
     * @return PeriodEnd
     */
    public static function createMinItemPeriodEnd() : PeriodEnd
    {
        return new PeriodEnd(PeriodEndType::MIN_ITEM_PERIOD_END, null);
    }


    /**
     * End the period at a given time.
     * @param int $timestamp - the unix timestamp of the end.
     * @return PeriodEnd
     */
    public static function createTimestamp(int $timestamp) : PeriodEnd
    {
        return new PeriodEnd(PeriodEndType::TIMESTAMP, $timestamp);
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
