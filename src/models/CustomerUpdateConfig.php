<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Updatable;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerUpdateConfig implements Arrayable
{
    /**
     * Create a configuration for specifying which fields of a customer can be updated by the checkout session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_update
     * @param Updatable $address
     * @param Updatable $name
     * @param Updatable $shipping
     */
    public function __construct(
        private Updatable $address = Updatable::NEVER,
        private Updatable $name = Updatable::NEVER,
        private Updatable $shipping  = Updatable::NEVER,
    )
    {

    }


    public function toArray(): array
    {
        return [
            'address' => $this->address->value,
            'name' => $this->name->value,
            'shipping' => $this->shipping->value,
        ];
    }
}