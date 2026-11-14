<?php

/*
 * A collection of the discounts to apply to a subscription, or an item on one.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionItemDiscount;


class DiscountCollection extends AbstractCollection
{
    public function __construct(SubscriptionItemDiscount ...$discounts)
    {
        parent::__construct(SubscriptionItemDiscount::class, ...$discounts);
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
            /* @var $item SubscriptionItemDiscount */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
