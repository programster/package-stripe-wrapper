# Listing and pagination

- [The list methods](#the-list-methods)
- [Pagination](#pagination)
- [Filtering by date](#filtering-by-date)
- [Jumping ahead](#jumping-ahead)

## The list methods
Each returns Stripe's `\Stripe\Collection`, whose `data` holds the results:

| Method | Lists |
|---|---|
| `listCustomers()` | Customers, optionally by email address. |
| `listCustomerPaymentMethods()` | A customer's payment methods, optionally of one type. |
| `listSubscriptions()` | Subscriptions, by customer, price, status, collection method and more. |
| `listSubscriptionItems()` | A subscription's items. |
| `listInvoices()` | Invoices, by customer, subscription, status and more. |
| `listPaymentIntents()` | Payment intents, optionally by customer. |
| `listCharges()` | Charges, by customer, payment intent or transfer group. |
| `listRefunds()` | Refunds, optionally by charge or payment intent. |

`listInvoices()` includes each invoice's payments by default (`expandPayments: true`), as that's the only way to find
the payment intents that paid for a subscription (subscription, then invoices, then payment intents).

## Pagination
Each list method returns up to `$limit` results, which defaults to 100, Stripe's maximum. Stripe allows 1 to 100, so
anything else throws an `ExceptionInvalidValue`:

```php
$client = new StripeClient('sk_test_...');

$page = $client->listCustomers(limit: 50);

if ($page->has_more)
{
    $lastCustomer = $page->data[count($page->data) - 1];
    $nextPage = $client->listCustomers(cursorStartingAfter: $lastCustomer->id, limit: 50);
}
```

```php
// Throws ExceptionInvalidValue
$client->listCustomers(limit: 500);
```

## Filtering by date
A `TimePeriod` filters by when something was created, as unix timestamps. Both ends are inclusive by default:

```php
$lastWeek = new TimePeriod(start: strtotime('-7 days'), end: time());
$recentCharges = $client->listCharges(created: $lastWeek);

// Or exclusive, e.g. to page through by time without repeating the boundary.
$after = new TimePeriod(start: strtotime('-7 days'), end: time(), isStartInclusive: false, isEndInclusive: false);
```

## Jumping ahead
Stripe's lists only page with cursors (the ID of the last object you got), with no page number or offset, so there's
no way to jump straight to "page 4". Something has to go through pages 1 to 3 first. Instead:

- **To jump to a point in time, filter by date.** Most lists can be filtered by when things were created, and a date
  range is a single request, however far back it is. This is usually what "page 4" really means, e.g. last month's
  charges:

```php
$lastMonth = new TimePeriod(
    start: strtotime('first day of last month 00:00:00'),
    end: strtotime('last day of last month 23:59:59'),
);

$lastMonthsCharges = $client->listCharges(created: $lastMonth);
```

- **To page through a list, keep each page's cursor.** On a web page, put the ID of the last object shown into the
  "next" link, so the next request can carry on from it, and keep the cursors of earlier pages for "previous". That's
  why Stripe's own dashboard only has "next" and "previous", rather than page numbers:

```php
// e.g. from the "next" link's query string, such as ?after=cus_123, or null for the first page.
$after = 'cus_123';

$page = $client->listCustomers(cursorStartingAfter: $after, limit: 50);
$lastShown = $page->data[count($page->data) - 1] ?? null;
$nextLink = ($page->has_more && $lastShown !== null) ? "?after={$lastShown->id}" : null;
```
