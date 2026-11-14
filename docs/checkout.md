# Checkout sessions

- [Hosted or embedded](#hosted-or-embedded)
- [A one-off payment](#a-one-off-payment)
- [A subscription](#a-subscription)
- [The customer](#the-customer)
- [Other options](#other-options)
- [Retrieving a session](#retrieving-a-session)

A checkout session is a page, hosted by Stripe or embedded in your site, where the customer pays. There's a method for
each kind: `createCheckoutSessionForSinglePayment()` for a one-off payment, and `createCheckoutSessionForSubscription()`
for a subscription (which can also include one-off items). Each returns Stripe's `\Stripe\Checkout\Session`.

## Hosted or embedded
A `FlowConfig` says where the checkout is shown, and where the customer goes afterwards:

```php
$client = new StripeClient('sk_test_...');

// On Stripe's own page, which the customer is sent to, and returned from.
$hosted = FlowConfig::createHosted(
    successUrl: "https://example.com/success",
    cancelUrl: "https://example.com/basket",
);

// Embedded in your own page, with Stripe's JavaScript. The session's client_secret is what the page needs.
$embedded = FlowConfig::createEmbedded(
    returnUrl: "https://example.com/return",
    redirectOnCompletion: RedirectOnCompletion::IF_REQUIRED,
);
```

## A one-off payment
Each line item is either the ID of a price you've already created in Stripe, or the price's details:

```php
$shirt = new PriceDataForSinglePayment(
    currency: Currency::GBP,
    unitAmount: 2000, // In pennies, so £20.00.
    productDataOrId: new ProductData(name: "T-shirt", description: "A designer t-shirt."),
);

$items = new SinglePaymentLineItemsCollection(
    new SinglePaymentLineItem(quantity: 1, priceDataOrPriceId: $shirt),
    new SinglePaymentLineItem(quantity: 2, priceDataOrPriceId: 'price_123'),
);

$session = $client->createCheckoutSessionForSinglePayment(flowConfig: $hosted, items: $items);
$paymentPageUrl = $session->url;
```

A price can't be negative, and an `AdjustableQuantityConfig`'s minimum can't be more than its maximum, so mistakes like
these throw an `ExceptionInvalidValue` where they're made, rather than when Stripe rejects the request:

```php
// Throws ExceptionInvalidValue
$refund = new PriceDataForSinglePayment(currency: Currency::GBP, unitAmount: -500, productDataOrId: 'prod_123');
```

## A subscription
A subscription's prices need a `RecurringConfig`, to say how often they're charged:

```php
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);

$plan = new PriceDataForSubscriptionCheckoutSession(
    currency: Currency::GBP,
    unitAmount: 999,
    productDataOrId: new ProductData(name: "Monthly plan"),
    recurring: $monthly,
);

$subscriptionData = new SubscriptionData(
    description: "Monthly plan",
    trialConfig: TrialConfig::createForNumDays(14, EndTrialBehaviorIfMissingPaymentMethod::CANCEL),
);

$session = $client->createCheckoutSessionForSubscription(
    flowConfig: $hosted,
    items: new SubscriptionCheckoutLineItemCollection(new SubscriptionCheckoutLineItem(1, $plan)),
    subscriptionData: $subscriptionData,
);
```

A proration behaviour can only be given along with a billing cycle anchor, which Stripe requires, so
`new SubscriptionData(prorationBehavior: ProrationBehavior::NONE)` on its own throws an `ExceptionInvalidValue`.

## The customer
Pass either an `ExistingCustomer`, for a customer that's already in Stripe, or the customer's email address, to prefill
it:

```php
// An existing customer, whose address Stripe may update from what they enter.
$existing = new ExistingCustomer('cus_123', new CustomerUpdateConfig(address: Updateable::AUTO));
$session = $client->createCheckoutSessionForSinglePayment($hosted, $items, existingStripeCustomerOrEmail: $existing);

// Or just their email address.
$session = $client->createCheckoutSessionForSinglePayment(
    $hosted,
    $items,
    existingStripeCustomerOrEmail: 'jo@example.com',
);
```

A string that isn't an email address throws an `ExceptionInvalidValue`, as passing a customer's ID as a string is an
easy mistake to make:

```php
// Throws ExceptionInvalidValue
$client->createCheckoutSessionForSinglePayment($hosted, $items, existingStripeCustomerOrEmail: 'cus_123');
```

## Other options
Both methods take many more optional parameters, each typed and documented in `StripeClient`. Some of the most useful:

```php
$session = $client->createCheckoutSessionForSinglePayment(
    flowConfig: $hosted,
    items: $items,
    clientReferenceId: 'order-42',
    discount: Discount::createCoupon('coupon_123'),
    locale: Locale::FRENCH,
    billingAddressCollection: BillingAddressCollection::REQUIRED,
    shippingAddressCollection: new CountryCodeCollection(CountryCode::GB, CountryCode::FR),
    enablePhoneNumberCollection: true,
    customTextOptions: new CustomTextOptions(submit: "We'll ship your order within 2 working days."),
    metadata: new Metadata(new MetadataItem('order_id', '42')),
);
```

## Retrieving a session
For example, on the success page, to check what was paid:

```php
$session = $client->retrieveCheckoutSession('cs_test_123');
$status = $session->status;
```
