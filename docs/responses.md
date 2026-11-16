# Responses

- [What the methods return](#what-the-methods-return)
- [Reading a response](#reading-a-response)
- [Statuses](#statuses)
- [Related objects](#related-objects)
- [Narrower types](#narrower-types)
- [Anything else Stripe sent](#anything-else-stripe-sent)
- [Webhooks](#webhooks)

## What the methods return
Every `StripeClient` method returns this package's own readonly models, from the `Programster\Stripe\Responses`
namespace, rather than the Stripe SDK's objects, so your IDE knows what's in them:

| Method | Returns |
|---|---|
| `createHostedCheckoutSessionForSinglePayment()`, `createHostedCheckoutSessionForSubscription()` | `NewHostedCheckoutSession` |
| `createEmbeddedCheckoutSessionForSinglePayment()`, `createEmbeddedCheckoutSessionForSubscription()` | `NewEmbeddedCheckoutSession` |
| `retrieveCheckoutSession()` | `CheckoutSession` |
| `createCustomer()`, `retrieveCustomer()` | `Customer` |
| `createSubscription()`, `retrieveSubscription()`, `cancelSubscription()` | `Subscription` |
| `updateSubscriptionItem()` | `SubscriptionItem` |
| `retrieveInvoice()` | `Invoice` |
| `previewUpcomingInvoice()`, `previewSubscriptionChange()`, `previewNewSubscription()` | `InvoicePreview` (see [Subscriptions](subscriptions.md#previewing-an-invoice)) |
| `retrievePaymentIntent()` | `PaymentIntent` |
| `retrieveCharge()` | `Charge` |
| `retrieveRefund()` | `Refund` |
| `retrievePaymentMethod()` | `PaymentMethod` |
| `verifyWebhookEvent()` | `Event` (see [Webhooks](#webhooks)) |
| Each `list...()` method | A readonly collection of them, e.g. `CustomerCollection` (see [Listing](listing.md)) |

## Reading a response
Each field has a getter. Fields Stripe can leave empty return `null`, as most of a customer's do, for example:

```php
$client = new StripeClient('sk_test_...');
$customer = $client->retrieveCustomer('cus_123');

$customerId = $customer->getId();              // Always set.
$email = $customer->getEmail();                // A string, or null if it isn't set.
$city = $customer->getAddress()?->getCity();   // The address, and each part of it, can be empty.
$created = $customer->getCreated();            // A DateTimeImmutable, in UTC.
$metadata = $customer->getMetadata();
$userId = $metadata->hasKey('user_id') ? $metadata->getValue('user_id') : null;
```

- **Times** are `DateTimeImmutable`s, in UTC.
- **Amounts** are `Money`: `getAmount()` in the currency's smallest unit (e.g. pennies), and `getCurrency()`, as
  Stripe sends it (e.g. `"gbp"`). `hasCurrency(Currency::GBP)` compares it with the `Currency` enum.
- **Metadata** is a `Metadata` collection: `hasKey('user_id')` says whether a key is there, and `getValue('user_id')`
  gives its value, throwing an `ExceptionMissingMetadataKey` if it isn't. Keys are case-sensitive, as they are in
  Stripe. `toStripeArrayForm()` gives it all as an array.
- **Related objects** are given by their ID, e.g. `getCustomerId()`, whether Stripe sent just the ID or the whole
  object.

## Statuses
`getStatus()` returns the status exactly as Stripe sent it, as a string, so a status Stripe adds in future can't break
anything. To check one, use `hasStatus()`, which takes the enum, so the comparison can't be misspelt, or one of the
shortcuts:

```php
$subscription = $client->retrieveSubscription('sub_123');

if ($subscription->hasStatus(SubscriptionStatus::PAST_DUE))
{
    // Ask the customer to update their card.
}

$hasAccess = $subscription->isActive() || $subscription->isTrialing();
$statusToShow = $subscription->getStatus();    // e.g. "active".
```

Comparing `getStatus()` with a string yourself is how mistakes like `'cancelled'` (Stripe spells it `'canceled'`) slip
through, so prefer `hasStatus()`. The same goes for other values Stripe can add to, such as a payment method's type
(`hasType(PaymentMethodType::CARD)`).

## Related objects
A `fetch...()` method asks Stripe for a related object, such as a subscription's customer. Each call makes a request,
so it's named `fetch` rather than `get`:

```php
$customer = $subscription->fetchCustomer();
$items = $subscription->fetchItems();          // Every item, however many there are.

$session = $client->retrieveCheckoutSession('cs_test_123');

if ($session->getSubscriptionId() !== null)
{
    $subscription = $session->fetchSubscription();
}
```

- **A `fetch...()` for something that isn't there throws an `ExceptionNoRelatedObject`**, e.g. a checkout session's
  `fetchSubscription()` before it's been completed. Check the matching `get...Id()` first, which returns `null`.
- **`getItems()` only returns what Stripe included in the subscription.** Stripe only includes the first few, so if it
  left any out, `getItems()` throws an `ExceptionIncompleteList`, rather than quietly giving back only some of them.
  `hasAllItems()` says whether it's safe to call, and `fetchItems()` always gets them all.
- **An invoice's payments** aren't included unless they're asked for: `$invoice->fetchPayments()` gets them, or
  `listInvoicesWithPayments()` gives `InvoiceWithPayments`, whose `getPayments()` already has them.

Each model's related objects:

| Model | `fetch...()` methods |
|---|---|
| `CheckoutSession` | `fetchCustomer()`, `fetchSubscription()`, `fetchPaymentIntent()`, `fetchInvoice()` |
| `Customer` | `fetchSubscriptions()` (whatever their status), `fetchPaymentMethods()` |
| `Subscription` | `fetchCustomer()`, `fetchItems()`, `fetchLatestInvoice()`, `fetchDefaultPaymentMethod()`, `fetchUpcomingInvoicePreview()` |
| `SubscriptionItem` | `fetchSubscription()` |
| `Invoice` | `fetchCustomer()`, `fetchSubscription()`, `fetchPayments()` |
| `InvoicePayment` | `fetchInvoice()`, `fetchPaymentIntent()` |
| `PaymentIntent` | `fetchCustomer()`, `fetchLatestCharge()`, `fetchPaymentMethod()`, `fetchCharges()`, `fetchRefunds()` |
| `Charge` | `fetchCustomer()`, `fetchPaymentIntent()`, `fetchPaymentMethod()`, `fetchRefunds()` |
| `Refund` | `fetchCharge()`, `fetchPaymentIntent()` |
| `PaymentMethod` | `fetchCustomer()` |

The ones that return a collection get every match, however many there are.

## Narrower types
Where something guarantees more than Stripe always sends, a narrower type holds the guarantee, so the field can't be
null:

- **What a method asked for:** `listInvoicesWithPayments()` returns `InvoiceWithPayments`, which has the payments.
- **Something that isn't in Stripe:** the preview methods return an `InvoicePreview`, rather than an `Invoice`, as
  the invoice hasn't been created, so it can't be retrieved, paid or sent.
- **How a session was just made:** a `NewHostedCheckoutSession` always has its URL, and a `NewEmbeddedCheckoutSession`
  its client secret (see [Checkout sessions](checkout.md#hosted-or-embedded)).
- **An object's state:** `$session->toCompleted()` returns a `CompletedCheckoutSession`, which always has the
  customer's details, and throws an `ExceptionWrongStatus` if the session hasn't been completed (see
  [Checkout sessions](checkout.md#a-completed-session)).

Each wraps the general model, which `getSession()` or `getInvoice()` returns.

## Anything else Stripe sent
The models have the fields people commonly need. For anything else, `getStripeObject()` returns a fresh copy of the
SDK's object, exactly as Stripe sent it:

```php
$invoiceSettings = $customer->getStripeObject()->invoice_settings;
```

Changing the copy doesn't change the model. If there's a field you use often, it can be added to the model.

## Webhooks
`verifyWebhookEvent()` checks that a request to your webhook endpoint came from Stripe, from its signature, and gives
the `Event` it holds. Give it the request's body exactly as it arrived, its `Stripe-Signature` header, and the
endpoint's signing secret, as a `WebhookSigningSecret`:

```php
function handleStripeWebhook(StripeClient $client, WebhookSigningSecret $signingSecret): void
{
    try
    {
        $event = $client->verifyWebhookEvent(
            file_get_contents('php://input'),
            $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '',
            $signingSecret,
        );
    }
    catch (\Stripe\Exception\SignatureVerificationException $e)
    {
        // Not from Stripe, or not for this endpoint.
        http_response_code(400);
        exit;
    }

    if ($event->hasType(EventType::CUSTOMER_SUBSCRIPTION_UPDATED))
    {
        $subscription = $event->getSubscription();
        // Update your own records from $subscription->getStatus(), etc.
    }

    http_response_code(200);
}

// With the endpoint's signing secret, from the dashboard's webhook settings (or "stripe listen", for the CLI):
// handleStripeWebhook($client, new WebhookSigningSecret('whsec_...'));
```

- **The signing secret starts `whsec_`**, and each endpoint has its own. It isn't your API's secret key, so
  `WebhookSigningSecret` throws an `ExceptionInvalidValue` if given one, and it's kept out of `var_dump()` and
  `print_r()` output, like the client's key.
- **The body must be exactly what arrived.** Decoding the JSON and encoding it again changes it, so the signature no
  longer matches.
- **Old requests are rejected**, so one that's been recorded can't be replayed later. By default a signature can be up
  to 5 minutes old, as Stripe recommends. `toleranceSeconds` changes that, but must be at least 1.
- **Check the type with `hasType()`**, which takes the `EventType` enum, so it can't be misspelt. `getType()` gives the
  type as Stripe sent it, as Stripe has many more types than the enum, and adds new ones.
- **The object the event is about** comes as its model from `getCheckoutSession()`, `getCustomer()`,
  `getSubscription()`, `getInvoice()`, `getPaymentIntent()`, `getCharge()`, `getRefund()` or `getPaymentMethod()`,
  which each throw an `ExceptionUnexpectedResponse` if it's something else. `getDataObjectType()` says what it is (e.g.
  `"customer"`), and `getDataObject()` gives the SDK's object, for anything else.
- **An event is what the object was when it happened.** Stripe can send events late, or out of order, so for anything
  that matters, such as whether a subscription is still active, use the object's ID to retrieve it again.

`createFromStripeObject()` turns any SDK object into the matching model, e.g. one you already have from the SDK:

```php
$stripeEvent = \Stripe\Event::constructFrom([
    'id' => 'evt_123',
    'object' => 'event',
    'type' => 'customer.subscription.updated',
    'data' => ['object' => \Programster\Stripe\Testing\FakeStripeHttpClient::loadFixture('subscription-active')],
]);

$subscription = Subscription::createFromStripeObject($stripeEvent->data->object, $client);
$customerId = $subscription->getCustomerId();
```

It throws an `ExceptionUnexpectedResponse` if given the wrong kind of object, such as a charge where a subscription was
expected. (The example builds a made-up event from a recorded subscription.)

The models can't be serialized, as they hold the client, and so your secret key. Store an object's ID instead, and
retrieve it again when you need it.
