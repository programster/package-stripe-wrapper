<?php

/*
 * A collection of the line items in a subscription checkout session.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;


/**
 * @extends AbstractReadonlyCollection<SubscriptionCheckoutLineItem>
 *
 * @phpstan-import-type SubscriptionCheckoutLineItemArray from SubscriptionCheckoutLineItem
 */
final readonly class SubscriptionCheckoutLineItemCollection extends AbstractReadonlyCollection
{
    public function __construct(SubscriptionCheckoutLineItem ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<SubscriptionCheckoutLineItemArray>
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


    public function getType(): string { return SubscriptionCheckoutLineItem::class; }
}
