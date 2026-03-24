<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerShippingDetails implements Arrayable
{
    /**
     * Create a Shipping details item for a customer.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-shipping-address
     */
    public function __construct(
        private Address  $address,
        private string $name,
        private ?string $phone = null
    )
    {
    }


    public function toArray(): array
    {
        $arrayForm = [
            'address' => $this->address->toArray(),
            'name' => $this->name,
        ];

        if ($this->phone !== null)
        {
            $arrayForm['phone'] = $this->phone;
        }

        return $arrayForm;
    }
}