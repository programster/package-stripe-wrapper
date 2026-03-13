<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\InvoiceCustomField;

class InvoiceCustomFieldCollection extends AbstractCollection
{
    public function __construct(InvoiceCustomField ...$fields)
    {
        parent::__construct(InvoiceCustomField::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item InvoiceCustomField */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}