# Exceptions

The package throws its own exceptions when you give it something Stripe would reject, as soon as the object or request
is created, so the error points at the line that made the mistake. They're all in the `Programster\Stripe\Exceptions`
namespace:

| Exception | Thrown when |
|---|---|
| `ExceptionInvalidValue` | A value is out of range, or can't be used, e.g. a negative price, a limit outside 1 to 100, or a customer's ID where an email address is expected. |
| `ExceptionDuplicateKey` | The same metadata key is given twice. It extends `ExceptionInvalidValue`. |
| `ExceptionValueTooLong` | A value is longer than Stripe allows, e.g. a metadata value over 500 characters. `getValue()` returns it. |
| `ExceptionInvalidKeyName` | A metadata key contains square brackets. |
| `ExceptionMissingRequiredParameter` | An object needs at least one of its optional values, e.g. `CustomTextOptions` with no text. |
| `ExceptionInvalidDropdownValue` | A dropdown option's value isn't alphanumeric. |
| `ExceptionDropdownOptionsMustBeUnique` | Two dropdown options have the same value. |

```php
// Throws ExceptionInvalidValue
$price = new PriceDataForSinglePayment(Currency::GBP, -1, 'prod_123');
```

When Stripe itself rejects a request, or can't be reached, the methods throw `stripe/stripe-php`'s
`\Stripe\Exception\ApiErrorException` (or a subclass of it, such as `\Stripe\Exception\CardException`), as the
`@throws` of each `StripeClient` method says:

```php
$client = new StripeClient('sk_test_...');

try
{
    $customer = $client->createCustomer(email: 'jo@example.com');
}
catch (\Stripe\Exception\ApiErrorException $e)
{
    print "Stripe refused: " . $e->getMessage() . PHP_EOL;
}
```
