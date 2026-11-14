Stripe Wrapper
==============

[![Tests](https://github.com/programster/package-stripe-wrapper/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/programster/package-stripe-wrapper/actions/workflows/tests.yml)

## Contents
- [About](#about)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Checkout sessions](docs/checkout.md)
- [Customers](docs/customers.md)
- [Subscriptions](docs/subscriptions.md)
- [Responses](docs/responses.md)
- [Listing](docs/listing.md)
- [Exceptions](docs/exceptions.md)
- [Upgrading](docs/upgrading.md)
- [Testing](docs/testing.md)
- [Design principles](#design-principles)
- [License](#license)

## About
A wrapper around the [stripe/stripe-php](https://packagist.org/packages/stripe/stripe-php) package, to make it easier
to use Stripe without having to read its documentation. Instead of building arrays of parameters, you fill in
constructors whose parameters are typed and documented, and your IDE shows you the options as you type. Values with a
fixed set of options, such as currencies, countries and statuses, are enums, so they can't be misspelt.

What comes back is typed too: each method returns readonly models, such as a `Customer` or a `Subscription`, with a
getter for each field, and the list methods return a collection of everything that matches, so there are no pages to
handle (see [Responses](docs/responses.md)).

## Requirements
PHP 8.2, 8.3, 8.4 or 8.5, with the `curl` extension.

## Installation
```bash
composer require programster/stripe-wrapper
```

## Quick start
This creates a checkout session for a one-off payment, and sends the customer to Stripe's payment page:

```php
<?php

require_once(__DIR__ . '/vendor/autoload.php');

$client = new StripeClient('sk_test_...');
$flowConfig = FlowConfig::createHosted(successUrl: "https://example.com/success");

$shirt = new PriceDataForSinglePayment(
    currency: Currency::GBP,
    unitAmount: 2000,
    productDataOrId: new ProductData(
        name: "T-shirt",
        description: "A designer t-shirt."
    )
);

$items = new SinglePaymentLineItemsCollection(
    new SinglePaymentLineItem(
        quantity: 1,
        priceDataOrPriceId: $shirt,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    )
);

$checkoutSession = $client->createCheckoutSessionForSinglePayment(
    flowConfig: $flowConfig,
    items: $items,
);

# Redirect the user to the Stripe payment page.
http_response_code(303);
header('Location: ' . $checkoutSession->getUrl());
```

Every class used above is in the `Programster\Stripe` namespace, e.g. `Programster\Stripe\StripeClient` and
`Programster\Stripe\Enums\Currency`. See [Checkout sessions](docs/checkout.md) for subscriptions, embedded checkouts,
existing customers and the other options.

## Design principles
- **Mistakes are caught where they're made.** Options with a fixed set of values are enums, and values Stripe would
  always reject (such as a negative price, or a minimum that's more than the maximum) throw an exception when the object
  is created, rather than when the request reaches Stripe.
- **Each client uses its own key.** Requests go through the client they were made with, so an application with several
  Stripe accounts can't send one account's request with another's key. The key is also kept out of `var_dump()`,
  `print_r()` and stack traces.
- **Tested against Stripe itself.** As well as offline tests on every supported version of PHP, live tests check that
  Stripe's test mode accepts what the package sends (see [Testing](docs/testing.md)).

## License
MIT. See [LICENSE](LICENSE).
