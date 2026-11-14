# Listing

- [The list methods](#the-list-methods)
- [How many you get](#how-many-you-get)
- [Paging through a list](#paging-through-a-list)
- [Filtering by date](#filtering-by-date)
- [Jumping ahead](#jumping-ahead)

## The list methods
Each returns a readonly collection of [response models](responses.md) of everything that matches, asking Stripe for as
many pages as it takes, so there are no pages to handle:

| Method | Returns |
|---|---|
| `listCustomers()` | `CustomerCollection`, optionally by email address. |
| `listCustomerPaymentMethods()` | `PaymentMethodCollection` of a customer's payment methods. |
| `listSubscriptions()` | `SubscriptionCollection`, by customer, price, status, collection method and more. |
| `listSubscriptionItems()` | `SubscriptionItemCollection` of a subscription's items. |
| `listInvoices()` | `InvoiceCollection`, by customer, subscription, status and more. |
| `listInvoicesWithPayments()` | `InvoiceWithPaymentsCollection`: the same, with each invoice's payments. |
| `listInvoicePayments()` | `InvoicePaymentCollection` of an invoice's payments. |
| `listPaymentIntents()` | `PaymentIntentCollection`, optionally by customer. |
| `listCharges()` | `ChargeCollection`, by customer, payment intent or transfer group. |
| `listRefunds()` | `RefundCollection`, optionally by charge or payment intent. |

```php
$client = new StripeClient('sk_test_...');
$customers = $client->listCustomers(email: 'jo@example.com');

foreach ($customers as $customer)
{
    print $customer->getId() . PHP_EOL;
}

$newest = $customers->first();
$howMany = count($customers);
```

Lists are newest first. The collections can be sorted, filtered and combined (see
[programster/collections](https://github.com/programster/package-collections)), and can't be changed.

## How many you get
Without a limit, a list method gets up to 1,000 (`ResultLimit::DEFAULT_SAFETY_CAP`). If more than that match, it
throws an `ExceptionTooManyResults`, rather than quietly giving back only some of them, as getting them all could take
many requests, and a lot of memory. To get a different number, pass a `ResultLimit`:

```php
// Up to 50. If more match, only the newest 50 are given, without an exception.
$recent = $client->listCustomers(limit: ResultLimit::upTo(50));

// Every one, however many there are. Only do this when the filters keep the number small enough.
$everyone = $client->listCustomers(email: 'jo@example.com', limit: ResultLimit::all());
```

```php
// Throws ExceptionInvalidValue
$nothing = ResultLimit::upTo(0);
```

## Paging through a list
To page through a list yourself, e.g. 50 at a time on a web page, start each page after the last object of the one
before. An empty collection means there are no more:

```php
$page = $client->listCustomers(limit: ResultLimit::upTo(50));

if (!$page->isEmpty())
{
    $nextPage = $client->listCustomers(limit: ResultLimit::upTo(50), startingAfter: $page->last()->getId());
}
```

`endingBefore` goes the other way, back towards the newest.

## Filtering by date
A `TimePeriod` filters by when something was created, between two `DateTimeImmutable`s. Both ends are inclusive by
default, and an end before the start throws an `ExceptionInvalidValue`:

```php
$lastWeek = new TimePeriod(start: new DateTimeImmutable('-7 days'), end: new DateTimeImmutable());
$recentCharges = $client->listCharges(created: $lastWeek);

// Or exclusive.
$exclusive = new TimePeriod(
    start: new DateTimeImmutable('-7 days'),
    end: new DateTimeImmutable(),
    isStartInclusive: false,
    isEndInclusive: false,
);
```

## Jumping ahead
Stripe's lists only page with cursors (the ID of the last object you got), with no page number or offset, so there's
no way to jump straight to "page 4". Something has to go through pages 1 to 3 first. Instead:

- **To jump to a point in time, filter by date.** Most lists can be filtered by when things were created, and a date
  range is a single request, however far back it is. This is usually what "page 4" really means, e.g. last month's
  charges:

```php
$lastMonth = new TimePeriod(
    start: new DateTimeImmutable('first day of last month 00:00:00'),
    end: new DateTimeImmutable('last day of last month 23:59:59'),
);

$lastMonthsCharges = $client->listCharges(created: $lastMonth, limit: ResultLimit::all());
```

- **To page through a list, keep each page's cursor.** On a web page, put the ID of the last object shown into the
  "next" link, so the next request can carry on from it, and keep the cursors of earlier pages for "previous". That's
  why Stripe's own dashboard only has "next" and "previous", rather than page numbers:

```php
// e.g. from the "next" link's query string, such as ?after=cus_123, or null for the first page.
$after = 'cus_123';

$page = $client->listCustomers(limit: ResultLimit::upTo(50), startingAfter: $after);
$nextLink = $page->isEmpty() ? null : "?after=" . $page->last()->getId();
```
