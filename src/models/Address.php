<?php

/*
 * A postal address, e.g. a customer's, or one to ship to.
 * https://docs.stripe.com/api/customers/create#create_customer-address
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Interfaces\Arrayable;

final readonly class Address implements Arrayable
{
    private string $m_addressLine1;
    private ?string $m_addressLine2;
    private ?string $m_city;
    private ?string $m_postcodeOrZip;
    private ?string $m_stateProvinceOrRegion;
    private ?CountryCode $m_country;


    /**
     * Create an address.
     *
     * @param string $addressLine1 - the first line of the address, e.g. the street and house number.
     *
     * @param string|null $addressLine2 - optionally, the second line, e.g. an apartment or building name.
     *
     * @param string|null $city - optionally, the city, district, suburb, town or village.
     *
     * @param string|null $postcodeOrZip - optionally, the postcode or ZIP code.
     *
     * @param string|null $stateProvinceOrRegion - optionally, the state, county, province or region.
     *
     * @param CountryCode|null $country - optionally, the country, e.g. CountryCode::GB.
     */
    public function __construct(
        string       $addressLine1,
        ?string      $addressLine2 = null,
        ?string      $city = null,
        ?string      $postcodeOrZip = null,
        ?string      $stateProvinceOrRegion = null,
        ?CountryCode $country = null,
    )
    {
        $this->m_addressLine1 = $addressLine1;
        $this->m_addressLine2 = $addressLine2;
        $this->m_city = $city;
        $this->m_postcodeOrZip = $postcodeOrZip;
        $this->m_stateProvinceOrRegion = $stateProvinceOrRegion;
        $this->m_country = $country;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'line1' => $this->m_addressLine1,
        ];

        if ($this->m_addressLine2 !== null)
        {
            $arrayForm['line2'] = $this->m_addressLine2;
        }

        if ($this->m_city !== null)
        {
            $arrayForm['city'] = $this->m_city;
        }

        if ($this->m_country !== null)
        {
            $arrayForm['country'] = $this->m_country->value;
        }

        if ($this->m_postcodeOrZip !== null)
        {
            $arrayForm['postal_code'] = $this->m_postcodeOrZip;
        }

        if ($this->m_stateProvinceOrRegion !== null)
        {
            $arrayForm['state'] = $this->m_stateProvinceOrRegion;
        }

        return $arrayForm;
    }
}
