<?php

/*
 * An object to encapsulate all of the trial settings, such as trial_end, trial_period_days, and trial_settings.
 * Create one with one of the static create methods.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;


readonly class TrialConfig
{
    private ?int $m_numDays;
    private ?int $m_endTimestamp;
    private ?EndTrialBehaviorIfMissingPaymentMethod $m_endTrialBehaviorIfMissingPaymentMethod;


    /**
     * @param int|null $numDays - the number of days the trial lasts, or null if it ends at $endTimestamp.
     *
     * @param int|null $endTimestamp - the unix timestamp the trial ends at, or null if it lasts $numDays.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     */
    private function __construct(
        ?int $numDays = null,
        ?int $endTimestamp = null,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null,
    )
    {
        $this->m_numDays = $numDays;
        $this->m_endTimestamp = $endTimestamp;
        $this->m_endTrialBehaviorIfMissingPaymentMethod = $endTrialBehaviorIfMissingPaymentMethod;
    }


    /**
     * Create a trial that ends at a given time.
     *
     * @param int $unixTimestamp - the unix timestamp the trial ends at.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     *
     * @return TrialConfig
     */
    public static function createForEndTimestamp(
        int $unixTimestamp,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ) : TrialConfig
    {
        return new TrialConfig(null, $unixTimestamp, $endTrialBehaviorIfMissingPaymentMethod);
    }


    /**
     * Create a trial that lasts a number of days.
     *
     * @param int $numDays - the number of days the trial lasts.
     *
     * @param EndTrialBehaviorIfMissingPaymentMethod|null $endTrialBehaviorIfMissingPaymentMethod - what to do if the
     * trial ends and the customer hasn't given a payment method.
     *
     * @return TrialConfig
     */
    public static function createForNumDays(
        int $numDays,
        ?EndTrialBehaviorIfMissingPaymentMethod $endTrialBehaviorIfMissingPaymentMethod = null
    ) : TrialConfig
    {
        return new TrialConfig($numDays, null, $endTrialBehaviorIfMissingPaymentMethod);
    }


    /**
     * Get the trial's settings, as the parameters Stripe takes for them.
     * @return array<string, mixed>
     */
    public function getStripeParams() : array
    {
        $params = [];

        if ($this->m_numDays !== null) { $params['trial_period_days'] = $this->m_numDays; }
        if ($this->m_endTimestamp !== null) { $params['trial_end'] = $this->m_endTimestamp; }

        if ($this->m_endTrialBehaviorIfMissingPaymentMethod !== null)
        {
            $endBehavior = ['missing_payment_method' => $this->m_endTrialBehaviorIfMissingPaymentMethod->value];
            $params['trial_settings'] = ['end_behavior' => $endBehavior];
        }

        return $params;
    }
}
