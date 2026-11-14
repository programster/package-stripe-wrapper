<?php

/*
 * An object to encapsulate all of the trial settings, such as trial_end, trial_period_days, and trial_settings, plus
 * whether to use the trial set on the plan. Create one with one of the static create methods.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;


readonly class SubscriptionTrialConfig
{
    private ?int $m_numDays;
    private ?int $m_endTimestamp;
    private ?EndTrialBehaviorIfMissingPaymentMethod $m_endTrialBehaviorIfMissingPaymentMethod;
    private ?bool $m_trialFromPlan;


    /**
     * @param int|null $numDays - the number of days the trial lasts, or null if it ends at $endTimestamp.
     *
     * @param int|null $endTimestamp - the unix timestamp the trial ends at, or null if it lasts $numDays.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     *
     * @param bool|null $trialFromPlan - whether to use the trial period set on the plan.
     */
    private function __construct(
        ?int $numDays = null,
        ?int $endTimestamp = null,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
        ?bool $trialFromPlan = null,
    )
    {
        $this->m_numDays = $numDays;
        $this->m_endTimestamp = $endTimestamp;
        $this->m_endTrialBehaviorIfMissingPaymentMethod = $endTrialBehaviorIfMissingPaymentMethod;
        $this->m_trialFromPlan = $trialFromPlan;
    }


    /**
     * Create a trial that ends at a given time.
     *
     * @param int $unixTimestamp - the unix timestamp the trial ends at.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     *
     * @return SubscriptionTrialConfig
     */
    public static function createForEndTimestamp(
        int $unixTimestamp,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ) : SubscriptionTrialConfig
    {
        return new SubscriptionTrialConfig(null, $unixTimestamp, $endTrialBehaviorIfMissingPaymentMethod, null);
    }


    /**
     * Create a trial that lasts a number of days.
     *
     * @param int $numDays - the number of days the trial lasts.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     *
     * @param bool|null $trialFromPlan - whether to use the trial period set on the plan.
     *
     * @return SubscriptionTrialConfig
     */
    public static function createForNumDays(
        int $numDays,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
        ?bool $trialFromPlan = null
    ) : SubscriptionTrialConfig
    {
        return new SubscriptionTrialConfig($numDays, null, $endTrialBehaviorIfMissingPaymentMethod, $trialFromPlan);
    }


    /**
     * Get the trial's settings, as the parameters Stripe takes for them.
     * @return array
     */
    public function getStripeParams() : array
    {
        $params = [];

        if ($this->m_numDays !== null) { $params['trial_period_days'] = $this->m_numDays; }
        if ($this->m_endTimestamp !== null) { $params['trial_end'] = $this->m_endTimestamp; }

        if ($this->m_endTrialBehaviorIfMissingPaymentMethod !== null)
        {
            $params['trial_settings'] = ['end_behavior' => $this->m_endTrialBehaviorIfMissingPaymentMethod->value];
        }

        if ($this->m_trialFromPlan !== null)
        {
            $params['trial_from_plan'] = $this->m_trialFromPlan;
        }

        return $params;
    }
}
