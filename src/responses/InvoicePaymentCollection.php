<?php

/*
 * A readonly collection of InvoicePayment response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<InvoicePayment>
 */
final readonly class InvoicePaymentCollection extends AbstractReadonlyCollection
{
    public function __construct(InvoicePayment ...$elements)
    {
        parent::__construct(...$elements);
    }
}
