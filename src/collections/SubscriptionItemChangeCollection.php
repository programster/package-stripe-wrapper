<?php

/*
 * The changes to a subscription's items, for StripeClient::previewSubscriptionChange(). Each existing item can only be
 * changed once.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Exceptions\ExceptionDuplicateKey;
use Programster\Stripe\Models\SubscriptionItemChange;


/**
 * @extends AbstractReadonlyCollection<SubscriptionItemChange>
 *
 * @phpstan-import-type SubscriptionItemChangeArray from SubscriptionItemChange
 */
final readonly class SubscriptionItemChangeCollection extends AbstractReadonlyCollection
{
    /**
     * @param SubscriptionItemChange ...$changes - the changes. Each existing item can only be changed once.
     *
     * @throws ExceptionDuplicateKey - if an existing item is changed more than once, naming the item's ID. Put both
     * changes in one, e.g. a new price and a new quantity.
     */
    public function __construct(SubscriptionItemChange ...$changes)
    {
        $changedItemIds = [];

        foreach ($changes as $change)
        {
            $itemId = $change->getSubscriptionItemId();

            if ($itemId !== null && array_key_exists($itemId, $changedItemIds))
            {
                throw new ExceptionDuplicateKey($itemId);
            }

            if ($itemId !== null)
            {
                $changedItemIds[$itemId] = true;
            }
        }

        parent::__construct(...$changes);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each change's array form.
     * @return list<SubscriptionItemChangeArray>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $change)
        {
            $stripeArrayForm[] = $change->toArray();
        }

        return $stripeArrayForm;
    }
}
