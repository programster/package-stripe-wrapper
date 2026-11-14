<?php

/*
 * A collection of country codes, e.g. for the countries that shipping addresses may be collected for.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\CountryCode;


class CountryCodeCollection extends AbstractCollection
{
    public function __construct(CountryCode ...$countryCodes)
    {
        parent::__construct(CountryCode::class, ...$countryCodes);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's values.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item CountryCode */
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
