<?php

/*
 * A readonly collection of InvoiceLineItem response models, e.g. the lines of an invoice preview.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<InvoiceLineItem>
 */
final readonly class InvoiceLineItemCollection extends AbstractReadonlyCollection
{
    public function __construct(InvoiceLineItem ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return InvoiceLineItem::class; }
}
