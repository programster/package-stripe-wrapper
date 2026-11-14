<?php

/*
 * One of the options in a dropdown custom field.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields-dropdown-options
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidDropdownValue;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class DropdownOption implements Arrayable
{
    private string $m_label;
    private string $m_value;


    /**
     * Create an option for a dropdown.
     *
     * @param string $label - the label shown to the customer, up to 100 characters.
     *
     * @param string $value - the value your integration gets back, which isn't shown to the customer. It must be
     * alphanumeric, and up to 100 characters.
     *
     * @throws ExceptionValueTooLong - if the label or value is more than 100 characters.
     * @throws ExceptionInvalidDropdownValue - if the value isn't alphanumeric.
     */
    public function __construct(string $label, string $value)
    {
        if (strlen($label) > 100)
        {
            throw new ExceptionValueTooLong($label, "Option label is too long. Must be 100 characters or less.");
        }

        if (strlen($value) > 100)
        {
            throw new ExceptionValueTooLong($value, "Option value is too long. Must be 100 characters or less.");
        }

        if (preg_match('/^[a-zA-Z0-9]+$/', $value) !== 1)
        {
            throw new ExceptionInvalidDropdownValue($value);
        }

        $this->m_label = $label;
        $this->m_value = $value;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'label' => $this->m_label,
            'value' => $this->m_value,
        ];
    }


    # Accessors
    public function getValue() : string { return $this->m_value; }
}
