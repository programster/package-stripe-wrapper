# Checkout sessions

- [Hosted or embedded](#hosted-or-embedded)
- [A one-off payment](#a-one-off-payment)
- [A subscription](#a-subscription)
- [The customer](#the-customer)
- [Other options](#other-options)
- [Retrieving a session](#retrieving-a-session)

A checkout session is a page, hosted by Stripe or embedded in your site, where the customer pays. There's a method for
each kind: `createCheckoutSessionForSinglePayment()` for a one-off payment, and `createCheckoutSessionForSubscription()`
for a subscription (which can also include one-off items). Each returns a [`CheckoutSession`](responses.md).

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
Each line item is for a price you've already created in Stripe, with `forPrice()`, or for a price created inline, with
`forPriceData()`. In the same way, an inline price is for an existing product, with `forProduct()`, or one created
inline, with `forProductData()`:

```php
$shirt = PriceDataForSinglePayment::forProductData(
    currency: Currency::GBP,
    unitAmount: 2000, // In pennies, so £20.00.
    productData: new ProductData(name: "T-shirt", description: "A designer t-shirt."),
);

$items = new SinglePaymentLineItemsCollection(
    SinglePaymentLineItem::forPriceData($shirt, quantity: 1),
    SinglePaymentLineItem::forPrice('price_123', quantity: 2),
);

$session = $client->createCheckoutSessionForSinglePayment(flowConfig: $hosted, items: $items);
$paymentPageUrl = $session->getUrl();
```

A price can't be negative, an `AdjustableQuantityConfig`'s minimum can't be more than its maximum, and a product's ID
can't be given where a price's is needed (or the other way round), so mistakes like these throw an
`ExceptionInvalidValue` where they're made, rather than when Stripe rejects the request:

```php
// Throws ExceptionInvalidValue
$refund = PriceDataForSinglePayment::forProduct(currency: Currency::GBP, unitAmount: -500, productId: 'prod_123');
```

```php
// Throws ExceptionInvalidValue (it's a product's ID)
$item = SinglePaymentLineItem::forPrice('prod_123', quantity: 1);
```

A price's ID isn't checked for starting `price_`, as a legacy plan's ID can be one you chose, and neither is a
product's for `prod_`, for the same reason.

## A subscription
A subscription's prices need a `RecurringConfig`, to say how often they're charged:

```php
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);

$plan = PriceDataForSubscriptionCheckoutSession::forProductData(
    currency: Currency::GBP,
    unitAmount: 999,
    productData: new ProductData(name: "Monthly plan"),
    recurring: $monthly,
);

$subscriptionData = new SubscriptionData(
    description: "Monthly plan",
    trialConfig: TrialConfig::createForNumDays(14, EndTrialBehaviorIfMissingPaymentMethod::CANCEL),
);

$session = $client->createCheckoutSessionForSubscription(
    flowConfig: $hosted,
    items: new SubscriptionCheckoutLineItemCollection(SubscriptionCheckoutLineItem::forPriceData($plan, 1)),
    subscriptionData: $subscriptionData,
);
```

A proration behaviour can only be given along with a billing cycle anchor, which Stripe requires, so
`new SubscriptionData(prorationBehavior: ProrationBehavior::NONE)` on its own throws an `ExceptionInvalidValue`.

## The customer
`$customer` says who the session is for: a customer that's already in Stripe, with `existing()`, or someone whose
email address you know, to fill it in for them, with `withEmail()`. Each method has its own type, a
`SinglePaymentCheckoutCustomer` or a `SubscriptionCheckoutCustomer`, as only a single payment lets you choose whether
Stripe creates a customer:

```php
// An existing customer, whose address Stripe may update from what they enter.
$existing = SinglePaymentCheckoutCustomer::existing('cus_123', new CustomerUpdateConfig(address: Updateable::AUTO));
$session = $client->createCheckoutSessionForSinglePayment($hosted, $items, customer: $existing);

// Their email address, with a customer always created for them.
$newCustomer = SinglePaymentCheckoutCustomer::withEmail('jo@example.com', CustomerCreation::ALWAYS);
$session = $client->createCheckoutSessionForSinglePayment($hosted, $items, customer: $newCustomer);

// No email address, but still always creating a customer.
$session = $client->createCheckoutSessionForSinglePayment(
    $hosted,
    $items,
    customer: SinglePaymentCheckoutCustomer::create(CustomerCreation::ALWAYS),
);

// For a subscription, Stripe always creates the customer.
$subscriber = SubscriptionCheckoutCustomer::withEmail('jo@example.com');
```

Stripe doesn't allow a customer to be created when there's already one, so `existing()` doesn't take a
`CustomerCreation`, and a `CustomerUpdateConfig` only goes with `existing()`. A customer's ID that doesn't start
`cus_`, or an email address that isn't one, throws an `ExceptionInvalidValue`, which catches them being mixed up:

```php
// Throws ExceptionInvalidValue
$customer = SinglePaymentCheckoutCustomer::withEmail('cus_123');
```

## Other options
Both methods take many more optional parameters, each typed and documented in `StripeClient`. Some of the most useful:

```php
$session = $client->createCheckoutSessionForSinglePayment(
    flowConfig: $hosted,
    items: $items,
    clientReferenceId: 'order-42',
    expiresAt: new DateTimeImmutable('+1 hour'),
    discount: Discount::createCoupon('coupon_123'),
    locale: Locale::FRENCH,
    billingAddressCollection: BillingAddressCollection::REQUIRED,
    shippingAddressCollection: new ShippingCountryCodeCollection(ShippingCountryCode::GB, ShippingCountryCode::FR),
    enablePhoneNumberCollection: true,
    customTextOptions: new CustomTextOptions(submit: "We'll ship your order within 2 working days."),
    metadata: new Metadata(new MetadataItem('order_id', '42')),
);
```

## Retrieving a session
For example, on the success page, to check what was paid:

```php
$session = $client->retrieveCheckoutSession('cs_test_123');
$isPaid = $session->isPaid();
```
