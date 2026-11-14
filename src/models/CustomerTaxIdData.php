<?php

/*
 * One of a customer's tax IDs, e.g. a VAT number.
 * https://docs.stripe.com/api/customers/create#create_customer-tax_id_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\TaxIdDataType;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerTaxIdData implements Arrayable
{
    private TaxIdDataType $m_type;
    private string $m_value;


    /**
     * Create a customer tax ID data object.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data
     *
     * @param TaxIdDataType $type - the type of tax ID, e.g. a UK VAT number.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data-type
     *
     * @param string $value - the tax ID itself.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data-value
     */
    public function __construct(TaxIdDataType $type, string $value)
    {
        $this->m_type = $type;
        $this->m_value = $value;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'type' => $this->m_type->value,
            'value' => $this->m_value,
        ];
    }
}
