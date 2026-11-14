<?php

/*
 * The details a customer gave during a checkout session, such as their email address. Every part can be empty.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;


final readonly class CustomerDetails
{
    private ?string $m_email;
    private ?string $m_name;
    private ?string $m_phone;
    private ?Address $m_address;
    private ?string $m_taxExempt;


    /**
     * @param ResponseFields $fields - the details' fields.
     * @throws ExceptionUnexpectedResponse - if a field is of an unexpected type.
     * @internal - customer details are created by the checkout sessions that hold them.
     */
    public function __construct(ResponseFields $fields)
    {
        $address = $fields->getOptionalObject('address');
        $this->m_email = $fields->getOptionalString('email');
        $this->m_name = $fields->getOptionalString('name');
        $this->m_phone = $fields->getOptionalString('phone');
        $this->m_address = ($address !== null) ? new Address($address) : null;
        $this->m_taxExempt = $fields->getOptionalString('tax_exempt');
    }


    # Accessors
    public function getEmail(): ?string { return $this->m_email; }
    public function getName(): ?string { return $this->m_name; }
    public function getPhone(): ?string { return $this->m_phone; }
    public function getAddress(): ?Address { return $this->m_address; }
    public function getTaxExempt(): ?string { return $this->m_taxExempt; }
}
