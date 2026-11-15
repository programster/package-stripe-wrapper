# Examples

- [Selling a product set up in Stripe's dashboard](#selling-a-product-set-up-in-stripes-dashboard)
- [Checking out a basket from your own database](#checking-out-a-basket-from-your-own-database)
- [Knowing which order was paid for](#knowing-which-order-was-paid-for)
- [An embedded checkout, from a JSON endpoint](#an-embedded-checkout-from-a-json-endpoint)
- [Promotion codes and coupons](#promotion-codes-and-coupons)
- [Asking the customer a question at checkout](#asking-the-customer-a-question-at-checkout)
- [A monthly plan with a free trial](#a-monthly-plan-with-a-free-trial)
- [Fulfilling orders from a webhook](#fulfilling-orders-from-a-webhook)
- [Checking whether a customer has access](#checking-whether-a-customer-has-access)
- [Moving a subscriber to another plan](#moving-a-subscriber-to-another-plan)
- [Cancelling a subscription](#cancelling-a-subscription)
- [Linking to a payment's receipt](#linking-to-a-payments-receipt)
- [Chasing overdue invoices](#chasing-overdue-invoices)
- [Last month's takings](#last-months-takings)
- [More than one Stripe account](#more-than-one-stripe-account)
- [Catching mistakes in your own input](#catching-mistakes-in-your-own-input)

Each example is a common task, from start to finish. Every class is in the `Programster\Stripe` namespace (see the
[quick start](../README.md#quick-start)), and the IDs, such as `'price_123'`, stand in for your own. Each one uses this
client:

```php
$client = new StripeClient('sk_test_...');
```

## Selling a product set up in Stripe's dashboard
When the product and its price are already in Stripe, made in the dashboard or through the API, a line item only needs
the price's ID, which the dashboard shows on the product's page. Changing the price in the dashboard then changes what
customers pay, without changing your code:

```php
$items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1));

$session = $client->createHostedCheckoutSessionForSinglePayment(
    flow: new HostedFlow(successUrl: "https://example.com/thank-you", cancelUrl: "https://example.com/shop"),
    items: $items,
);

$paymentPageUrl = $session->getUrl();
```

## Checking out a basket from your own database
When your products and prices live in your own database instead, describe each one in the call with
`createUsingInlineProduct()` and `createUsingInlinePrice()`, and Stripe creates them along with the session, so nothing
has to be set up in Stripe first. This also lets the customer change how many they want, and collects a shipping
address and phone number:

```php
// e.g. the rows of the customer's basket, from your own database.
$basket = [
    ['name' => 'T-shirt', 'pennies' => 2000, 'quantity' => 2],
    ['name' => 'Mug', 'pennies' => 850, 'quantity' => 1],
];

$lineItems = [];

foreach ($basket as $row)
{
    $price = PriceDataForSinglePayment::createUsingInlineProduct(
        currency: Currency::GBP,
        unitAmount: $row['pennies'],
        productData: new ProductData(name: $row['name']),
    );

    $lineItems[] = SinglePaymentLineItem::createUsingInlinePrice(
        priceData: $price,
        quantity: $row['quantity'],
        adjustableQuantityConfig: new AdjustableQuantityConfig(1, 10),
    );
}

$session = $client->createHostedCheckoutSessionForSinglePayment(
    flow: new HostedFlow(successUrl: "https://example.com/thank-you", cancelUrl: "https://example.com/basket"),
    items: new SinglePaymentLineItemsCollection(...$lineItems),
    shippingAddressCollection: new ShippingCountryCodeCollection(ShippingCountryCode::GB, ShippingCountryCode::IE),
    enablePhoneNumberCollection: true,
);
```

## Knowing which order was paid for
Give the session your own order's ID as its client reference ID, and put `{CHECKOUT_SESSION_ID}` in the success URL,
which Stripe replaces with the session's ID when it sends the customer back. The success page can then retrieve the
session, and see which order it was for, and whether it's been paid:

```php
$session = $client->createHostedCheckoutSessionForSinglePayment(
    flow: new HostedFlow(successUrl: "https://example.com/thank-you?session={CHECKOUT_SESSION_ID}"),
    items: new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1)),
    clientReferenceId: 'order-42',
    metadata: new Metadata(new MetadataItem('order_id', '42')),
);
```

Then, on the success page:

```php
// e.g. from $_GET['session'].
$sessionId = 'cs_test_123';

$returned = $client->retrieveCheckoutSession($sessionId);
$orderReference = $returned->getClientReferenceId();          // "order-42"
$orderId = $returned->getMetadata()->toStripeArrayForm()['order_id'] ?? null;

if ($returned->isPaid())
{
    // Show "thank you". Fulfil the order from a webhook, though (see below), as the customer may never come back.
}
```

Anyone can visit the success page with a session's ID, so only show them what's safe to show. The webhook is what
should trigger fulfilling the order.

## An embedded checkout, from a JSON endpoint
For a checkout embedded in your own page, your page's JavaScript asks your server for the session's client secret,
and passes it to Stripe's JavaScript. The endpoint returns it as JSON:

```php
$session = $client->createEmbeddedCheckoutSessionForSinglePayment(
    flow: new EmbeddedFlow(returnUrl: "https://example.com/return?session={CHECKOUT_SESSION_ID}"),
    items: new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1)),
);

header('Content-Type: application/json');
print json_encode(['clientSecret' => $session->getClientSecret()]);
```

With `RedirectOnCompletion::IF_REQUIRED`, the customer is only sent to the return URL when their payment method needs
it (such as a bank's own page), so your page can show its own message the rest of the time:

```php
$flow = new EmbeddedFlow(
    returnUrl: "https://example.com/return?session={CHECKOUT_SESSION_ID}",
    redirectOnCompletion: RedirectOnCompletion::IF_REQUIRED,
);
```

## Promotion codes and coupons
Let the customer type in a promotion code you've made in the dashboard, or apply a coupon for them. Stripe only allows
one or the other in a session:

```php
$items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1));
$flow = new HostedFlow(successUrl: "https://example.com/thank-you");

// The customer can enter a code.
$withCodeBox = $client->createHostedCheckoutSessionForSinglePayment($flow, $items, allowPromoCodes: true);

// Or a discount is already applied, e.g. from a link in your newsletter.
$discounted = $client->createHostedCheckoutSessionForSinglePayment(
    $flow,
    $items,
    discount: Discount::createUsingCouponId('SUMMER20'),
);
```

## Asking the customer a question at checkout
Custom fields add up to three questions to the payment page. Stripe keeps the answers on the completed session, in
`getStripeObject()->custom_fields`:

```php
$heardAbout = FieldType::createDropdown(
    null,
    new DropdownOption('From a friend', 'friend'),
    new DropdownOption('A search engine', 'search'),
    new DropdownOption('Social media', 'social'),
);

$question = new CustomField('heard_about', 'How did you hear about us?', $heardAbout, optional: true);

$session = $client->createHostedCheckoutSessionForSinglePayment(
    flow: new HostedFlow(successUrl: "https://example.com/thank-you"),
    items: new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1)),
    customFields: new CustomFieldCollection($question),
    customTextOptions: new CustomTextOptions(submit: "We ship within 2 working days."),
);
```

## A monthly plan with a free trial
A subscription's checkout works the same way, with a price that recurs. This signs up an existing customer, so it's
added to their account, with 14 days free before the first payment:

```php
$plan = SubscriptionCheckoutLineItem::createUsingExistingPrice('price_monthly', 1);

$session = $client->createHostedCheckoutSessionForSubscription(
    flow: new HostedFlow(successUrl: "https://example.com/welcome", cancelUrl: "https://example.com/pricing"),
    items: new SubscriptionCheckoutLineItemCollection($plan),
    subscriptionData: new SubscriptionData(
        trialConfig: TrialConfig::createUsingNumDays(14),
    ),
    customer: SubscriptionCheckoutCustomer::createUsingCustomerId('cus_123'),
);
```

For a price that isn't in Stripe yet, describe it inline, with how often it's charged:

```php
$monthlyPrice = PriceDataForSubscriptionCheckoutSession::createUsingExistingProduct(
    currency: Currency::GBP,
    unitAmount: 999,
    productId: 'prod_123',
    recurring: new RecurringConfig(RecurringInterval::MONTH, 1),
);

$inlinePlan = SubscriptionCheckoutLineItem::createUsingInlinePrice($monthlyPrice, 1);
```

## Fulfilling orders from a webhook
Stripe tells your webhook endpoint when a session is completed, even if the customer closes their browser before
getting back to your site, so that's the place to fulfil the order. `toCompleted()` gives a `CompletedCheckoutSession`,
whose customer details are always there:

```php
function fulfilOrder(CompletedCheckoutSession $completed): void
{
    $session = $completed->getSession();
    $orderReference = $session->getClientReferenceId();
    $email = $completed->getCustomerDetails()->getEmail();

    if ($session->isPaid())
    {
        // Mark the order as paid, and send it.
    }
}


function handleStripeWebhook(StripeClient $client, string $payload, string $signature, string $endpointSecret): void
{
    // Throws a \Stripe\Exception\SignatureVerificationException if the request didn't come from Stripe.
    $event = \Stripe\Webhook::constructEvent($payload, $signature, $endpointSecret);

    if ($event->type === 'checkout.session.completed')
    {
        $session = CheckoutSession::createFromStripeObject($event->data->object, $client);
        fulfilOrder($session->toCompleted());
    }
}

// In your webhook endpoint, with the signing secret from the dashboard's webhook settings:
// handleStripeWebhook($client, file_get_contents('php://input'), $_SERVER['HTTP_STRIPE_SIGNATURE'], 'whsec_...');
```

A payment by bank transfer, or another slow method, can complete the session before it's paid. Stripe then sends
`checkout.session.async_payment_succeeded` once it is, which can be handled the same way.

## Checking whether a customer has access
A customer has access while any of their subscriptions is active, or in its trial:

```php
function hasAccess(Customer $customer): bool
{
    $hasAccess = false;

    foreach ($customer->fetchSubscriptions() as $subscription)
    {
        if ($subscription->isActive() || $subscription->isTrialing())
        {
            $hasAccess = true;
        }
    }

    return $hasAccess;
}

$canSeePremiumContent = hasAccess($client->retrieveCustomer('cus_123'));
```

That's a request to Stripe each time, so for a check on every page, keep the answer in your own database, and update
it from the `customer.subscription.updated` and `customer.subscription.deleted` webhooks.

## Moving a subscriber to another plan
Change the subscription's item to the new plan's price. With `ProrationBehavior::CREATE`, the next invoice charges
for the rest of this period at the new price, less what's unused at the old one:

```php
$subscription = $client->retrieveSubscription('sub_123');
$currentItem = $subscription->fetchItems()->first();

$upgraded = $client->updateSubscriptionItem(
    $currentItem->getId(),
    price: SubscriptionItemPrice::createUsingExistingPrice('price_premium'),
    prorationBehavior: ProrationBehavior::CREATE,
);

$seats = $client->updateSubscriptionItem($currentItem->getId(), quantity: 5);
```

## Cancelling a subscription
`cancelSubscription()` cancels it straight away. With `prorate`, the customer is credited for the time they've paid
for but won't use, and saying why helps you see why customers leave, in the dashboard:

```php
$cancelled = $client->cancelSubscription(
    'sub_123',
    new CancellationDetails(feedback: CancellationFeedback::MISSING_FEATURES),
    prorate: true,
);

$endedAt = $cancelled->getEndedAt();
```

To let them keep using it until the end of what they've paid for, create the subscription with
`cancelAt: CancelAt::createAtPeriodEnd()`, or cancel it from the dashboard or the customer portal.

## Linking to a payment's receipt
A paid checkout session leads to its payment, and the payment's charge has Stripe's receipt page:

```php
$session = $client->retrieveCheckoutSession('cs_test_123');

if ($session->getPaymentIntentId() !== null)
{
    $receiptUrl = $session->fetchPaymentIntent()->fetchLatestCharge()->getReceiptUrl();
}
```

The same goes for a subscription's latest payment: `$subscription->fetchLatestInvoice()->fetchPayments()` gives the
invoice's payments, each with `fetchPaymentIntent()`.

## Chasing overdue invoices
Invoices that are sent rather than charged to a card stay open until they're paid. This finds the ones that are past
their due date, with the link to the page where the customer can pay:

```php
$open = $client->listInvoices(
    status: InvoiceStatus::OPEN,
    collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE,
    limit: ResultLimit::createAll(),
);

$now = new DateTimeImmutable();

foreach ($open as $invoice)
{
    $dueDate = $invoice->getDueDate();

    if ($dueDate !== null && $dueDate < $now)
    {
        $owed = $invoice->getAmountRemaining()->getAmount();
        $payLink = $invoice->getHostedInvoiceUrl();
        // Email the customer the link.
    }
}
```

## Last month's takings
The total taken last month, less refunds, in each currency, in its smallest unit (e.g. pennies):

```php
$lastMonth = new TimePeriod(
    start: new DateTimeImmutable('first day of last month 00:00:00'),
    end: new DateTimeImmutable('last day of last month 23:59:59'),
);

$takings = [];

foreach ($client->listCharges(created: $lastMonth, limit: ResultLimit::createAll()) as $charge)
{
    if ($charge->isSucceeded())
    {
        $currency = $charge->getAmount()->getCurrency();
        $kept = $charge->getAmountCaptured()->getAmount() - $charge->getAmountRefunded()->getAmount();
        $takings[$currency] = ($takings[$currency] ?? 0) + $kept;
    }
}
```

A charge is counted in the month it was made, so a refund this month for a charge last month comes off last month's
total. Stripe's fees aren't taken off: they're in its balance transactions, which the dashboard's reports cover.

## More than one Stripe account
Each client sends its requests with its own key, so an application with several accounts, e.g. one per country, makes
a client for each, and what one returns can only make further requests with that client's key:

```php
$ukClient = new StripeClient('sk_test_uk...');
$usClient = new StripeClient('sk_test_us...');

$ukCustomer = $ukClient->retrieveCustomer('cus_123');
$ukSubscriptions = $ukCustomer->fetchSubscriptions();    // Asks the UK account.
```

## Catching mistakes in your own input
Values Stripe would always reject throw an `ExceptionInvalidValue` as soon as the object is created, so when they come
from your own users, e.g. an admin form for a product's price, the error can be shown on the form, before anything is
sent to Stripe:

```php
// e.g. from the admin form.
$enteredPennies = -500;

try
{
    $price = PriceDataForSinglePayment::createUsingExistingProduct(Currency::GBP, $enteredPennies, 'prod_123');
}
catch (ExceptionInvalidValue $e)
{
    $formError = "The price can't be negative.";
}
```

Stripe can still turn down a request for reasons only it knows, such as a price that's been archived, which throws a
`\Stripe\Exception\ApiErrorException` (see [Exceptions](exceptions.md)).
