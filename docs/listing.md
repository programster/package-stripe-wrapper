# Listing and pagination

- [The list methods](#the-list-methods)
- [Pagination](#pagination)
- [Filtering by date](#filtering-by-date)

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
