<?php

/*
 * An object to encapsulate all of the trial settings, such as trial_end, trial_period_dyas, and trial_settings.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;
use Programster\Stripe\Interfaces\Arrayable;

readonly class SubscriptionTrialConfig
{
    private function __construct(
        private ?int $numDays = null,
        private ?int $endTimestamp = null,
        private ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
        private ?bool $trialFromPlan = null,
    )
    {

    }


    public static function createForEndTimestamp(
        int $unixTimestamp,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ): SubscriptionTrialConfig
    {
        return new SubscriptionTrialConfig(
            null,
            $unixTimestamp,
            $endTrialBehaviorIfMissingPaymentMethod,
            null
        );
    }


    public static function createForNumDays(
        int $numDays,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
        bool $trialFromPlan = null
    ): SubscriptionTrialConfig
    {
        return new SubscriptionTrialConfig(
            $numDays,
            null,
            $endTrialBehaviorIfMissingPaymentMethod,
            $trialFromPlan
        );
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

        if ($this->trialFromPlan !== null)
        {
            $params['trial_from_plan'] = $this->trialFromPlan;
        }

        return $params;
    }
}