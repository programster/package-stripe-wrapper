<?php

/*
 * A name and value to show on an invoice, e.g. a purchase order number.
 * https://docs.stripe.com/api/customers/create#create_customer-invoice_settings-custom_fields
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

final readonly class InvoiceCustomField implements Arrayable
{
    private string $m_name;
    private string $m_value;


    /**
     * Create a custom field for an invoice.
     * @param string $name - the name of the field, up to 40 characters.
     * @param string $value - the value of the field, up to 140 characters.
     * @throws ExceptionValueTooLong - if the name or value is too long.
     */
    public function __construct(string $name, string $value)
    {
        if (strlen($name) > 40)
        {
            throw new ExceptionValueTooLong(
                $name,
                "A custom field for an invoice cannot have a name more then 40 characters long."
            );
        }

        if (strlen($value) > 140)
        {
            throw new ExceptionValueTooLong(
                $value,
                "A custom field for an invoice cannot have a value more then 140 characters long."
            );
        }

        $this->m_name = $name;
        $this->m_value = $value;
    }


    /**
     * @return array{name: string, value: string}
     */
    public function toArray() : array
    {
        return [
            'name' => $this->m_name,
            'value' => $this->m_value,
        ];
    }
}
