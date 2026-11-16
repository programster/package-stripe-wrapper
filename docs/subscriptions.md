# Subscriptions

- [Creating a subscription](#creating-a-subscription)
- [Reading a subscription](#reading-a-subscription)
- [Listing subscriptions](#listing-subscriptions)
- [Changing a subscription's items](#changing-a-subscriptions-items)
- [Previewing an invoice](#previewing-an-invoice)
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
    customer: SubscriptionCustomer::createUsingCustomerId('cus_123'),
    items: new SubscriptionLineItemCollection(SubscriptionItem::createUsingInlinePrice($price, quantity: 1)),
    currency: Currency::GBP,
    defaultPaymentMethodId: 'pm_123',
    billingCycleAnchor: BillingCycleAnchor::createOnDayOfMonth(1),
    cancelAt: CancelAt::createAtLongestItemPeriodEnd(),
    trialConfig: TrialConfig::createUsingNumDays(7),
);
```

- **The customer** is a `SubscriptionCustomer`: a customer, with
  `SubscriptionCustomer::createUsingCustomerId('cus_...')` (or `createUsingCustomer()`, given a `Customer` you've
  retrieved or created), or a customer account, with `SubscriptionCustomer::createUsingCustomerAccountId('acct_...')`.
  An ID that doesn't start with the right prefix throws an `ExceptionInvalidValue` (see below).
- **An item** is for a price that's already in Stripe, with
  `SubscriptionItem::createUsingExistingPrice('price_...', quantity: 1)`, or one described in the call, which Stripe
  creates along with it, with `createUsingInlinePrice()`. Invoice items (`SubscriptionInvoiceItem`) work the same way.
- **`$cancelAt`** is a `CancelAt`: `CancelAt::createAtPeriodEnd()`, `CancelAt::createAt(new DateTimeImmutable(...))`, or
  the latest or earliest end of the items' billing periods, with `CancelAt::createAtLongestItemPeriodEnd()` or
  `CancelAt::createAtShortestItemPeriodEnd()`.
- **`$billingCycleAnchor`** is a `BillingCycleAnchor`: a time, with `BillingCycleAnchor::createAt()`, or a day of the
  month (and optionally the month and time of day), with `BillingCycleAnchor::createOnDayOfMonth()`.
- **Times are `DateTimeImmutable`s**, e.g. `backdateStartDate: new DateTimeImmutable('-1 month')`. They aren't checked
  against the current time, as a subscription on a test clock goes by the clock's time instead.
- **A subscription's items can't have an adjustable quantity or dynamic tax rates**, which Stripe only allows in a
  checkout session, so a `SubscriptionItem` given either throws an `ExceptionInvalidValue`.
- **To send an invoice** rather than charge a card, pass `collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE`
  and `daysUntilDue`.

```php
// Throws ExceptionInvalidValue (a customer's ID isn't a customer account's)
$customer = SubscriptionCustomer::createUsingCustomerAccountId('cus_123');
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
$newPrice = SubscriptionItemPrice::createUsingExistingPrice('price_456');
$updated = $client->updateSubscriptionItem('si_123', price: $newPrice);
```

## Previewing an invoice
Three methods show what an invoice would be, without creating anything in Stripe. Each returns an `InvoicePreview`,
with the amounts (`getAmountDue()`, `getSubtotal()`, `getTotal()`), the dates, and each line, from `getLines()`:

```php
// A subscription's next invoice, as it stands (or $subscription->fetchUpcomingInvoicePreview()).
$upcoming = $client->previewUpcomingInvoice('sub_123');

foreach ($upcoming->getLines() as $line)
{
    $description = $line->getDescription();         // e.g. "1 × Premium (at £10.00 / month)"
    $amount = $line->getAmount()->getAmount();      // In pennies. A credit for unused time is negative.
    $isProration = $line->isProration();
}

// The next invoice if the subscription's items were changed, including the prorations for the change.
$changes = new SubscriptionItemChangeCollection(
    SubscriptionItemChange::createUpdateUsingItemId('si_123', quantity: 3),
    SubscriptionItemChange::createRemovalUsingItemId('si_456'),
    SubscriptionItemChange::createAddition(SubscriptionItem::createUsingExistingPrice('price_789', 1)),
);

$preview = $client->previewSubscriptionChange('sub_123', $changes, ProrationBehavior::CREATE);

// Make the change as of the same moment, so the prorations are exactly what was previewed.
$updated = $client->updateSubscriptionItem('si_123', quantity: 3, prorationDate: $preview->getProrationDate());

// The first invoice of a subscription that hasn't been created yet, e.g. to show the price with tax before they
// subscribe. It takes the same customer and items as createSubscription().
$firstInvoice = $client->previewNewSubscription(
    SubscriptionCustomer::createUsingCustomerId('cus_123'),
    new SubscriptionLineItemCollection(SubscriptionItem::createUsingExistingPrice('price_789', 1)),
);
```

- **Prorations depend on the moment they're worked out**, so pass the preview's `getProrationDate()` to
  `updateSubscriptionItem()` when making the change, or the customer pays a slightly different amount from the one
  they were shown. `previewSubscriptionChange()` also takes a `$prorationDate`, to preview a change as of another time.
- **A change is to an existing item, by its ID** (`si_...`), or to one from `getItems()`, with
  `createUpdateUsingItem()` or `createRemovalUsingItem()`. An update needs a new price, a new quantity, or both, and an
  item can only be changed once in a preview, so these throw:

```php
// Throws ExceptionInvalidValue (nothing would change)
$change = SubscriptionItemChange::createUpdateUsingItemId('si_123');
```

```php
// Throws ExceptionDuplicateKey (put both in one change)
$changes = new SubscriptionItemChangeCollection(
    SubscriptionItemChange::createUpdateUsingItemId('si_123', quantity: 2),
    SubscriptionItemChange::createRemovalUsingItemId('si_123'),
);
```

- **`getLines()` only returns what Stripe included.** Stripe only includes the first few lines, so if it left any out,
  `getLines()` throws an `ExceptionIncompleteList`. `hasAllLines()` says whether it's safe to call. The totals always
  include every line.
- **A preview isn't an `Invoice`**, as it isn't in Stripe, so it has no number, link or PDF, and can't be retrieved or
  paid.

## Cancelling a subscription
```php
$cancelled = $client->cancelSubscription(
    'sub_123',
    new CancellationDetails(userComment: 'Too expensive for me', feedback: CancellationFeedback::TOO_EXPENSIVE),
    prorate: true,
);
```
