<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class Discount implements Arrayable
{

    /**
     * Create a discount object for a coupon or promotion code. Stripe only allows one to be set.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-discounts
     * @param string $type
     * @param string $value
     */
    private function __construct(private readonly string $type, private readonly string $value)
    {
    }


    public static function createPromoCode(string $value) : Discount
    {
        return new Discount("promotion_code", $value);
    }


    public static function createCoupon(string $value) : Discount
    {
        return new Discount("coupon", $value);
    }


    public function toArray(): array
    {
        return [
            $this->type => $this->value,
        ];
    }
}