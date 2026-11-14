<?php

/*
 * A coupon or promotion code to apply to a checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-discounts
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\DiscountType;
use Programster\Stripe\Interfaces\Arrayable;

readonly class Discount implements Arrayable
{
    private DiscountType $m_type;
    private string $m_value;


    /**
     * Create a discount object for a coupon or promotion code. Stripe only allows one to be set. This is private, so
     * use createPromoCode() or createCoupon().
     *
     * @param DiscountType $type - whether this is a promotion code or a coupon.
     *
     * @param string $value - the ID of the promotion code or coupon.
     */
    private function __construct(DiscountType $type, string $value)
    {
        $this->m_type = $type;
        $this->m_value = $value;
    }


    /**
     * Apply a promotion code.
     * @param string $value - the ID of the promotion code.
     * @return Discount
     */
    public static function createPromoCode(string $value) : Discount
    {
        return new Discount(DiscountType::PROMOTION_CODE, $value);
    }


    /**
     * Apply a coupon.
     * @param string $value - the ID of the coupon.
     * @return Discount
     */
    public static function createCoupon(string $value) : Discount
    {
        return new Discount(DiscountType::COUPON, $value);
    }


    public function toArray() : array
    {
        return [
            $this->m_type->value => $this->m_value,
        ];
    }
}
