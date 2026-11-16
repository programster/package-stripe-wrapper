<?php

/*
 * A readonly collection of Subscription response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<Subscription>
 */
final readonly class SubscriptionCollection extends AbstractReadonlyCollection
{
    public function __construct(Subscription ...$elements)
    {
        parent::__construct(...$elements);
    }
}
