<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\TaxIdDataType;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerTaxIdData implements Arrayable
{

    /**
     * Create a customer tax ID data object.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data
     * @param TaxIdDataType $type - https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data-type
     * @param string $value - https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax_id_data-value
     */
    private function __construct(private readonly TaxIdDataType $type, private readonly string $value)
    {
    }


    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value,
        ];
    }
}