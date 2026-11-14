<?php

/*
 * A card's details, from a payment method, e.g. to show the customer which card they saved.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;


final readonly class Card
{
    private string $m_brand;
    private string $m_last4;
    private int $m_expiryMonth;
    private int $m_expiryYear;
    private ?string $m_country;
    private string $m_funding;


    /**
     * @param ResponseFields $fields - the card's fields.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     * @internal - cards are created by the payment methods that hold them.
     */
    public function __construct(ResponseFields $fields)
    {
        $this->m_brand = $fields->getString('brand');
        $this->m_last4 = $fields->getString('last4');
        $this->m_expiryMonth = $fields->getInt('exp_month');
        $this->m_expiryYear = $fields->getInt('exp_year');
        $this->m_country = $fields->getOptionalString('country');
        $this->m_funding = $fields->getString('funding');
    }


    # Accessors
    public function getBrand(): string { return $this->m_brand; }
    public function getLast4(): string { return $this->m_last4; }
    public function getExpiryMonth(): int { return $this->m_expiryMonth; }
    public function getExpiryYear(): int { return $this->m_expiryYear; }
    public function getCountry(): ?string { return $this->m_country; }
    public function getFunding(): string { return $this->m_funding; }
}
