<?php

/*
 * A collection of the custom fields to show on an invoice.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\InvoiceCustomField;


/**
 * @extends AbstractReadonlyCollection<InvoiceCustomField>
 */
final readonly class InvoiceCustomFieldCollection extends AbstractReadonlyCollection
{
    public function __construct(InvoiceCustomField ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array<string, mixed>>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }


    public function getType(): string { return InvoiceCustomField::class; }
}
