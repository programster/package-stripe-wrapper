<?php

/*
 * A readonly collection of SubscriptionItem response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<SubscriptionItem>
 */
final readonly class SubscriptionItemCollection extends AbstractReadonlyCollection
{
    public function __construct(SubscriptionItem ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return SubscriptionItem::class; }
}
