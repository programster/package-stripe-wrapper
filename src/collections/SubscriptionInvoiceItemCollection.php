<?php

/*
 * A collection of the invoice items to add to a subscription's next invoice.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\SubscriptionInvoiceItem;


/**
 * @extends AbstractReadonlyCollection<SubscriptionInvoiceItem>
 *
 * @phpstan-import-type SubscriptionInvoiceItemArray from SubscriptionInvoiceItem
 */
final readonly class SubscriptionInvoiceItemCollection extends AbstractReadonlyCollection
{
    public function __construct(SubscriptionInvoiceItem ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<SubscriptionInvoiceItemArray>
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


    public function getType(): string { return SubscriptionInvoiceItem::class; }
}
