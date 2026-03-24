<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\TaxIdDataType;
use Programster\Stripe\Models\InvoiceCustomField;

class CustomerTaxIdDataCollection extends AbstractCollection
{
    public function __construct(TaxIdDataType ...$taxIds)
    {
        parent::__construct(TaxIdDataType::class, ...$taxIds);
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