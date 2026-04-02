<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceCustomField implements Arrayable
{

    /**
     * @param string $name - String of your choice that your integration can use to reconcile this field. Must be unique
     * to this field, alphanumeric, and up to 200 characters.
     * @param string $value - the label to display next to the field
     * @param FieldType $type - the type of field. E.g. a dropdown, text, or numeric field.
     * @param bool $optional - whether this field is optional or not. E.g. does the customer have to fill it in?
     */
    public function __construct(
        private string $name,
        private string $value,
    )
    {
        if (strlen($this->name) > 40)
        {
            throw new ExceptionValueTooLong($this->name, "A custom field for an invoice cannot have a name more then 40 characters long.");
        }

        if (strlen($this->value) > 140)
        {
            throw new ExceptionValueTooLong($this->value, "A custom field for an invoice cannot have a value more then 140 characters long.");
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }
}