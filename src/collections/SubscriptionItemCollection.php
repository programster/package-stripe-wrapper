<?php

/*
 * A collection of the items in a subscription.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionItem;


class SubscriptionItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionItem ...$fields)
    {
        parent::__construct(SubscriptionItem::class, ...$fields);
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
            /* @var $item SubscriptionItem */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
