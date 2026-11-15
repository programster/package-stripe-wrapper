# Checkout sessions

- [Hosted or embedded](#hosted-or-embedded)
- [A one-off payment](#a-one-off-payment)
- [A subscription](#a-subscription)
- [The customer](#the-customer)
- [Other options](#other-options)
- [Retrieving a session](#retrieving-a-session)
- [A completed session](#a-completed-session)

A checkout session is a page, hosted by Stripe or embedded in your site, where the customer pays. There's a method for
each kind of payment and each place it's shown:

| | Hosted by Stripe | Embedded in your site |
|---|---|---|
| A one-off payment | `createHostedCheckoutSessionForSinglePayment()` | `createEmbeddedCheckoutSessionForSinglePayment()` |
| A subscription (which can also include one-off items) | `createHostedCheckoutSessionForSubscription()` | `createEmbeddedCheckoutSessionForSubscription()` |

## Hosted or embedded
A hosted session's method takes a `HostedFlow`, and returns a `NewHostedCheckoutSession`, whose `getUrl()` is the
payment page to send the customer to. An embedded session's method takes an `EmbeddedFlow`, and returns a
`NewEmbeddedCheckoutSession`, whose `getClientSecret()` is what Stripe's JavaScript needs to show the payment form.
Each is certain to have its URL or client secret, so neither can be null, and a flow can't be given to the other kind
of method. `getSession()` returns the [`CheckoutSession`](responses.md) itself, for its other details.

```php
$client = new StripeClient('sk_test_...');

// On Stripe's own page, which the customer is sent to, and returned from.
$hosted = new HostedFlow(
    successUrl: "https://example.com/success",
    cancelUrl: "https://example.com/basket",
);

// Embedded in your own page, with Stripe's JavaScript.
$embedded = new EmbeddedFlow(
    returnUrl: "https://example.com/return",
    redirectOnCompletion: RedirectOnCompletion::IF_REQUIRED,
);
```

The URL and client secret are only certain when the session has just been created: Stripe empties the URL once the
session is completed or expires, so a session retrieved later is a plain `CheckoutSession`, whose `getUrl()` and
`getClientSecret()` can return null.

## A one-off payment
Each line item's price is either one that's already in Stripe, with `createUsingExistingPrice()` and the price's ID, or
one described in the call, with `createUsingInlinePrice()`, which Stripe creates along with the session. An existing
price has to have been created beforehand (in the dashboard or through the API), while an inline one needs nothing set
up first. In the same way, an inline price is for a product that's already in Stripe, with
`createUsingExistingProduct()`, or one described in the call, with `createUsingInlineProduct()`:

```php
$shirt = PriceDataForSinglePayment::createUsingInlineProduct(
    currency: Currency::GBP,
    unitAmount: 2000, // In pennies, so £20.00.
    productData: new ProductData(name: "T-shirt", description: "A designer t-shirt."),
);

$items = new SinglePaymentLineItemsCollection(
    SinglePaymentLineItem::createUsingInlinePrice($shirt, quantity: 1),
    SinglePaymentLineItem::createUsingExistingPrice('price_123', quantity: 2),
);

$session = $client->createHostedCheckoutSessionForSinglePayment(flow: $hosted, items: $items);
$paymentPageUrl = $session->getUrl();

// Or, embedded in your own page.
$embeddedSession = $client->createEmbeddedCheckoutSessionForSinglePayment(flow: $embedded, items: $items);
$clientSecret = $embeddedSession->getClientSecret();
```

A price can't be negative, an `AdjustableQuantityConfig`'s minimum can't be more than its maximum, and a product's ID
can't be given where a price's is needed (or the other way round), so mistakes like these throw an
`ExceptionInvalidValue` where they're made, rather than when Stripe rejects the request:

```php
// Throws ExceptionInvalidValue
$refund = PriceDataForSinglePayment::createUsingExistingProduct(
    currency: Currency::GBP,
    unitAmount: -500,
    productId: 'prod_123',
);
```

```php
// Throws ExceptionInvalidValue (it's a product's ID)
$item = SinglePaymentLineItem::createUsingExistingPrice('prod_123', quantity: 1);
```

A price's ID isn't checked for starting `price_`, as a legacy plan's ID can be one you chose, and neither is a
product's for `prod_`, for the same reason.

## A subscription
A subscription's prices need a `RecurringConfig`, to say how often they're charged:

```php
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);

$plan = PriceDataForSubscriptionCheckoutSession::createUsingInlineProduct(
    currency: Currency::GBP,
    unitAmount: 999,
    productData: new ProductData(name: "Monthly plan"),
    recurring: $monthly,
);

$subscriptionData = new SubscriptionData(
    description: "Monthly plan",
    trialConfig: TrialConfig::createUsingNumDays(14, EndTrialBehaviorIfMissingPaymentMethod::CANCEL),
);

$session = $client->createHostedCheckoutSessionForSubscription(
    flow: $hosted,
    items: new SubscriptionCheckoutLineItemCollection(SubscriptionCheckoutLineItem::createUsingInlinePrice($plan, 1)),
    subscriptionData: $subscriptionData,
);
```

A proration behaviour can only be given along with a billing cycle anchor, which Stripe requires, so
`new SubscriptionData(prorationBehavior: ProrationBehavior::NONE)` on its own throws an `ExceptionInvalidValue`.

## The customer
`$customer` says who the session is for: a customer that's already in Stripe, with `createUsingCustomerId()` (or
`createUsingCustomer()`, given a `Customer` you've retrieved or created), or someone whose email address you know, to
fill it in for them, with `createUsingEmail()`. Each method has its own type, a
`SinglePaymentCheckoutCustomer` or a `SubscriptionCheckoutCustomer`, as only a single payment lets you choose whether
Stripe creates a customer:

```php
// An existing customer, whose address Stripe may update from what they enter.
$updateAddress = new CustomerUpdateConfig(address: Updateable::AUTO);
$existing = SinglePaymentCheckoutCustomer::createUsingCustomerId('cus_123', $updateAddress);
$session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $items, customer: $existing);

// Their email address, with a customer always created for them.
$newCustomer = SinglePaymentCheckoutCustomer::createUsingEmail('jo@example.com', CustomerCreation::ALWAYS);
$session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $items, customer: $newCustomer);

// No email address, but still always creating a customer.
$session = $client->createHostedCheckoutSessionForSinglePayment(
    $hosted,
    $items,
    customer: SinglePaymentCheckoutCustomer::create(CustomerCreation::ALWAYS),
);

// For a subscription, Stripe always creates the customer.
$subscriber = SubscriptionCheckoutCustomer::createUsingEmail('jo@example.com');

// Or a customer you've just created.
$created = $client->createCustomer(email: 'jo@example.com', name: 'Jo Bloggs');

$session = $client->createHostedCheckoutSessionForSinglePayment(
    $hosted,
    $items,
    customer: SinglePaymentCheckoutCustomer::createUsingCustomer($created),
);
```

Stripe doesn't allow a customer to be created when there's already one, so `createUsingCustomerId()` and
`createUsingCustomer()` don't take a `CustomerCreation`, and a `CustomerUpdateConfig` only goes with them. A customer's
ID that doesn't start `cus_`, or an email address that isn't one, throws an `ExceptionInvalidValue`, which catches them
being mixed up:

```php
// Throws ExceptionInvalidValue
$customer = SinglePaymentCheckoutCustomer::createUsingEmail('cus_123');
```

## Other options
The methods take many more optional parameters, each typed and documented in `StripeClient`. Some of the most useful:

```php
$session = $client->createHostedCheckoutSessionForSinglePayment(
    flow: $hosted,
    items: $items,
    clientReferenceId: 'order-42',
    expiresAt: new DateTimeImmutable('+1 hour'),
    discount: Discount::createUsingCouponId('coupon_123'),
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

## A completed session
`toCompleted()` returns a `CompletedCheckoutSession`, whose `getCustomerDetails()` is certain to be there, e.g. when
handling a `checkout.session.completed` webhook. It throws an `ExceptionWrongStatus` if the session hasn't been
completed, so check `isComplete()` first:

```php
// Or, in a webhook, CheckoutSession::createFromStripeObject($event->data->object, $client).
$session = $client->retrieveCheckoutSession('cs_test_123');

if ($session->isComplete())
{
    $email = $session->toCompleted()->getCustomerDetails()->getEmail();
}
```

The details' fields, such as the email address, can still be null, as Stripe only fills in what was collected.
