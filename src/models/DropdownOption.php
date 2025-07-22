<?php



namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\ExceptionDropdownOptionsMustBeUnique;
use Programster\Stripe\Enums\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class DropdownOption implements Arrayable
{
    public function __construct(
        private string $label,
        private string $value
    )
    {
        if (strlen($this->label) > 100)
        {
            throw new ExceptionValueTooLong($this->label, "Option label is too long. Must be 100 characters or less.");
        }

        if (strlen($this->$value) > 100)
        {
            throw new ExceptionValueTooLong($this->label, "Option label is too long. Must be 100 characters or less.");
        }

        if (preg_match('/[^a-z0-9A-Z]*$/', $this->value) === 0)
        {
            throw new ExceptionDropdownOptionsMustBeUnique($this->value, );
        }
    }


    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
        ];
    }

    public function getValue() : string
    {
        return $this->value;
    }
}