<?php

/*
 * A collection of the line items being bought in a one-time payment checkout session.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\SinglePaymentLineItem;


/**
 * @extends AbstractReadonlyCollection<SinglePaymentLineItem>
 *
 * @phpstan-import-type SinglePaymentLineItemArray from SinglePaymentLineItem
 */
final readonly class SinglePaymentLineItemsCollection extends AbstractReadonlyCollection
{
    public function __construct(SinglePaymentLineItem ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<SinglePaymentLineItemArray>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
