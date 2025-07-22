<?php

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
     * Return the custom fields in a collapsed array form suitable for sending to stripe.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Models\CustomField */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}