<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class Address implements Arrayable
{
    /**
     * Create a Shipping Address object for a customer.
     * @param string $addressLine1
     * @param string|null $addressLine2
     * @param string|null $city
     * @param string|null $postcodeOrZip
     * @param string|null $stateProvinceOrRegion
     * @param string|null $country
     */
    public function __construct(
        private string  $addressLine1,
        private ?string $addressLine2 = null,
        private ?string $city = null,
        private ?string $postcodeOrZip = null,
        private ?string $stateProvinceOrRegion = null,
        private ?string $country = null,
    )
    {
    }


    public function toArray(): array
    {
        $arrayForm = [
            'line1' => $this->addressLine1,
        ];

        if ($this->addressLine2 !== null)
        {
            $arrayForm['line2'] = $this->addressLine2;
        }

        if ($this->city !== null)
        {
            $arrayForm['city'] = $this->city;
        }

        if ($this->country !== null)
        {
            $arrayForm['country'] = $this->country;
        }

        if ($this->postcodeOrZip !== null)
        {
            $arrayForm['postal_code'] = $this->postcodeOrZip;
        }

        if ($this->stateProvinceOrRegion !== null)
        {
            $arrayForm['state'] = $this->stateProvinceOrRegion;
        }

        return $arrayForm;
    }
}