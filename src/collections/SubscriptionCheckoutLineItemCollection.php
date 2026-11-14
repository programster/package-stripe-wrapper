<?php

/*
 * A collection of the line items in a subscription checkout session.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;


class SubscriptionCheckoutLineItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionCheckoutLineItem ...$fields)
    {
        parent::__construct(SubscriptionCheckoutLineItem::class, ...$fields);
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
            /* @var $item SubscriptionCheckoutLineItem */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
