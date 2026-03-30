<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Updateable;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerUpdateConfig implements Arrayable
{
    /**
     * Create a configuration for specifying which fields of a customer can be updated by the checkout session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_update
     * @param Updateable $address
     * @param Updateable $name
     * @param Updateable $shipping
     */
    public function __construct(
        private Updateable $address = Updateable::NEVER,
        private Updateable $name = Updateable::NEVER,
        private Updateable $shipping  = Updateable::NEVER,
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