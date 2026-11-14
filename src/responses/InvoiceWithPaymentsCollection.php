<?php

/*
 * A readonly collection of InvoiceWithPayments response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<InvoiceWithPayments>
 */
final readonly class InvoiceWithPaymentsCollection extends AbstractReadonlyCollection
{
    public function __construct(InvoiceWithPayments ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return InvoiceWithPayments::class; }
}
