<?php

/*
 * Thresholds at which an invoice will be sent, and the subscription advanced to a new billing period.
 * https://docs.stripe.com/api/subscription_items/update#update_subscription_item-billing_thresholds
 */

namespace Programster\Stripe\Models;

readonly class BillingThresholds
{
    private ?int $m_usageGte;


    /**
     * @param int|null $usageGte - Number of units that meets the billing threshold to advance the subscription to a new
     * billing period (e.g., it takes 10 $5 units to meet a $50 monetary threshold). If null, then this will provide
     * stripe with an empty string to remove any previously-defined thresholds.
     * https://docs.stripe.com/api/subscription_items/update?lang=php#update_subscription_item-billing_thresholds
     */
    public function __construct(?int $usageGte)
    {
        $this->m_usageGte = $usageGte;
    }


    /**
     * Get the form to send to Stripe. Stripe takes an empty string to mean "remove any thresholds".
     * @return array<string, int>|string
     */
    public function toStripeForm() : array|string
    {
        if ($this->m_usageGte !== null)
        {
            $stripeForm = [
                'usage_gte' => $this->m_usageGte
            ];
        }
        else
        {
            $stripeForm = "";
        }

        return $stripeForm;
    }
}
