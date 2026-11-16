<?php

/*
 * A collection of locales, e.g. for a customer's preferred languages.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Enums\Locale;


/**
 * @extends AbstractReadonlyCollection<Locale>
 */
final readonly class LocaleCollection extends AbstractReadonlyCollection
{
    public function __construct(Locale ...$countryCodes)
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
