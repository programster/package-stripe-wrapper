<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class BillingThresholds
{
    /**
     * @param int|null $usageGte - Number of units that meets the billing threshold to advance the subscription to a new
     *  billing period (e.g., it takes 10 $5 units to meet a $50 monetary threshold). If null, then this will provide
     * stripe with an empty string to remove any previously-defined thresholds.
     * https://docs.stripe.com/api/subscription_items/update?lang=php#update_subscription_item-billing_thresholds
     */
    public function __construct(private readonly ?int $usageGte)
    {

    }


    public function toStripeForm(): array|string
    {
        if ($this->usageGte !== null)
        {
            $arrayForm = [
                'usage_gte' => $this->usageGte
            ];
        }
        else
        {
            $arrayForm = "";
        }

        return $arrayForm;
    }
}