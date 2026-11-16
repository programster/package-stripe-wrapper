<?php

/*
 * A readonly collection of Refund response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<Refund>
 */
final readonly class RefundCollection extends AbstractReadonlyCollection
{
    public function __construct(Refund ...$elements)
    {
        parent::__construct(...$elements);
    }
}
