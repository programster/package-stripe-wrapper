<?php

/*
 * Which of an existing customer's details a checkout session may update.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_update
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Updateable;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerUpdateConfig implements Arrayable
{
    private Updateable $m_address;
    private Updateable $m_name;
    private Updateable $m_shipping;


    /**
     * Create a configuration for specifying which fields of a customer can be updated by the checkout session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_update
     * @param Updateable $address - whether the customer's billing address can be updated.
     * @param Updateable $name - whether the customer's name can be updated.
     * @param Updateable $shipping - whether the customer's shipping details can be updated.
     */
    public function __construct(
        Updateable $address = Updateable::NEVER,
        Updateable $name = Updateable::NEVER,
        Updateable $shipping = Updateable::NEVER,
    )
    {
        $this->m_address = $address;
        $this->m_name = $name;
        $this->m_shipping = $shipping;
    }


    public function toArray() : array
    {
        return [
            'address' => $this->m_address->value,
            'name' => $this->m_name->value,
            'shipping' => $this->m_shipping->value,
        ];
    }
}
