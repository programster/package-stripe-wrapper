<?php

/*
 * A collection of the discounts to apply to a subscription, or an item on one.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\SubscriptionItemDiscount;


/**
 * @extends AbstractReadonlyCollection<SubscriptionItemDiscount>
 */
final readonly class DiscountCollection extends AbstractReadonlyCollection
{
    public function __construct(SubscriptionItemDiscount ...$discounts)
    {
        parent::__construct(...$discounts);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array<string, mixed>>
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


    public function getType(): string { return SubscriptionItemDiscount::class; }
}
