<?php

/*
 * The fields of one object from a Stripe response, read as the types the response models expect. If a field the
 * package needs is missing, or of an unexpected type, an ExceptionUnexpectedResponse is thrown, naming the object and
 * field, so a change in Stripe's API fails loudly in one place. This is internal to the package, and may change in any
 * release.
 */

namespace Programster\Stripe\Internal;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Responses\Money;

/**
 * @internal
 */
final readonly class ResponseFields
{
    /** @var array<string, mixed> */
    private array $m_values;

    private string $m_description;


    /**
     * @param array<string, mixed> $values - the object's fields, as Stripe sent them.
     * @param string $description - what the object is, for error messages, e.g. "customer cus_123".
     */
    public function __construct(array $values, string $description)
    {
        $this->m_values = $values;
        $this->m_description = $description;
    }


    /**
     * Read the fields of an object from a Stripe response, checking that it is one (each of its keys is a field's
     * name), rather than a list.
     *
     * @param array<mixed> $values - the object's values, e.g. from the SDK object's toArray().
     *
     * @param string $description - what the object is, for error messages, e.g. "customer cus_123".
     *
     * @return ResponseFields
     *
     * @throws ExceptionUnexpectedResponse - if any of its keys isn't a field's name.
     */
    public static function createFromArray(array $values, string $description): ResponseFields
    {
        $fields = [];

        foreach ($values as $key => $value)
        {
            if (!is_string($key))
            {
                throw new ExceptionUnexpectedResponse(
                    "The {$description} from Stripe should be an object, but was a list."
                );
            }

            $fields[$key] = $value;
        }

        return new ResponseFields($fields, $description);
    }


    /**
     * Read the fields of a nested object, such as a customer's address.
     * @param string $field - the field holding the nested object.
     * @return ResponseFields
     * @throws ExceptionUnexpectedResponse - if the field is missing, or isn't an object.
     */
    public function getObject(string $field): ResponseFields
    {
        $value = $this->getValue($field);

        if (!is_array($value))
        {
            $this->throwUnexpected($field, "an object", $value);
        }

        return $this->createNested($field, $value, "{$field} of {$this->m_description}");
    }


    /**
     * Read the fields of a nested object that can be null, such as a customer's address.
     * @param string $field - the field holding the nested object.
     * @return ResponseFields|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't an object or null.
     */
    public function getOptionalObject(string $field): ?ResponseFields
    {
        $value = $this->m_values[$field] ?? null;
        $fields = null;

        if (is_array($value))
        {
            $fields = $this->createNested($field, $value, "{$field} of {$this->m_description}");
        }
        elseif ($value !== null)
        {
            $this->throwUnexpected($field, "an object or null", $value);
        }

        return $fields;
    }


    /**
     * @param string $field - the field's name.
     * @return string
     * @throws ExceptionUnexpectedResponse - if the field is missing, or isn't a string.
     */
    public function getString(string $field): string
    {
        $value = $this->getValue($field);

        if (!is_string($value))
        {
            $this->throwUnexpected($field, "a string", $value);
        }

        return $value;
    }


    /**
     * @param string $field - the field's name.
     * @return string|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't a string or null.
     */
    public function getOptionalString(string $field): ?string
    {
        $value = $this->m_values[$field] ?? null;

        if ($value !== null && !is_string($value))
        {
            $this->throwUnexpected($field, "a string or null", $value);
        }

        return $value;
    }


    /**
     * @param string $field - the field's name.
     * @return int
     * @throws ExceptionUnexpectedResponse - if the field is missing, or isn't an int.
     */
    public function getInt(string $field): int
    {
        $value = $this->getValue($field);

        if (!is_int($value))
        {
            $this->throwUnexpected($field, "an int", $value);
        }

        return $value;
    }


    /**
     * @param string $field - the field's name.
     * @return int|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't an int or null.
     */
    public function getOptionalInt(string $field): ?int
    {
        $value = $this->m_values[$field] ?? null;

        if ($value !== null && !is_int($value))
        {
            $this->throwUnexpected($field, "an int or null", $value);
        }

        return $value;
    }


    /**
     * @param string $field - the field's name.
     * @return bool
     * @throws ExceptionUnexpectedResponse - if the field is missing, or isn't a bool.
     */
    public function getBool(string $field): bool
    {
        $value = $this->getValue($field);

        if (!is_bool($value))
        {
            $this->throwUnexpected($field, "a bool", $value);
        }

        return $value;
    }


    /**
     * @param string $field - the field's name.
     * @return bool|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't a bool or null.
     */
    public function getOptionalBool(string $field): ?bool
    {
        $value = $this->m_values[$field] ?? null;

        if ($value !== null && !is_bool($value))
        {
            $this->throwUnexpected($field, "a bool or null", $value);
        }

        return $value;
    }


    /**
     * Read a unix timestamp, as a time in UTC.
     * @param string $field - the field's name.
     * @return \DateTimeImmutable
     * @throws ExceptionUnexpectedResponse - if the field is missing, or isn't an int.
     */
    public function getTime(string $field): \DateTimeImmutable
    {
        return self::timestampToTime($this->getInt($field));
    }


    /**
     * Read a unix timestamp that can be null, as a time in UTC.
     * @param string $field - the field's name.
     * @return \DateTimeImmutable|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't an int or null.
     */
    public function getOptionalTime(string $field): ?\DateTimeImmutable
    {
        $timestamp = $this->getOptionalInt($field);
        return ($timestamp !== null) ? self::timestampToTime($timestamp) : null;
    }


    /**
     * Read the ID of a related object, which Stripe sends as just its ID, or as the whole object if it was expanded.
     * @param string $field - the field's name.
     * @return string
     * @throws ExceptionUnexpectedResponse - if the field is missing, or is neither an ID nor an object with one.
     */
    public function getId(string $field): string
    {
        $id = $this->getOptionalId($field);

        if ($id === null)
        {
            $this->throwUnexpected($field, "an ID", null);
        }

        return $id;
    }


    /**
     * Read the ID of a related object that can be null. Stripe sends it as just its ID, or as the whole object if it
     * was expanded.
     * @param string $field - the field's name.
     * @return string|null - null if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field is neither an ID, an object with one, nor null.
     */
    public function getOptionalId(string $field): ?string
    {
        $value = $this->m_values[$field] ?? null;

        if (is_array($value) && isset($value['id']) && is_string($value['id']))
        {
            $id = $value['id'];
        }
        elseif ($value === null || is_string($value))
        {
            $id = $value;
        }
        else
        {
            $this->throwUnexpected($field, "an ID, an object with one, or null", $value);
        }

        return $id;
    }


    /**
     * Read an object's metadata.
     * @return Metadata
     * @throws ExceptionUnexpectedResponse - if the metadata isn't an object of strings.
     */
    public function getMetadata(): Metadata
    {
        $values = $this->m_values['metadata'] ?? [];
        $items = [];

        if (!is_array($values))
        {
            $this->throwUnexpected('metadata', "an object", $values);
        }

        foreach ($values as $key => $value)
        {
            if (!is_string($value))
            {
                $this->throwUnexpected("metadata.{$key}", "a string", $value);
            }

            $items[] = new MetadataItem((string)$key, $value);
        }

        return new Metadata(...$items);
    }


    /**
     * Read a list of strings, such as a customer's preferred locales.
     * @param string $field - the field's name.
     * @return StringCollection - empty if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't a list of strings.
     */
    public function getStrings(string $field): StringCollection
    {
        $values = $this->m_values[$field] ?? [];
        $strings = [];

        if (!is_array($values))
        {
            $this->throwUnexpected($field, "a list", $values);
        }

        foreach ($values as $value)
        {
            if (!is_string($value))
            {
                $this->throwUnexpected($field, "a list of strings", $value);
            }

            $strings[] = $value;
        }

        return new StringCollection(...$strings);
    }


    /**
     * Read an amount of money, from its amount field and the object's currency field.
     * @param string $amountField - the field holding the amount, in the currency's smallest unit (e.g. pennies).
     * @param string $currencyField - the field holding the currency's code, e.g. "gbp".
     * @return Money|null - null if Stripe sent no amount.
     * @throws ExceptionUnexpectedResponse - if the amount isn't an int, or there's an amount but no currency.
     */
    public function getOptionalMoney(string $amountField, string $currencyField = 'currency'): ?Money
    {
        $amount = $this->getOptionalInt($amountField);
        return ($amount !== null) ? new Money($amount, $this->getString($currencyField)) : null;
    }


    /**
     * Read a plain list of nested objects (rather than one of Stripe's paged lists), such as a subscription item's tax
     * rates.
     * @param string $field - the field holding the list.
     * @return list<ResponseFields> - each object's fields, or none if Stripe sent null, or left the field out.
     * @throws ExceptionUnexpectedResponse - if the field isn't a list of objects.
     */
    public function getObjects(string $field): array
    {
        $values = $this->m_values[$field] ?? [];
        $objects = [];

        if (!is_array($values))
        {
            $this->throwUnexpected($field, "a list", $values);
        }

        foreach ($values as $index => $value)
        {
            if (!is_array($value))
            {
                $this->throwUnexpected("{$field}.{$index}", "an object", $value);
            }

            $description = "{$field}[{$index}] of {$this->m_description}";
            $objects[] = $this->createNested("{$field}.{$index}", $value, $description);
        }

        return $objects;
    }


    /**
     * Read a list of nested objects, such as the items Stripe included in a subscription, along with whether Stripe
     * left any out (has_more).
     * @param string $field - the field holding the list, e.g. "items".
     * @return array{0: list<ResponseFields>, 1: bool} - each object's fields, and whether more weren't included.
     * @throws ExceptionUnexpectedResponse - if the field isn't one of Stripe's lists.
     */
    public function getList(string $field): array
    {
        $list = $this->getObject($field);
        $data = $list->getValue('data');
        $objects = [];

        if (!is_array($data))
        {
            $list->throwUnexpected('data', "a list", $data);
        }

        foreach ($data as $index => $values)
        {
            if (!is_array($values))
            {
                $list->throwUnexpected("data.{$index}", "an object", $values);
            }

            $description = "{$field}[{$index}] of {$this->m_description}";
            $objects[] = $list->createNested("data.{$index}", $values, $description);
        }

        return [$objects, $list->getBool('has_more')];
    }


    /**
     * Get the fields as Stripe sent them, e.g. to rebuild the SDK's object from them.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->m_values;
    }


    /**
     * Get a field's value, which must be present, though it can be null.
     * @param string $field - the field's name.
     * @return mixed
     * @throws ExceptionUnexpectedResponse - if the field is missing.
     */
    private function getValue(string $field): mixed
    {
        if (!array_key_exists($field, $this->m_values))
        {
            throw new ExceptionUnexpectedResponse("The {$this->m_description} from Stripe has no \"{$field}\" field.");
        }

        return $this->m_values[$field];
    }


    /**
     * Create the fields of a nested object, checking that it is one (each of its keys is a field's name), rather than
     * a list.
     *
     * @param string $field - the field holding the object, for the error message.
     *
     * @param array<mixed> $values - the object's values, as Stripe sent them.
     *
     * @param string $description - what the object is, for error messages, e.g. "address of customer cus_123".
     *
     * @return ResponseFields
     *
     * @throws ExceptionUnexpectedResponse - if any of its keys isn't a field's name.
     */
    private function createNested(string $field, array $values, string $description): ResponseFields
    {
        $fields = [];

        foreach ($values as $key => $value)
        {
            if (!is_string($key))
            {
                $this->throwUnexpected($field, "an object", $values);
            }

            $fields[$key] = $value;
        }

        return new ResponseFields($fields, $description);
    }


    /**
     * Throw an exception saying a field wasn't what was expected.
     * @param string $field - the field's name.
     * @param string $expected - what it should have been, e.g. "a string".
     * @param mixed $value - what it was.
     * @return never
     * @throws ExceptionUnexpectedResponse
     */
    private function throwUnexpected(string $field, string $expected, mixed $value): never
    {
        throw new ExceptionUnexpectedResponse(
            "The \"{$field}\" field of the {$this->m_description} from Stripe should be {$expected}, but was " .
            get_debug_type($value) . "."
        );
    }


    /**
     * Turn a unix timestamp into a time in UTC.
     * @param int $timestamp - the timestamp.
     * @return \DateTimeImmutable
     */
    private static function timestampToTime(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));
    }
}
