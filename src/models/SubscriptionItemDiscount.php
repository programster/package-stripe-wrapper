<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class SubscriptionItemDiscount implements Arrayable
{
    /**
     * A single discount object within a collection. This constructor is private as you need to use one of the
     * static create methods.
     * https://docs.stripe.com/api/subscription_items/update?lang=php#update_subscription_item-discounts
     */
    public function __construct(
        private readonly ?string $couponId,
        private readonly ?string $discountId,
        private readonly ?string $promotionCodeId,
    )
    {

    }


    public function createFromCouponId(string $couponId): SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount($couponId, null, null);
    }


    public function createFromDiscountId(string $discountId): SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount(null, $discountId, null);
    }


    public function createFromPromotionCode(string $promotionCode): SubscriptionItemDiscount
    {
        return new SubscriptionItemDiscount(null, null, $promotionCode);
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->couponId !== null) { $arrayForm['coupon'] = $this->couponId; }
        if ($this->discountId !== null) { $arrayForm['discount'] = $this->discountId; }
        if ($this->promotionCodeId !== null) { $arrayForm['promotion_code'] = $this->promotionCodeId; }

        return $arrayForm;
    }
}