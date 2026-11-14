<?php

/*
 * A custom field for the customer to fill in during checkout.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomField implements Arrayable
{
    private string $m_key;
    private string $m_label;
    private FieldType $m_type;
    private bool $m_optional;


    /**
     * Create a custom field for checkout.
     *
     * @param string $key - String of your choice that your integration can use to reconcile this field. Must be unique
     * to this field, alphanumeric, and up to 200 characters.
     *
     * @param string $label - the label to display next to the field.
     *
     * @param FieldType $type - the type of field. E.g. a dropdown, text, or numeric field.
     *
     * @param bool $optional - whether this field is optional or not. E.g. does the customer have to fill it in?
     *
     * @throws ExceptionValueTooLong - if the key is more than 200 characters.
     */
    public function __construct(string $key, string $label, FieldType $type, bool $optional)
    {
        if (strlen($key) > 200)
        {
            throw new ExceptionValueTooLong($key, "A custom field key must not be more than 200 characters.");
        }

        $this->m_key = $key;
        $this->m_label = $label;
        $this->m_type = $type;
        $this->m_optional = $optional;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'key' => $this->m_key,
            'label' => [
                'custom' => $this->m_label,
                'type' => "custom"
            ],
            'optional' => $this->m_optional,
        ];

        return array_merge($arrayForm, $this->m_type->toArray());
    }
}
