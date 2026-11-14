# Upgrading

- [From 0.2.2 to 0.2.3](#from-022-to-023)
- [From 0.2.1 to 0.2.2](#from-021-to-022)
- [From 0.2.0 to 0.2.1](#from-020-to-021)
- [From 0.1.0 to 0.2.0](#from-010-to-020)
    - [Changes you may need to make](#changes-you-may-need-to-make)
    - [Bugs fixed](#bugs-fixed)

What changed in each release, and what, if anything, you need to change in your code when upgrading.

Composer treats each `0.x` minor version like a major one, so a project requiring `^0.1.0` won't be upgraded to
`0.2.0` by itself: change it to `^0.2`.

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
