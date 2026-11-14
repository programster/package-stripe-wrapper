# Customers

- [Creating a customer](#creating-a-customer)
- [Reading a customer](#reading-a-customer)
- [Finding customers](#finding-customers)
- [A customer's payment methods](#a-customers-payment-methods)
- [Retrying safely](#retrying-safely)

## Creating a customer
`createCustomer()` returns a [`Customer`](responses.md). Every parameter is optional:

```php
use Programster\Stripe\Models\Address;

$client = new StripeClient('sk_test_...');
$address = new Address('1 High Street', city: 'London', postcodeOrZip: 'N1 1AA', country: CountryCode::GB);

$customer = $client->createCustomer(
    address: $address,
    email: 'jo@example.com',
    name: 'Jo Bloggs',
    metadata: new Metadata(new MetadataItem('user_id', '42')),
    shippingDetails: new CustomerShippingDetails($address, 'Jo Bloggs'),
    taxConfig: new CustomerTaxConfig(ipAddress: '203.0.113.1', validateLocation: ValidateLocation::DEFERRED),
    taxIdData: new CustomerTaxIdDataCollection(new CustomerTaxIdData(TaxIdDataType::GB_VAT, 'GB123456789')),
    preferredLocales: new LocaleCollection(Locale::ENGLISH),
);

$customerId = $customer->getId();
```

- **Metadata keys** must be 1 to 40 characters, without square brackets, and values up to 500 characters. A customer
  can have up to 50 keys, and the same key can't be given twice. Each is checked where the `MetadataItem` or `Metadata`
  is created.

## Reading a customer
```php
$customer = $client->retrieveCustomer('cus_123');
$email = $customer->getEmail();                 // Null if it isn't set.
$postcode = $customer->getAddress()?->getPostalCode();
```

A customer that's been deleted can't be retrieved, as Stripe only gives back its ID, so `retrieveCustomer()` throws an
`ExceptionCustomerDeleted`. See [Responses](responses.md) for everything a customer has.

## Finding customers
```php
$customers = $client->listCustomers(email: 'jo@example.com');

foreach ($customers as $customer)
{
    print $customer->getId() . PHP_EOL;
}
```

See [Listing](listing.md) for the options every list method has.

## A customer's payment methods
```php
$cards = $client->listCustomerPaymentMethods('cus_123', PaymentMethodType::CARD);

foreach ($cards as $card)
{
    print $card->getCard()?->getBrand() . " ending " . $card->getCard()?->getLast4() . PHP_EOL;
}
```

## Retrying safely
If a request times out, it may still have reached Stripe, so sending it again could create a second customer. Give the
request an idempotency key, such as your own ID for the user, and Stripe returns the first customer, rather than making
another. `createCustomer()`, `createSubscription()`, `updateSubscriptionItem()` and both checkout session methods
take one:

```php
$customer = $client->createCustomer(email: 'jo@example.com', idempotencyKey: 'signup-user-42');
```

Use a new key for each different request: Stripe rejects a key that's reused with different parameters. A key must be 1
to 255 characters, or an `ExceptionInvalidValue` is thrown.
