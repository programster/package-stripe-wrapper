<?php

/*
 * A readonly collection of Customer response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<Customer>
 */
final readonly class CustomerCollection extends AbstractReadonlyCollection
{
    public function __construct(Customer ...$elements)
    {
        parent::__construct(...$elements);
    }
}
