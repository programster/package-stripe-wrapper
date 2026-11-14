<?php

/*
 * A discount to apply to a subscription, or an item on one: a coupon, an existing discount, or a promotion code.
 * Create one with one of the static create methods.
 * https://docs.stripe.com/api/subscription_items/update?lang=php#update_subscription_item-discounts
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


readonly class SubscriptionItemDiscount implements Arrayable
{
    private ?string $m_couponId;
    private ?string $m_discountId;
    private ?string $m_promotionCodeId;


    /**
     * Prefer one of the static create methods, which can't be given the wrong number of IDs. Exactly one of the
     * parameters must be given.
     *
     * @param string|null $couponId - the ID of a coupon to redeem.
     *
     * @param string|null $discountId - the ID of an existing discount to use.
     *
     * @param string|null $promotionCodeId - the ID of a promotion code to redeem.
     *
     * @throws ExceptionInvalidValue - if not exactly one of the IDs is given.
     */
    public function __construct(
        ?string $couponId,
        ?string $discountId,
        ?string $promotionCodeId,
    )
    {
        $numProvided = count(array_filter([$couponId, $discountId, $promotionCodeId], 'is_string'));

        if ($numProvided !== 1)
        {
            throw new ExceptionInvalidValue(
                "A discount needs exactly one of a coupon ID, discount ID or promotion code, but {$numProvided} " .
                "were given."
            );
        }

        $this->m_couponId = $couponId;
        $this->m_discountId = $discountId;
        $this->m_promotionCodeId = $promotionCodeId;
    }


    /**
     * Create a discount that redeems a coupon.
     * @param string $couponId - the ID of the coupon.
     * @return SubscriptionItemDiscount
     */
    public static function createFromCouponId(string $couponId) : SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount($couponId, null, null);
    }


    /**
     * Create a discount that uses an existing discount.
     * @param string $discountId - the ID of the discount.
     * @return SubscriptionItemDiscount
     */
    public static function createFromDiscountId(string $discountId) : SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount(null, $discountId, null);
    }


    /**
     * Create a discount that redeems a promotion code.
     * @param string $promotionCode - the ID of the promotion code.
     * @return SubscriptionItemDiscount
     */
    public static function createFromPromotionCode(string $promotionCode) : SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount(null, null, $promotionCode);
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_couponId !== null) { $arrayForm['coupon'] = $this->m_couponId; }
        if ($this->m_discountId !== null) { $arrayForm['discount'] = $this->m_discountId; }
        if ($this->m_promotionCodeId !== null) { $arrayForm['promotion_code'] = $this->m_promotionCodeId; }

        return $arrayForm;
    }
}
