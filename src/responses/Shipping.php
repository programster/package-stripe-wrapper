<?php

/*
 * Where to ship to, from a Stripe response, e.g. a customer's shipping details.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;


final readonly class Shipping
{
    private ?string $m_name;
    private ?string $m_phone;
    private ?Address $m_address;


    /**
     * @param ResponseFields $fields - the shipping details' fields.
     * @throws ExceptionUnexpectedResponse - if a field is of an unexpected type.
     * @internal - shipping details are created by the response models that hold them.
     */
    public function __construct(ResponseFields $fields)
    {
        $address = $fields->getOptionalObject('address');
        $this->m_name = $fields->getOptionalString('name');
        $this->m_phone = $fields->getOptionalString('phone');
        $this->m_address = ($address !== null) ? new Address($address) : null;
    }


    # Accessors
    public function getName(): ?string { return $this->m_name; }
    public function getPhone(): ?string { return $this->m_phone; }
    public function getAddress(): ?Address { return $this->m_address; }
}
