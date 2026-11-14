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
    private readonly bool $m_enabled;
    private readonly ?bool $m_allowPromotionCodesInRecovery;


    /**
     * This is private, so use createEnabled() or createDisabled().
     * @param bool $enabled - whether the session can be recovered after it expires.
     * @param bool|null $allowPromotionCodesInRecovery - whether the recovered session allows promotion codes.
     */
    private function __construct(bool $enabled, ?bool $allowPromotionCodesInRecovery)
    {
        $this->m_enabled = $enabled;
        $this->m_allowPromotionCodesInRecovery = $allowPromotionCodesInRecovery;
    }


    /**
     * Allow an expired session to be recovered.
     * @param bool $allowPromotionCodesInRecovery - whether the recovered session should allow promotion codes.
     * @return AfterExpiration
     */
    public static function createEnabled(bool $allowPromotionCodesInRecovery) : AfterExpiration
    {
        return new AfterExpiration(true, $allowPromotionCodesInRecovery);
    }


    /**
     * Don't allow an expired session to be recovered.
     * @return AfterExpiration
     */
    public static function createDisabled() : AfterExpiration
    {
        return new AfterExpiration(false, null);
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $recoveryObj = [
            "enabled" => $this->m_enabled,
        ];

        if ($this->m_allowPromotionCodesInRecovery !== null)
        {
            $recoveryObj["allow_promotion_codes"] = $this->m_allowPromotionCodesInRecovery;
        }

        return ["recovery" => $recoveryObj];
    }
}
