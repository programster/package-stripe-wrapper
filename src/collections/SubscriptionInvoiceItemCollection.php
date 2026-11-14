<?php

/*
 * A collection of the invoice items to add to a subscription's next invoice.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionInvoiceItem;


class SubscriptionInvoiceItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionInvoiceItem ...$fields)
    {
        parent::__construct(SubscriptionInvoiceItem::class, ...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array<string, mixed>>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item SubscriptionInvoiceItem */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
