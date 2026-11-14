# Subscriptions

- [Creating a subscription](#creating-a-subscription)
- [Reading a subscription](#reading-a-subscription)
- [Listing subscriptions](#listing-subscriptions)
- [Changing a subscription's items](#changing-a-subscriptions-items)
- [Cancelling a subscription](#cancelling-a-subscription)

Most of the time, a customer should start a subscription through a [checkout session](checkout.md#a-subscription), so
they enter their own payment details. Creating one directly is for when you already have them, or want Stripe to send
the customer an invoice instead.

## Creating a subscription
`createSubscription()` returns a [`Subscription`](responses.md):

```php
use Programster\Stripe\Models\SubscriptionItem;

$client = new StripeClient('sk_test_...');
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
$price = new PriceDataForSubscriptionItem(Currency::GBP, 999, 'prod_123', $monthly);

$subscription = $client->createSubscription(
    customer: SubscriptionCustomer::customer('cus_123'),
    items: new SubscriptionLineItemCollection(SubscriptionItem::forPriceData($price, quantity: 1)),
    currency: Currency::GBP,
    defaultPaymentMethodId: 'pm_123',
    billingCycleAnchor: BillingCycleAnchor::onDayOfMonth(1),
    cancelAt: CancelAt::atLongestItemPeriodEnd(),
    trialConfig: TrialConfig::createForNumDays(7),
);
```

- **The customer** is a `SubscriptionCustomer`: a customer, with `SubscriptionCustomer::customer('cus_...')`, or a
  customer account, with `SubscriptionCustomer::customerAccount('acct_...')`. An ID that doesn't start with the right
  prefix throws an `ExceptionInvalidValue` (see below).
- **An item** is for an existing price, with `SubscriptionItem::forPrice('price_...', quantity: 1)`, or one created
  inline, with `forPriceData()`. Invoice items (`SubscriptionInvoiceItem`) work the same way.
- **`$cancelAt`** is a `CancelAt`: `CancelAt::atPeriodEnd()`, `CancelAt::at(new DateTimeImmutable(...))`, or the
  latest or earliest end of the items' billing periods, with `CancelAt::atLongestItemPeriodEnd()` or
  `CancelAt::atShortestItemPeriodEnd()`.
- **`$billingCycleAnchor`** is a `BillingCycleAnchor`: a time, with `BillingCycleAnchor::at()`, or a day of the month
  (and optionally the month and time of day), with `BillingCycleAnchor::onDayOfMonth()`.
- **Times are `DateTimeImmutable`s**, e.g. `backdateStartDate: new DateTimeImmutable('-1 month')`. They aren't checked
  against the current time, as a subscription on a test clock goes by the clock's time instead.
- **A subscription's items can't have an adjustable quantity or dynamic tax rates**, which Stripe only allows in a
  checkout session, so a `SubscriptionItem` given either throws an `ExceptionInvalidValue`.
- **To send an invoice** rather than charge a card, pass `collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE`
  and `daysUntilDue`.

```php
// Throws ExceptionInvalidValue (a customer's ID isn't a customer account's)
$customer = SubscriptionCustomer::customerAccount('cus_123');
```

## Reading a subscription
```php
$subscription = $client->retrieveSubscription('sub_123');
$hasAccess = $subscription->isActive() || $subscription->isTrialing();

foreach ($subscription->fetchItems() as $item)
{
    $renews = $item->getCurrentPeriodEnd();    // Each item has its own billing period.
}

$customer = $subscription->fetchCustomer();
```

See [Responses](responses.md) for statuses, and the difference between `getItems()` and `fetchItems()`.

## Listing subscriptions
```php
$subscriptions = $client->listSubscriptions(
    customerId: 'cus_123',
    subscriptionStatus: SubscriptionStatus::ACTIVE,
);
```

## Changing a subscription's items
```php
$items = $client->listSubscriptionItems('sub_123');

foreach ($items as $item)
{
    $updated = $client->updateSubscriptionItem(
        $item->getId(),
        quantity: 2,
        prorationBehavior: ProrationBehavior::CREATE,
    );
}

// Or change an item's price.
$updated = $client->updateSubscriptionItem('si_123', price: SubscriptionItemPrice::forPrice('price_456'));
```

## Cancelling a subscription
```php
$cancelled = $client->cancelSubscription(
    'sub_123',
    new CancellationDetails(userComment: 'Too expensive for me', feedback: CancellationFeedback::TOO_EXPENSIVE),
    prorate: true,
);
```
