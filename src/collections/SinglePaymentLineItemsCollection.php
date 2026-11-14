<?php

/*
 * A collection of the line items being bought in a one-time payment checkout session.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SinglePaymentLineItem;


class SinglePaymentLineItemsCollection extends AbstractCollection
{
    public function __construct(SinglePaymentLineItem ...$fields)
    {
        parent::__construct(SinglePaymentLineItem::class, ...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item SinglePaymentLineItem */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
