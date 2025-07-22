<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\AllowRedisplayFilters;
use Programster\Stripe\Interfaces\Arrayable;

class SavedPaymentMethodOptions implements Arrayable
{
    public function __construct(
        private readonly bool $enableSavingOfPaymentMethod = false,
        private readonly bool $enablePaymentMethodRemoval = false,
        private readonly AllowRedisplayFilters $allowRedisplayFilters = AllowRedisplayFilters::UNSPECIFIED
    )
    {

    }


    public function toArray(): array
    {
        return [
            'payment_method_save' => ($this->enableSavingOfPaymentMethod) ? "enabled" : "disabled",
            'payment_method_remove' => ($this->enablePaymentMethodRemoval) ? "enabled" : "disabled",
            'allow_redisplay_filters' => $this->allowRedisplayFilters->value
        ];
    }
}