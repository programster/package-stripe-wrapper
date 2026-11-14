<?php

/*
 * A collection of the custom fields to show on a checkout page.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\CustomField;


class CustomFieldCollection extends AbstractCollection
{
    public function __construct(CustomField ...$fields)
    {
        parent::__construct(CustomField::class, ...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item CustomField */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
