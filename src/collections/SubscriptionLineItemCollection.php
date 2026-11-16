<?php

/*
 * A collection of the items in a subscription, for StripeClient::createSubscription().
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\SubscriptionItem;


/**
 * @extends AbstractReadonlyCollection<SubscriptionItem>
 *
 * @phpstan-import-type SubscriptionItemArray from SubscriptionItem
 */
final readonly class SubscriptionLineItemCollection extends AbstractReadonlyCollection
{
    public function __construct(SubscriptionItem ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<SubscriptionItemArray>
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
