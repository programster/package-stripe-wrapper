<?php

/*
 * Tax details about a customer.
 * https://docs.stripe.com/api/customers/create#create_customer-tax
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\ValidateLocation;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerTaxConfig implements Arrayable
{
    private ?string $m_ipAddress;
    private ?ValidateLocation $m_validateLocation;


    /**
     * Tax details about the customer.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax
     *
     * @param string|null $ipAddress - A recent IP address of the customer used for tax reporting and tax location
     * inference. Stripe recommends updating the IP address when a new PaymentMethod is attached or the address field
     * on the customer is updated. We recommend against updating this field more frequently since it could result in
     * unexpected tax location/reporting outcomes.
     *
     * @param ValidateLocation|null $validateLocation - A flag that indicates when Stripe should validate the customer
     * tax location. Defaults to deferred.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax-validate_location
     */
    public function __construct(?string $ipAddress = null, ?ValidateLocation $validateLocation = null)
    {
        $this->m_ipAddress = $ipAddress;
        $this->m_validateLocation = $validateLocation;
    }


    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_ipAddress !== null) { $arrayForm['ip_address'] = $this->m_ipAddress; }
        if ($this->m_validateLocation !== null) { $arrayForm['validate_location'] = $this->m_validateLocation->value; }

        return $arrayForm;
    }
}
