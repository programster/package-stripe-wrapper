<?php

/*
 * A collection of locales, e.g. for a customer's preferred languages.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\Locale;


class LocaleCollection extends AbstractCollection
{
    public function __construct(Locale ...$countryCodes)
    {
        parent::__construct(Locale::class, ...$countryCodes);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's values.
     * @return list<string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item Locale */
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
