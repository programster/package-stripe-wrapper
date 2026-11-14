<?php

/*
 * An address from a Stripe response, e.g. a customer's. Every part of it can be empty.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;


final readonly class Address
{
    private ?string $m_line1;
    private ?string $m_line2;
    private ?string $m_city;
    private ?string $m_state;
    private ?string $m_postalCode;
    private ?string $m_country;


    /**
     * @param ResponseFields $fields - the address's fields.
     * @throws ExceptionUnexpectedResponse - if a field is of an unexpected type.
     * @internal - addresses are created by the response models that hold them.
     */
    public function __construct(ResponseFields $fields)
    {
        $this->m_line1 = $fields->getOptionalString('line1');
        $this->m_line2 = $fields->getOptionalString('line2');
        $this->m_city = $fields->getOptionalString('city');
        $this->m_state = $fields->getOptionalString('state');
        $this->m_postalCode = $fields->getOptionalString('postal_code');
        $this->m_country = $fields->getOptionalString('country');
    }


    # Accessors
    public function getLine1(): ?string { return $this->m_line1; }
    public function getLine2(): ?string { return $this->m_line2; }
    public function getCity(): ?string { return $this->m_city; }
    public function getState(): ?string { return $this->m_state; }
    public function getPostalCode(): ?string { return $this->m_postalCode; }
    public function getCountry(): ?string { return $this->m_country; }
}
