<?php

/*
 * The period an invoice item covers.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

final readonly class InvoiceItemPeriod implements Arrayable
{
    private PeriodStart $m_start;
    private PeriodEnd $m_end;


    /**
     * Create the period an invoice item covers.
     * @param PeriodStart $start - when the period starts.
     * @param PeriodEnd $end - when the period ends.
     */
    public function __construct(PeriodStart $start, PeriodEnd $end)
    {
        $this->m_start = $start;
        $this->m_end = $end;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'end' => $this->m_end->toArray(),
            'start' => $this->m_start->toArray(),
        ];
    }
}
