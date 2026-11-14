<?php

/*
 * The type of a custom field, and its settings: numeric, text, or a dropdown of options.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields-type
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\CustomFieldType;
use Programster\Stripe\Exceptions\ExceptionDropdownOptionsMustBeUnique;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;

readonly class FieldType implements Arrayable
{
    private CustomFieldType $m_type;
    /** @var array<string, mixed> */
    private array $m_childConfig;


    /**
     * This is private, so use one of the static create methods.
     * @param CustomFieldType $type - the type of field.
     * @param array<string, mixed> $childConfig - the settings for that type, in the form Stripe takes them.
     */
    private function __construct(CustomFieldType $type, array $childConfig)
    {
        $this->m_type = $type;
        $this->m_childConfig = $childConfig;
    }


    /**
     * Create a numeric field. Note that this takes the maximum length before the minimum, unlike createText().
     *
     * @param string $defaultValue - the value that will pre-fill the field on the payment page. If you don't want to
     * provide a default value, then just provide an empty string, and none is sent to Stripe (which rejects an empty
     * one).
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields-numeric-default_value
     *
     * @param int $maxLength - the most digits the customer can enter.
     *
     * @param int $minLength - the fewest digits the customer can enter.
     *
     * @return FieldType
     *
     * @throws ExceptionInvalidValue - if the lengths are out of range, or the minimum is more than the maximum.
     */
    public static function createNumeric(string $defaultValue, int $maxLength, int $minLength) : FieldType
    {
        self::assertValidLengths($minLength, $maxLength);

        $childConfig = self::buildLengthConfig($defaultValue, $minLength, $maxLength);

        return new FieldType(CustomFieldType::NUMERIC, $childConfig);
    }


    /**
     * Create a text field.
     *
     * @param string $defaultValue - the value that will pre-fill the field on the payment page. If you don't want to
     * provide a default value, then just provide an empty string, and none is sent to Stripe (which rejects an empty
     * one).
     *
     * @param int $minLength - the fewest characters the customer can enter.
     *
     * @param int $maxLength - the most characters the customer can enter.
     *
     * @return FieldType
     *
     * @throws ExceptionInvalidValue - if the lengths are out of range, or the minimum is more than the maximum.
     */
    public static function createText(string $defaultValue, int $minLength, int $maxLength) : FieldType
    {
        self::assertValidLengths($minLength, $maxLength);

        $childConfig = self::buildLengthConfig($defaultValue, $minLength, $maxLength);

        return new FieldType(CustomFieldType::TEXT, $childConfig);
    }


    /**
     * Create a dropdown field
     *
     * @param DropdownOption|null $defaultOption - an option to act as the default. Specify null if you dont want to
     * have a default option
     *
     * @param DropdownOption ...$otherOptions - all the other options. This must NOT include the default option if you
     * provided one. The values in the set of options must be unique.
     *
     * @return FieldType
     *
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
                throw new ExceptionDropdownOptionsMustBeUnique($defaultOption->getValue());
            }

            $existingValues[$defaultOption->getValue()] = $defaultOption->toArray();
        }

        $childConfig = ['options' => array_values($existingValues)];

        if ($defaultOption !== null)
        {
            $childConfig['default_value'] = $defaultOption->getValue();
        }

        return new FieldType(CustomFieldType::DROPDOWN, $childConfig);
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'type' => $this->m_type->value,
            $this->m_type->value => $this->m_childConfig,
        ];
    }


    /**
     * Build the settings for a numeric or text field. Stripe rejects an empty default value, so one is only included
     * when there is one.
     * @param string $defaultValue - the value to pre-fill the field with, or an empty string for none.
     * @param int $minLength - the minimum length.
     * @param int $maxLength - the maximum length.
     * @return array<string, int|string>
     */
    private static function buildLengthConfig(string $defaultValue, int $minLength, int $maxLength): array
    {
        $childConfig = [
            'minimum_length' => $minLength,
            'maximum_length' => $maxLength,
        ];

        if ($defaultValue !== '')
        {
            $childConfig['default_value'] = $defaultValue;
        }

        return $childConfig;
    }


    /**
     * Check the length limits, which also catches the minimum and maximum being passed the wrong way round.
     * @param int $minLength - the minimum length.
     * @param int $maxLength - the maximum length.
     * @return void
     * @throws ExceptionInvalidValue
     */
    private static function assertValidLengths(int $minLength, int $maxLength) : void
    {
        if ($minLength < 0 || $maxLength > 255 || $minLength > $maxLength)
        {
            throw new ExceptionInvalidValue(
                "A field's lengths must be from 0 to 255, with the minimum ({$minLength}) no more than the maximum " .
                "({$maxLength}). Note that createNumeric() takes the maximum before the minimum."
            );
        }
    }


    # Accessors
    public function getType() : string { return $this->m_type->value; }
}
