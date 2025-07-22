<?php



namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\ExceptionDropdownOptionsMustBeUnique;
use Programster\Stripe\Interfaces\Arrayable;

readonly class FieldType implements Arrayable
{
    private function __construct(
        private string $type,
        private array $childConfig)
    {

    }


    /**
     * @param string $defaultValue - the value that will pre-fill the field on the payment page. If you don't want to
     * provide a default value, then just provide an empty string.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields-numeric-default_value
     * @param int $maxLength
     * @param int $minLength
     * @return FieldType
     */
    public static function createNumeric(string $defaultValue, int $maxLength, int $minLength) : FieldType
    {
        $childConfig = [
            'default_value' => $defaultValue,
            'minimum_length' => $minLength,
            'maximum_length' => $maxLength,
        ];

        return new FieldType("numeric", $childConfig);
    }

    public static function createText(string $defaultValue, int $minLength, int $maxLength) : FieldType
    {
        $childConfig = [
            'default_value' => $defaultValue,
            'minimum_length' => $minLength,
            'maximum_length' => $maxLength,
        ];

        return new FieldType("text", $childConfig);
    }


    /**
     * Create a dropdown field
     * @param DropdownOption|null $defaultOption - an option to act as the default. Specify null if you dont want to have
     * a default option
     * @param DropdownOption ...$otherOptions - all the other options. This must NOT include the default option if you
     * provided one. The values in the set of options must be unique.
     * @return FieldType
     * @throws ExceptionDropdownOptionsMustBeUnique
     */
    public static function createDropdown(?DropdownOption $defaultOption, DropdownOption ...$otherOptions) : FieldType
    {
        $existingValues = [];

        foreach ($otherOptions as $otherOption)
        {
            if (isset($existingValues[$otherOption->getValue()]))
            {
                throw new ExceptionDropdownOptionsMustBeUnique($otherOption->getValue());
            }

            $existingValues[$otherOption->getValue()] = $otherOption->toArray();
        }

        if ($defaultOption !== null)
        {
            if (isset($existingValues[$defaultOption->getValue()]))
            {
                throw new ExceptionDropdownOptionsMustBeUnique($otherOption->getValue());
            }

            $existingValues[$defaultOption->getValue()] = $defaultOption->toArray();
        }

        $childConfig = [
            'options' => array_values($existingValues),
            'default_value' => $defaultOption->getValue(),
        ];

        return new FieldType("dropdown", $childConfig);
    }


    public function getType(): string
    {
        return $this->type;
    }


    public function toArray(): array
    {
        return [
            'type' => $this->type,
            $this->type => $this->childConfig,
        ];
    }
}