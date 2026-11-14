<?php

/*
 * A customer's shipping details: where to ship to, and who to.
 * https://docs.stripe.com/api/customers/create#create_customer-shipping
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerShippingDetails implements Arrayable
{
    private Address $m_address;
    private string $m_name;
    private ?string $m_phone;


    /**
     * Create the shipping details for a customer.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-shipping-address
     * @param Address $address - the address to ship to.
     * @param string $name - the name of the person to ship to.
     * @param string|null $phone - optionally, their phone number (including extension).
     */
    public function __construct(Address $address, string $name, ?string $phone = null)
    {
        $this->m_address = $address;
        $this->m_name = $name;
        $this->m_phone = $phone;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'address' => $this->m_address->toArray(),
            'name' => $this->m_name,
        ];

        if ($this->m_phone !== null)
        {
            $arrayForm['phone'] = $this->m_phone;
        }

        return $arrayForm;
    }
}
