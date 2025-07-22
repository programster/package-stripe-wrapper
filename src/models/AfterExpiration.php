<?php

/*
 * Create an AfterExpiration object for a checkout session. This configures whether you will allow the user to
 * recover after a session expires, and whether this recovered session should include any promo codes the user
 * may have had.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-after_expiration
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

class AfterExpiration implements Arrayable
{
    private function __construct(private readonly bool $enabled, private readonly ?bool $allowPromotionCodesInRecovery)
    {

    }

    public static function createEnabled(bool $allowPromotionCodesInRecovery)
    {
        return new AfterExpiration(true, $allowPromotionCodesInRecovery);
    }

    public function createDisabled()
    {
        return new AfterExpiration(false, null);
    }


    public function toArray(): array
    {
        $recoveryObj = [
            "enabled" => $this->enabled,
        ];

        if ($this->allowPromotionCodesInRecovery !== null)
        {
            $recoveryObj["allowPromotionCodesInRecovery"] = $this->allowPromotionCodesInRecovery;
        }

        return ["recovery" => $recoveryObj];
    }
}