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
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionLineItem;
use Programster\Stripe\Models\PriceDataForSubscription;
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
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionLineItem;
use Programster\Stripe\Models\PriceDataForSubscription;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\StripeClient;

require_once(__DIR__ . '/../vendor/autoload.php');

# define your private Stripe API Key.
define('STRIPE_SECRET_KEY', 'sk_test_...');

$client = new StripeClient(STRIPE_SECRET_KEY);
$flowConfig = FlowConfig::createHosted(successUrl: "http://localhost/success");
$recurringConfig = new RecurringConfig(RecurringInterval::MONTH, 1);

$shirt = new PriceDataForSubscription(
    currency: Currency::GBP,
    unitAmount: 2000,
    productDataOrId: new ProductData(
        name: "T-shirt",
        description: "A designer t-shirt."
    ),
    recurring: $recurringConfig,
);

$shorts = new PriceDataForSubscription(
    currency: Currency::GBP,
    unitAmount: 1500,
    productDataOrId: new ProductData(
        name: "Shorts"
    ),
    recurring: $recurringConfig,
);

$items = new SubscriptionLineItemCollection(
    new SubscriptionLineItem(
        quantity: 1,
        priceDataOrPriceId: $shirt,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    ),
    new SubscriptionLineItem(
        quantity: 1,
        priceDataOrPriceId: $shorts,
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 100)
    )
);

$checkoutSession = $client->createheckoutSessionForSubscription(
    flowConfig: $flowConfig,
    items: $items,
);

# Redirect the user to the stripe payment page.
http_response_code(303);
header('Location: ' . $checkoutSession->url);
```
