<?php

/*
 * The price to change a subscription item to, with updateSubscriptionItem(): an existing price, or one created
 * inline. Create one with forPrice() or forPriceData().
 * https://docs.stripe.com/api/subscription_items/update#update_subscription_item-price
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;


final readonly class SubscriptionItemPrice
{
    /** @var array<string, mixed> */
    private array $m_stripeParams;


    /**
     * This is private, so use forPrice() or forPriceData().
     * @param array<string, mixed> $stripeParams - the subscription item's parameters for the price.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * Change to an existing price.
     * @param string $priceId - the price's ID, e.g. "price_123" (or a legacy plan's ID).
     * @return SubscriptionItemPrice
     * @throws ExceptionInvalidValue - if $priceId is empty, or a product's ID.
     */
    public static function forPrice(string $priceId) : SubscriptionItemPrice
    {
        StripeIds::checkPriceId($priceId);
        return new SubscriptionItemPrice(['price' => $priceId]);
    }


    /**
     * Change to a price created inline.
     * @param PriceDataForSubscriptionItem $priceData - the price.
     * @return SubscriptionItemPrice
     */
    public static function forPriceData(PriceDataForSubscriptionItem $priceData) : SubscriptionItemPrice
    {
        return new SubscriptionItemPrice(['price_data' => $priceData->toArray()]);
    }


    /**
     * Get the parameters to add to a subscription item for the price.
     * @return array<string, mixed>
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
