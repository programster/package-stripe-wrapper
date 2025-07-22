<?php

/*
 * An object to encapsulate all of the trial settings, such as trial_end, trial_period_dyas, and trial_settings.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;
use Programster\Stripe\Interfaces\Arrayable;

readonly class TrialConfig
{
    /**
     * The parameters used to automatically create a Transfer when the payment succeeds.
     * For more information see https://docs.stripe.com/connect/charges
     * Docs for this object: https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-transfer_data
     * @param string $stripeAccountId - If specified, successful charges will be attributed to the destination account
     * for tax reporting, and the funds from charges will be transferred to the destination account. The ID of the
     * resulting transfer will be returned on the successful charge’s transfer field.
     * @param int $amount - The amount that will be transferred automatically when a charge succeeds. Don't forget that
     * this is in pennies/cents, not Pounds/Dollars.
     */
    public function __construct(
        private ?int $numDays = null,
        private ?int $endTimestamp = null,
        private ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
    )
    {

    }

    public static function createForEndTimestamp(
        int $unixTimestamp,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ): TrialConfig
    {
        return new TrialConfig(null, $unixTimestamp, $endTrialBehaviorIfMissingPaymentMethod);
    }

    public static function createForNumDays(
        int $numDays,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ): TrialConfig
    {
        return new TrialConfig($numDays, null, $endTrialBehaviorIfMissingPaymentMethod);
    }


    public function getStripeParams() : array
    {
        $params = [];

        if ($this->numDays !== null) { $params['trial_period_days'] = $this->numDays; }
        if ($this->endTimestamp !== null) { $params['trial_end'] = $this->endTimestamp; }

        if ($this->endTrialBehaviorIfMissingPaymentMethod !== null)
        {
            $params['trial_settings'] = array('end_behavior' => $this->endTrialBehaviorIfMissingPaymentMethod->value);
        }

        return $params;
    }
}