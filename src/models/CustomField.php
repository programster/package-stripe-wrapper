<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomField implements Arrayable
{

    /**
     * @param string $key - String of your choice that your integration can use to reconcile this field. Must be unique
     * to this field, alphanumeric, and up to 200 characters.
     * @param string $label - the label to display next to the field
     * @param FieldType $type - the type of field. E.g. a dropdown, text, or numeric field.
     * @param bool $optional - whether this field is optional or not. E.g. does the customer have to fill it in?
     */
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly FieldType $type,
        private readonly bool $optional
    )
    {
        if (strlen($this->key) > 100)
        {
            throw new ExceptionValueTooLong($this->key, "A custom field key must not be more than 200 characters.");
        }
    }

    public function toArray(): array
    {
        $arrayForm = [
            'key' => $this->key,
            'label' => [
                'custom' => $this->label,
                'type' => "custom"
            ],
        ];

        return array_merge($arrayForm, $this->type->toArray());
    }
}