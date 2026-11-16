# Exceptions

The package throws its own exceptions when you give it something Stripe would reject, as soon as the object or request
is created, so the error points at the line that made the mistake. It also throws them when a response can't be used
as asked. They're all in the `Programster\Stripe\Exceptions` namespace:

| Exception | Thrown when |
|---|---|
| `ExceptionInvalidValue` | A value is out of range, or can't be used, e.g. a negative price, a `ResultLimit` below 1, a customer's ID where an email address is expected, or a product's ID where a price's is. |
| `ExceptionDuplicateKey` | The same metadata key is given twice, or the same subscription item is changed twice in a preview. `getKey()` returns it. It extends `ExceptionInvalidValue`. |
| `ExceptionMissingMetadataKey` | `Metadata::getValue()` is asked for a key that isn't there. Check with `hasKey()` first if it may not be. `getKey()` returns it. |
| `ExceptionValueTooLong` | A value is longer than Stripe allows, e.g. a metadata value over 500 characters. `getValue()` returns it. |
| `ExceptionInvalidKeyName` | A metadata key contains square brackets. |
| `ExceptionMissingRequiredParameter` | An object needs at least one of its optional values, e.g. `CustomTextOptions` with no text. |
| `ExceptionInvalidDropdownValue` | A dropdown option's value isn't alphanumeric. |
| `ExceptionDropdownOptionsMustBeUnique` | Two dropdown options have the same value. |
| `ExceptionTooManyResults` | A list matches more than its safety cap (1,000 by default), and no `ResultLimit` was given. See [Listing](listing.md#how-many-you-get). |
| `ExceptionCustomerDeleted` | A customer that's been deleted is retrieved, as Stripe only gives back its ID. |
| `ExceptionNoRelatedObject` | A `fetch...()` method is called for a related object that isn't there, e.g. a checkout session's `fetchSubscription()` before it's been completed. |
| `ExceptionWrongStatus` | An object isn't in the status a method needs, e.g. `toCompleted()` on a checkout session that hasn't been completed. Check its `is...()` method first. |
| `ExceptionIncompleteList` | `getItems()`, `getPayments()` or an invoice preview's `getLines()` is called on a list Stripe cut short. Use the matching `fetch...()` method instead, where there is one. |
| `ExceptionUnexpectedResponse` | Stripe's response is missing a field the package needs, or has one of an unexpected type, e.g. because Stripe's API has changed. |

```php
// Throws ExceptionInvalidValue
$price = PriceDataForSinglePayment::createUsingExistingProduct(Currency::GBP, -1, 'prod_123');
```

A webhook request whose signature doesn't match, or is too old, makes `verifyWebhookEvent()` throw
`stripe/stripe-php`'s `\Stripe\Exception\SignatureVerificationException` (see [Responses](responses.md#webhooks)).

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
