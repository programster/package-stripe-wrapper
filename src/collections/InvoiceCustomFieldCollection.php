<?php

/*
 * A collection of the custom fields to show on an invoice.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\InvoiceCustomField;


class InvoiceCustomFieldCollection extends AbstractCollection
{
    public function __construct(InvoiceCustomField ...$fields)
    {
        parent::__construct(InvoiceCustomField::class, ...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array<string, mixed>>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item InvoiceCustomField */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
