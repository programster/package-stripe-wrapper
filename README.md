Stripe Wrapper
==============

A wrapper around the [stripe/stripe-php](https://packagist.org/packages/stripe/stripe-php) package to make it easier 
to use Stripe without having to read the documentation. You just need to fill in the constructors
and their parameters.


## Example Usage

Below are some examples for creating a checkout session.


#### Single Payment Example
This is an example of the most common use-case, of just charging the customer once for a set of
items they wish to purchase.

```php
<?php

use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\StripeClient;

require_once(__DIR__ . '/../vendor/autoload.php');

# define your private Stripe API Key.
define('STRIPE_SECRET_KEY', 'sk_test_...');

$client = new StripeClient(STRIPE_SECRET_KEY);
$flowConfig = FlowConfig::createHosted(successUrl: "http://localhost/success");

$shirt = new PriceDataForSinglePayment(
    currency: Currency::GBP,
    unitAmount: 2000,
    productDataOrId: new ProductData(
        name: "T-shirt",
        description: "A designer t-shirt."
    )
);

$shorts = new PriceDataForSinglePayment(
    currency: Currency::GBP,
    unitAmount: 1500,
    productDataOrId: new ProductData(
        name: "Shorts"
    )
);

$items = new SinglePaymentLineItemsCollection(
    new SinglePaymentLineItem(
        quantity: 1,
        priceDataOrPriceId: $shirt,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    ),
    new SinglePaymentLineItem(
        quantity: 1,
        priceDataOrPriceId: $shorts,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    )
);

$checkoutSession = $client->createCheckoutSessionForSinglePayment(
    flowConfig: $flowConfig,
    items: $items,
);

# Redirect the user to the stripe payment page.
http_response_code(303);
header('Location: ' . $checkoutSession->url);
```


#### Subscription Example
The example below will create a subscription checkout session, charging the user once a month. 
This is could be for if you were setting up your own Dollar Shave club, or Netflix subscription.

```php
<?php

use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\StripeClient;

require_once(__DIR__ . '/../vendor/autoload.php');

# define your private Stripe API Key.
define('STRIPE_SECRET_KEY', 'sk_test_...');

$client = new StripeClient(STRIPE_SECRET_KEY);
$flowConfig = FlowConfig::createHosted(successUrl: "http://localhost/success");
$recurringConfig = new RecurringConfig(RecurringInterval::MONTH, 1);

$shirt = new PriceDataForSubscriptionCheckoutSession(
    currency: Currency::GBP,
    unitAmount: 2000,
    productDataOrId: new ProductData(
        name: "T-shirt",
        description: "A designer t-shirt."
    ),
    recurring: $recurringConfig,
);

$shorts = new PriceDataForSubscriptionCheckoutSession(
    currency: Currency::GBP,
    unitAmount: 1500,
    productDataOrId: new ProductData(
        name: "Shorts"
    ),
    recurring: $recurringConfig,
);

$items = new SubscriptionCheckoutLineItemCollection(
    new SubscriptionCheckoutLineItem(
        quantity: 1,
        priceDataOrPriceId: $shirt,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    ),
    new SubscriptionCheckoutLineItem(
        quantity: 1,
        priceDataOrPriceId: $shorts,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    )
);

$checkoutSession = $client->createCheckoutSessionForSubscription(
    flowConfig: $flowConfig,
    items: $items,
);

# Redirect the user to the stripe payment page.
http_response_code(303);
header('Location: ' . $checkoutSession->url);
```


## Running the Tests
The smoke tests call every public method of `StripeClient`, and build the models and collections, checking exactly
what would be sent to Stripe. They never contact Stripe: every request is caught by a fake HTTP client, so no API key
or network access is needed.

To run them on every supported version of PHP (8.2 and up), each in its own Docker container:

```bash
composer install
testing/run-all-php-versions.sh
```

Pass versions to test only those (e.g. `testing/run-all-php-versions.sh 8.2 8.5`), or `--rebuild` to rebuild the
Docker images first, e.g. to pick up the latest patch releases. To run them once on your local PHP instead:

```bash
php -d zend.assertions=1 testing/main.php
```


### Live Tests Against Stripe's Test Mode
The smoke tests can only check what this package sends, not that Stripe accepts it. The live tests send real
requests to Stripe's test mode, to check that too. They refuse to run with anything other than a test-mode key, so
they can't create real customers or charge real cards.

1. Copy `testing/settings.php.tmpl` to `testing/settings.php` (which git ignores), and fill in your test-mode secret
   key, from https://dashboard.stripe.com/test/apikeys.
2. Optionally, turn on the extra tests for Stripe Tax, terms of service consent and Stripe Connect, once they're set
   up in your test-mode account, as described in the template.
3. Run them:

```bash
php -d zend.assertions=1 testing/main-live.php
```

Everything the tests create is cleaned up afterwards, as far as Stripe allows. Subscription customers are on a test
clock, which is deleted with everything attached to it. Other customers and the coupon are deleted, and the tax rate
and product are archived. Products that checkout sessions create from inline product data can't be removed through
the API, so they stay in test mode, marked with `created_by: programster-stripe-wrapper-live-tests` in their metadata.
