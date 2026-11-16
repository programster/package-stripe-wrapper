<?php

/*
 * A readonly collection of Invoice response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<Invoice>
 */
final readonly class InvoiceCollection extends AbstractReadonlyCollection
{
    public function __construct(Invoice ...$elements)
    {
        parent::__construct(...$elements);
    }
}
