# Upgrading

- [From 0.2.x to 1.0.0](#from-02x-to-100)
    - [What the methods return](#what-the-methods-return)
    - [Checkout sessions](#checkout-sessions)
    - [Listing](#listing)
    - [One type per parameter](#one-type-per-parameter)
    - [Times](#times)
    - [Countries](#countries)
    - [Other changes](#other-changes)
- [From 0.2.2 to 0.2.3](#from-022-to-023)
- [From 0.2.1 to 0.2.2](#from-021-to-022)
- [From 0.2.0 to 0.2.1](#from-020-to-021)
- [From 0.1.0 to 0.2.0](#from-010-to-020)
    - [Changes you may need to make](#changes-you-may-need-to-make)
    - [Bugs fixed](#bugs-fixed)

What changed in each release, and what, if anything, you need to change in your code when upgrading.

Composer treats each `0.x` minor version like a major one, so a project requiring `^0.1.0` won't be upgraded to
`0.2.0` by itself: change it to `^0.2`.

## From 0.2.x to 1.0.0
1.0.0 returns this package's own typed models, rather than the Stripe SDK's objects, and gets every result from a list,
rather than one page. Upgrade to the latest 0.2.x first, then make the changes below.

### What the methods return
Every method returns a readonly model from `Programster\Stripe\Responses` (see [Responses](responses.md)), so read
fields with their getters, rather than as properties:

- `$session->url` becomes `$session->getUrl()`, `$customer->id` becomes `$customer->getId()`, and so on. Fields Stripe
  can leave empty return `null`.
- **Statuses are strings**, as Stripe sent them, from `getStatus()`. Compare them with `hasStatus()`, which takes the
  enum (e.g. `$subscription->hasStatus(SubscriptionStatus::ACTIVE)`), or a shortcut such as `isActive()`.
- **Times are `DateTimeImmutable`s, and amounts are `Money`**, rather than unix timestamps and plain ints.
- **For a field the models don't have yet**, `getStripeObject()` returns a copy of the SDK's object, as before.
- **The models can't be serialized**, as they hold the client, and so your secret key. Store IDs instead.

### Checkout sessions
Each checkout method is now two, one for a session hosted by Stripe, and one for a session embedded in your site, so
what each returns is certain to have what you need from it:

| 0.2.x | 1.0.0 |
|---|---|
| `createCheckoutSessionForSinglePayment(FlowConfig::createHosted(...), ...)` | `createHostedCheckoutSessionForSinglePayment(new HostedFlow(...), ...)` |
| `createCheckoutSessionForSinglePayment(FlowConfig::createEmbedded(...), ...)` | `createEmbeddedCheckoutSessionForSinglePayment(new EmbeddedFlow(...), ...)` |
| `createCheckoutSessionForSubscription(...)` | `createHostedCheckoutSessionForSubscription(...)` or `createEmbeddedCheckoutSessionForSubscription(...)`, in the same way |
| `flowConfig: $flowConfig` | `flow: $flow` |
| `$session->url` | `$session->getUrl()`, which is a string, never null |
| `$session->client_secret` | `$session->getClientSecret()`, which is a string, never null |

- **The hosted methods return a `NewHostedCheckoutSession`**, and the embedded ones a `NewEmbeddedCheckoutSession`.
  `getId()` gives the session's ID, and `getSession()` the `CheckoutSession` itself, for its other details.
- **`FlowConfig` has been replaced by `HostedFlow` and `EmbeddedFlow`**, which take the same arguments as
  `createHosted()` and `createEmbedded()` did, so a hosted flow can't be given to an embedded method, or the other way
  round.
- **`TaxIdCollectionConfig` has been replaced by the `TaxIdCollection` enum**:
  `TaxIdCollection::REQUIRED_WHERE_SUPPORTED` for `new TaxIdCollectionConfig(true, true)`, and
  `TaxIdCollection::OPTIONAL` for `new TaxIdCollectionConfig(true, false)`. For `new TaxIdCollectionConfig(false, ...)`,
  which did nothing, leave `$taxIdCollection` out.
- **`$session->toCompleted()` returns a `CompletedCheckoutSession`**, whose customer details can't be null, e.g. for a
  `checkout.session.completed` webhook. It throws an `ExceptionWrongStatus` if the session hasn't been completed.

### Listing
- **Each list method returns a readonly collection of everything that matches** (e.g. `CustomerCollection`), rather
  than a `\Stripe\Collection` of one page, so `->data` and `->has_more` go: loop over the collection itself.
- **`$limit` is a `ResultLimit`**: `ResultLimit::upTo(50)`, or `ResultLimit::all()`, rather than an int from 1 to 100.
  Without one, up to 1,000 are got, and an `ExceptionTooManyResults` is thrown if more match. See
  [Listing](listing.md#how-many-you-get).
- **`$cursorStartingAfter` and `$cursorEndingBefore` are now `$startingAfter` and `$endingBefore`**, and come after the
  filters and `$limit`. Use named arguments, e.g. `startingAfter: $page->last()->getId()`.
- **`listInvoices()` no longer takes `$expandPayments`.** Use `listInvoicesWithPayments()`, which returns
  `InvoiceWithPayments`, or `$invoice->fetchPayments()`.

### One type per parameter
A parameter that took one of several types (e.g. a price's ID or its details) now takes one type, with a static creator
for each option. Each creator checks what it's given, so a customer's ID given where an email address was expected, or
a product's ID where a price's was, throws an `ExceptionInvalidValue` on the line that made the mistake.

| 0.2.x | 1.0.0 |
|---|---|
| `existingStripeCustomerOrEmail: new ExistingCustomer('cus_123', $update)` | `customer: SinglePaymentCheckoutCustomer::existing('cus_123', $update)` (or `SubscriptionCheckoutCustomer`, for a subscription) |
| `existingStripeCustomerOrEmail: 'jo@example.com'` | `customer: SinglePaymentCheckoutCustomer::withEmail('jo@example.com')` |
| `customerCreation: CustomerCreation::ALWAYS` | `customer: SinglePaymentCheckoutCustomer::withEmail('jo@example.com', CustomerCreation::ALWAYS)`, or `SinglePaymentCheckoutCustomer::create(CustomerCreation::ALWAYS)` without an email address |
| `createSubscription(new ExistingCustomer('cus_123'), ...)` | `createSubscription(SubscriptionCustomer::customer('cus_123'), ...)` |
| `createSubscription('acct_123', ...)` | `createSubscription(SubscriptionCustomer::customerAccount('acct_123'), ...)` |
| `cancelAt: true` | `cancelAt: CancelAt::atPeriodEnd()` |
| `cancelAt: $timestamp` | `cancelAt: CancelAt::at($time)` |
| `cancelAt: CancelAtEnum::MAX_PERIOD_END` (or `MIN_PERIOD_END`) | `cancelAt: CancelAt::atLongestItemPeriodEnd()` (or `atShortestItemPeriodEnd()`) |
| `billingCycleAnchorTimestampOrConfig: $timestamp` | `billingCycleAnchor: BillingCycleAnchor::at($time)` |
| `billingCycleAnchorTimestampOrConfig: new BillingCycleAnchorConfig(1)` | `billingCycleAnchor: BillingCycleAnchor::onDayOfMonth(1)` |
| `new SinglePaymentLineItem(1, 'price_123')` | `SinglePaymentLineItem::forPrice('price_123', 1)` |
| `new SinglePaymentLineItem(1, $priceData)` | `SinglePaymentLineItem::forPriceData($priceData, 1)` |
| `new PriceDataForSinglePayment(Currency::GBP, 100, 'prod_123')` | `PriceDataForSinglePayment::forProduct(Currency::GBP, 100, 'prod_123')` |
| `new PriceDataForSinglePayment(Currency::GBP, 100, $productData)` | `PriceDataForSinglePayment::forProductData(Currency::GBP, 100, $productData)` |
| `updateSubscriptionItem(..., priceDataOrPriceId: 'price_123')` | `updateSubscriptionItem(..., price: SubscriptionItemPrice::forPrice('price_123'))` |

- **`SubscriptionCheckoutLineItem`, `SubscriptionItem` and `SubscriptionInvoiceItem`** are created with `forPrice()`
  and `forPriceData()` in the same way, and **`PriceDataForSubscriptionCheckoutSession`** with `forProduct()` and
  `forProductData()`. Each takes the quantity after the price, which `SinglePaymentLineItem`,
  `SubscriptionCheckoutLineItem` and `SubscriptionItem` used to take first.
- **`ExistingCustomer`, `BillingCycleAnchorConfig` and `CancelAtEnum` have been removed**, and so has the
  `$customerCreation` parameter.
- **`cancelAt: false` has gone**, as it did the same as leaving it out.
- **A price's or product's ID** is only rejected when it's empty, or is the other one's (`prod_...` for a price, or
  `price_...` for a product), as either can have an ID you chose. A customer's ID must start `cus_`, and an account's
  `acct_`.

### Times
Every time a request takes is a `DateTimeImmutable`, like the times the responses give back, rather than a unix
timestamp, so seconds and milliseconds can't be mixed up. For a timestamp you already have, use
`new DateTimeImmutable('@' . $timestamp)`.

- The checkout methods' `$expiresAt`, `createSubscription()`'s `$backdateStartDate`, `updateSubscriptionItem()`'s
  `$prorationDate`, and `SubscriptionData`'s `$billingCycleAnchor`.
- `TimePeriod`'s start and end. An end before the start throws an `ExceptionInvalidValue`.
- `TrialConfig::createForEndTimestamp()` is now `TrialConfig::createForEndTime()`, and `PeriodStart::createTimestamp()`
  and `PeriodEnd::createTimestamp()` are now `createAt()`.

### Countries
- **An `Address`'s country is a `CountryCode`**, e.g. `country: CountryCode::GB` rather than `country: 'GB'`. Stripe
  accepts any two letters as an address's country without checking them, so a mistyped code used to be saved as it
  was. `CountryCode` has every ISO 3166-1 alpha-2 code, and `XK` for Kosovo.
- **The old `CountryCode` is now `ShippingCountryCode`**, and `CountryCodeCollection` is now
  `ShippingCountryCodeCollection`, for the checkout methods' `$shippingAddressCollection`. It only has the countries
  Checkout can collect shipping addresses for, which are fewer.

### Other changes
- **New methods:** `retrieveCustomer()`, `retrieveSubscription()`, `retrieveInvoice()`, `retrievePaymentIntent()`,
  `retrieveCharge()`, `retrieveRefund()`, `retrievePaymentMethod()`, `listInvoicePayments()` and
  `listInvoicesWithPayments()`, and `fetch...()` methods on the models for their related objects, e.g.
  `$subscription->fetchLatestInvoice()`. See [Responses](responses.md#related-objects) for the full list.
- **`listCharges()`'s `$paymentIntent` is now `$paymentIntentId`**, like the other methods' IDs.
- **`$adaptivePricing` works.** The checkout methods sent it as a bare bool, which Stripe rejected, so any session
  created with it failed. It's now sent in the form Stripe takes. Nothing needs to change in your code.
- **The request collections are readonly**, built on `programster/collections` 2.0's readonly collections, so they
  can't be changed after they're created: `append()`, `$collection[] = ...` and the other `ArrayObject` methods are
  gone. Create the collection with everything in it instead.
- **`SubscriptionItemCollection` has been removed** from `Programster\Stripe\Collections`, as it was a copy of
  `SubscriptionLineItemCollection`, which `createSubscription()` takes. The name is now the response collection, in
  `Programster\Stripe\Responses`.
- **The client can't be serialized**, as it holds the secret key.
- **The global API key isn't set any more.** Creating a `StripeClient` used to also call `\Stripe\Stripe::setApiKey()`,
  for the SDK's static methods. If your code uses those (e.g. `\Stripe\Customer::retrieve()`), set the key yourself,
  or use the client's methods.
- **`createCustomer()` takes one `CustomerShippingDetails`**, rather than a collection that could only hold one, so
  `CustomerShippingDetailsCollection` has been removed.
- **`FieldType::createNumeric()` takes the minimum length before the maximum**, like `createText()`. Swap the last two
  arguments.
- **`SubscriptionItem` no longer takes `$adjustableQuantityConfig`**, which Stripe never allowed on a subscription's
  items, so it always threw. Use named arguments for the parameters after it.
- **`SubscriptionItemDiscount` can only be created with its `createFrom...()` methods**, as each gives exactly one ID.
- **These have been removed**, as they never worked, or weren't used: `OnBehalfOfConfig`, `StripeConnectPaymentConfig`
  and `StripeConnectSubscriptionConfig` (and the `$stripeConnectConfig` parameter they were for),
  `SubscriptionTrialConfig` (use `TrialConfig`), and `PaymentMethodType::getAll()` (use `PaymentMethodType::cases()`).
  For Stripe Connect, pass a `PaymentTransferData` in `PaymentIntentData`, or a `SubscriptionTransferData` in
  `SubscriptionData`.
- **The models and the client are `final`**, and readonly.
- **`TransferData` is split in two**, as its amount meant pennies for a payment, but a percentage for a subscription:
  use `PaymentTransferData` (with an amount in pennies) in `PaymentIntentData`, and `SubscriptionTransferData` (with a
  percentage, from 0 to 100) in `SubscriptionData` and `createSubscription()`. Both check the destination is a
  connected account's ID (`acct_...`), and each amount is optional, sending everything when it's left out.

## From 0.2.2 to 0.2.3
Nothing needs to change in your code.

- **Requests can be retried safely.** `createCustomer()`, `createSubscription()`, `updateSubscriptionItem()` and both
  checkout session methods take an optional `$idempotencyKey`, so that a request sent again, such as after a timeout,
  doesn't create something twice. See [Customers](customers.md#retrying-safely).
- **PHP 9 isn't claimed until it's been tested.** The package requires `^8.2` (any PHP 8 release from 8.2), rather
  than `>=8.2.0`, which would have allowed it to be installed on PHP 9 before anyone knew if it worked there.
- **The package's type is `library`**, Composer's standard type, rather than `sdk`.
- **The arrays the models and list methods return say what they hold**, so PHPStan and your IDE know, e.g. the list
  methods return a `Collection<\Stripe\Customer>`. PHPStan now runs at level 6.
- **Full documentation**, in `docs/`, whose examples are run by the tests, so they can't drift from what the code does.
- **A GitHub workflow** runs the offline tests and PHPStan on every supported version of PHP.
- **`BillingMode` checks its proration discount config is there** before using it, rather than assuming so.

## From 0.2.1 to 0.2.2
Nothing needs to change in your code. PHPStan was added to the tests, at level 5.

## From 0.2.0 to 0.2.1
Nothing needs to change in your code. Live tests against Stripe's test mode were added, and found these bugs, which
are fixed:

- **A trial's end behaviour was sent in a form Stripe rejects**, so `TrialConfig` or `SubscriptionTrialConfig` with an
  `EndTrialBehaviorIfMissingPaymentMethod` always failed.
- **A text or numeric custom field with no default value was rejected by Stripe**, as an empty default was sent. An
  empty default is now left out.
- **`SubscriptionData` with a proration behaviour, but no billing cycle anchor, now throws an
  `ExceptionInvalidValue`**, rather than being rejected by Stripe.

## From 0.1.0 to 0.2.0
0.2.0 fixed a large number of bugs, many of which meant a feature always failed, or silently sent Stripe the wrong
values. None of the fixes change what working code does, apart from those below.

### Changes you may need to make
- **Filtering by date gives slightly different results.** `TimePeriod`'s inclusive and exclusive comparisons were the
  wrong way round, so its default "inclusive" range left out what was created on exactly its start or end second. It's
  now included.
- **Some values now throw an exception where they're created**, rather than being sent to Stripe and rejected. Code
  that catches `\Stripe\Exception\ApiErrorException` for these will now get the package's `ExceptionInvalidValue`
  instead. Examples are a negative price, a list limit outside 1 to 100, an adjustable quantity whose minimum is more
  than its maximum, and a customer's ID passed as a string to `createSubscription()`.
- **Dropdown option values must be alphanumeric**, as Stripe's documentation says. The check was meant to be there
  before, but never ran.
- **Enum values now match Stripe's**, e.g. `SubscriptionStatus::CANCELLED` is `"canceled"`. Code that compares an
  enum's value with the old, misspelt string will need changing, though the old values never matched what Stripe
  returns.
- **`ExceptionDuplicateKey` extends `ExceptionInvalidValue`**, and is now what `Metadata` throws for a duplicated key.
- **`createCustomer()` sends the shipping details as `shipping`**, which is what Stripe expects, and only accepts one
  set of them.
- **Return types were added to methods.** This only matters if you extended one of the package's classes, and
  overrode a method.

### Bugs fixed
- **Checkout sessions now use the client's own key.** They were created and retrieved with the key of whichever
  `StripeClient` was created last, so with more than one Stripe account, a session could be made in the wrong one.
- **The secret key is kept out of `var_dump()`, `print_r()` and stack traces.**
- **`TransferData` sent no amount**, and now sends it as a fixed amount for a payment, and as a percentage for a
  subscription.
- **Many objects couldn't be created, or crashed when used**, including `CustomerTaxConfig`, `CustomerTaxIdData`,
  `InvoiceRenderingOptions`, `AccountReference::createSelf()`, `BillingMode`, `BillingCycleAnchorConfig`,
  `DiscountCollection`, `CustomerTaxIdDataCollection`, `StringCollection`, `DropdownOption` and `MetadataItem`.
- **Settings that were dropped or misnamed are now sent**, such as the hosted cancel URL, the custom field `optional`
  flag, the product tax code, after-expiration promotion codes, the subscription collection method filter, and custom
  terms of service text.
- **The minimum PHP version was lowered to 8.2**, as it works there.
