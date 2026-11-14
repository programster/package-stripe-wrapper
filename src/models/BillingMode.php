<?php

/*
 * Controls how prorations and invoices for subscriptions are calculated and orchestrated.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-billing_mode
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\BillingModeType;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Interfaces\Arrayable;

readonly class BillingMode implements Arrayable
{
    private BillingModeType $m_type;
    private ?ProrationDiscountConfig $m_prorationDiscountConfig;


    /**
     * This is private, so use createClassic() or createFlexible().
     * @param BillingModeType $type - classic or flexible.
     * @param ProrationDiscountConfig|null $prorationDiscountConfig - for flexible mode, how discounts are shown.
     */
    private function __construct(BillingModeType $type, ?ProrationDiscountConfig $prorationDiscountConfig)
    {
        $this->m_type = $type;
        $this->m_prorationDiscountConfig = $prorationDiscountConfig;
    }


    /**
     * Calculate subscriptions and invoices based on legacy defaults.
     * @return BillingMode
     */
    public static function createClassic() : BillingMode
    {
        return new self(BillingModeType::CLASSIC, null);
    }


    /**
     * Use the more flexible calculation and orchestration options for subscriptions and invoices.
     * @param ProrationDiscountConfig $prorationDiscountConfig - how proration and discount amounts are shown.
     * @return BillingMode
     */
    public static function createFlexible(ProrationDiscountConfig $prorationDiscountConfig) : BillingMode
    {
        return new self(BillingModeType::FLEXIBLE, $prorationDiscountConfig);
    }


    public function toArray() : array
    {
        $arrayForm = [
            'type' => $this->m_type->value,
        ];

        if ($this->m_type === BillingModeType::FLEXIBLE)
        {
            $arrayForm['flexible'] = ['proration_discounts' => $this->m_prorationDiscountConfig->value];
        }

        return $arrayForm;
    }
}
