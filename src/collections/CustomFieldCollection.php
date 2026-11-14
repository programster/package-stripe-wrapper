<?php

/*
 * A collection of the custom fields to show on a checkout page.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\CustomField;


/**
 * @extends AbstractReadonlyCollection<CustomField>
 */
final readonly class CustomFieldCollection extends AbstractReadonlyCollection
{
    public function __construct(CustomField ...$fields)
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


    public function getType(): string { return CustomField::class; }
}
