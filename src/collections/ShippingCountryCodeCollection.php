<?php

/*
 * A collection of the countries that Checkout may collect shipping addresses for.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Enums\ShippingCountryCode;


/**
 * @extends AbstractReadonlyCollection<ShippingCountryCode>
 */
final readonly class ShippingCountryCodeCollection extends AbstractReadonlyCollection
{
    public function __construct(ShippingCountryCode ...$countryCodes)
    {
        parent::__construct(...$countryCodes);
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
