<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\BillingModeType;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Interfaces\Arrayable;

readonly class BillingMode implements Arrayable
{

    /**
     * Controls how prorations and invoices for subscriptions are calculated and orchestrated.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-billing_mode
     */
    private function __construct(
        private readonly BillingModeType          $type,
        private readonly ?ProrationDiscountConfig $prorationDiscountConfig,
    )
    {

    }


    public static function createClassic()
    {
        return new self(BillingModeType::CLASSIC, null);
    }


    public static function createFlexible(ProrationDiscountConfig $prorationDiscountConfig)
    {
        return new self(BillingModeType::FLEXIBLE, $prorationDiscountConfig);
    }

    public function toArray(): array
    {
        $arrayForm = [
            'type' => $this->type->value,
        ];

        if ($this->type !== BillingModeType::FLEXIBLE)
        {
            $arrayForm['flexible'] = ['proration_discounts' => $this->prorationDiscountConfig->value];
        }

        return $arrayForm;
    }
}