<?php

/*
 * A collection of country codes, e.g. for the countries that shipping addresses may be collected for.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Enums\CountryCode;


/**
 * @extends AbstractReadonlyCollection<CountryCode>
 */
final readonly class CountryCodeCollection extends AbstractReadonlyCollection
{
    public function __construct(CountryCode ...$countryCodes)
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


    public function getType(): string { return CountryCode::class; }
}
