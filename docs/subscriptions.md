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
    existingCustomerOrCustomerAccount: new ExistingCustomer('cus_123'),
    items: new SubscriptionLineItemCollection(new SubscriptionItem(1, $price)),
    currency: Currency::GBP,
    defaultPaymentMethodId: 'pm_123',
    trialConfig: TrialConfig::createForNumDays(7),
    cancelAt: CancelAtEnum::MAX_PERIOD_END,
);
```

- **The customer** is an `ExistingCustomer`. A string is treated as a customer account's ID (one starting `acct_`), so
  a customer's ID passed as a string throws an `ExceptionInvalidValue`, explaining what to pass instead (see below).
- **`$cancelAt`** takes `true` to cancel at the end of the current period, a timestamp, or a `CancelAtEnum`.
- **A subscription's items can't have an adjustable quantity or dynamic tax rates**, which Stripe only allows in a
  checkout session, so a `SubscriptionItem` given either throws an `ExceptionInvalidValue`.
- **To send an invoice** rather than charge a card, pass `collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE`
  and `daysUntilDue`.

```php
// Throws ExceptionInvalidValue
$items = new SubscriptionLineItemCollection(new SubscriptionItem(1, $price));
$client->createSubscription('cus_123', $items, Currency::GBP);
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
```

## Cancelling a subscription
```php
$cancelled = $client->cancelSubscription(
    'sub_123',
    new CancellationDetails(userComment: 'Too expensive for me', feedback: CancellationFeedback::TOO_EXPENSIVE),
    prorate: true,
);
```
