<?php

/*
 * A collection of the consent levels a saved payment method may have been saved with, for it to be shown again.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Enums\AllowRedisplayFilters;


/**
 * @extends AbstractReadonlyCollection<AllowRedisplayFilters>
 */
final readonly class AllowRedisplayFiltersCollection extends AbstractReadonlyCollection
{
    public function __construct(AllowRedisplayFilters ...$filters)
    {
        parent::__construct(...$filters);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's values.
     * @return list<string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
