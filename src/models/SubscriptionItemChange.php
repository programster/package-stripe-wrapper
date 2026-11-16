<?php

/*
 * One change to a subscription's items, for StripeClient::previewSubscriptionChange(): an existing item's price or
 * quantity changed, an item removed, or a new item added. Create one with createUpdateUsingItemId() (or
 * createUpdateUsingItem()), createRemovalUsingItemId() (or createRemovalUsingItem()), or createAddition().
 * https://docs.stripe.com/api/invoices/create_preview#create_create_preview-subscription_details-items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;
use Programster\Stripe\Responses\SubscriptionItem as SubscriptionItemResponse;


/**
 * @phpstan-import-type SubscriptionItemArray from SubscriptionItem
 * @phpstan-import-type PriceDataForSubscriptionItemArray from PriceDataForSubscriptionItem
 * @phpstan-type SubscriptionItemChangeArray array{
 *     id: string,
 *     price?: string,
 *     price_data?: PriceDataForSubscriptionItemArray,
 *     quantity?: int
 * }|array{id: string, deleted: true}|SubscriptionItemArray
 */
final readonly class SubscriptionItemChange
{
    private ?string $m_subscriptionItemId;

    /** @var SubscriptionItemChangeArray */
    private array $m_stripeParams;


    /**
     * This is private, so use one of the create...() methods.
     * @param string|null $subscriptionItemId - the ID of the existing item being changed, or null for a new item.
     * @param SubscriptionItemChangeArray $stripeParams - the change, in the form Stripe takes.
     */
    private function __construct(?string $subscriptionItemId, array $stripeParams)
    {
        $this->m_subscriptionItemId = $subscriptionItemId;
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * Change an existing item's price, quantity, or both.
     *
     * @param string $subscriptionItemId - the item's ID, e.g. "si_123".
     *
     * @param SubscriptionItemPrice|null $price - optionally, the price to change it to. Leave as null to keep its
     * price.
     *
     * @param int|null $quantity - optionally, the quantity to change it to. Leave as null to keep its quantity.
     *
     * @return SubscriptionItemChange
     *
     * @throws ExceptionInvalidValue - if $subscriptionItemId isn't a subscription item's ID, or neither a price nor a
     * quantity is given, as that wouldn't change anything.
     */
    public static function createUpdateUsingItemId(
        string $subscriptionItemId,
        ?SubscriptionItemPrice $price = null,
        ?int $quantity = null,
    ) : SubscriptionItemChange
    {
        StripeIds::checkSubscriptionItemId($subscriptionItemId);

        if ($price === null && $quantity === null)
        {
            throw new ExceptionInvalidValue(
                "Changing subscription item {$subscriptionItemId} needs a new price, a new quantity, or both."
            );
        }

        $params = ['id' => $subscriptionItemId];

        if ($price !== null) { $params = array_merge($params, $price->getStripeParams()); }
        if ($quantity !== null) { $params['quantity'] = $quantity; }

        return new SubscriptionItemChange($subscriptionItemId, $params);
    }


    /**
     * Change an existing item, such as one from Subscription::getItems(), as createUpdateUsingItemId() does.
     *
     * @param SubscriptionItemResponse $item - the item.
     *
     * @param SubscriptionItemPrice|null $price - optionally, the price to change it to. Leave as null to keep its
     * price.
     *
     * @param int|null $quantity - optionally, the quantity to change it to. Leave as null to keep its quantity.
     *
     * @return SubscriptionItemChange
     *
     * @throws ExceptionInvalidValue - if neither a price nor a quantity is given, as that wouldn't change anything.
     */
    public static function createUpdateUsingItem(
        SubscriptionItemResponse $item,
        ?SubscriptionItemPrice $price = null,
        ?int $quantity = null,
    ) : SubscriptionItemChange
    {
        return self::createUpdateUsingItemId($item->getId(), $price, $quantity);
    }


    /**
     * Remove an existing item from the subscription.
     * @param string $subscriptionItemId - the item's ID, e.g. "si_123".
     * @return SubscriptionItemChange
     * @throws ExceptionInvalidValue - if $subscriptionItemId isn't a subscription item's ID.
     */
    public static function createRemovalUsingItemId(string $subscriptionItemId) : SubscriptionItemChange
    {
        StripeIds::checkSubscriptionItemId($subscriptionItemId);
        return new SubscriptionItemChange($subscriptionItemId, ['id' => $subscriptionItemId, 'deleted' => true]);
    }


    /**
     * Remove an existing item, such as one from Subscription::getItems(), from the subscription.
     * @param SubscriptionItemResponse $item - the item.
     * @return SubscriptionItemChange
     */
    public static function createRemovalUsingItem(SubscriptionItemResponse $item) : SubscriptionItemChange
    {
        return self::createRemovalUsingItemId($item->getId());
    }


    /**
     * Add a new item to the subscription.
     * @param SubscriptionItem $item - the new item, as it would be given to createSubscription().
     * @return SubscriptionItemChange
     */
    public static function createAddition(SubscriptionItem $item) : SubscriptionItemChange
    {
        return new SubscriptionItemChange(null, $item->toArray());
    }


    /**
     * Get the ID of the existing item being changed, or null if it's a new item.
     * @return string|null
     * @internal - for SubscriptionItemChangeCollection, to check an item isn't changed twice.
     */
    public function getSubscriptionItemId() : ?string
    {
        return $this->m_subscriptionItemId;
    }


    /**
     * Get the change in the form Stripe takes.
     * @return SubscriptionItemChangeArray
     */
    public function toArray() : array
    {
        return $this->m_stripeParams;
    }
}
