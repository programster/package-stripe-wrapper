<?php

namespace Programster\Stripe;

use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\CustomerTaxIdDataCollection;
use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\LocaleCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Collections\SubscriptionInvoiceItemCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\BillingAddressCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\InvoiceStatus;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\PaymentBehavior;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\ReconciliationMode;
use Programster\Stripe\Enums\SessionMode;
use Programster\Stripe\Enums\SubmitType;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Enums\TaxExempt;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionIncompleteList;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionTooManyResults;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingCycleAnchor;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\BillingThresholds;
use Programster\Stripe\Models\CancelAt;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerShippingDetails;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\InvoiceSettings;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\PaymentSettings;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\SinglePaymentCheckoutCustomer;
use Programster\Stripe\Models\SubscriptionCheckoutCustomer;
use Programster\Stripe\Models\SubscriptionCustomer;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\SubscriptionItemPrice;
use Programster\Stripe\Models\SubscriptionTransferData;
use Programster\Stripe\Models\TaxIdCollectionConfig;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\TrialConfig;
use Programster\Stripe\Responses\Charge;
use Programster\Stripe\Responses\ChargeCollection;
use Programster\Stripe\Responses\CheckoutSession;
use Programster\Stripe\Responses\Customer;
use Programster\Stripe\Responses\CustomerCollection;
use Programster\Stripe\Responses\Invoice;
use Programster\Stripe\Responses\InvoiceCollection;
use Programster\Stripe\Responses\InvoicePayment;
use Programster\Stripe\Responses\InvoicePaymentCollection;
use Programster\Stripe\Responses\InvoiceWithPayments;
use Programster\Stripe\Responses\InvoiceWithPaymentsCollection;
use Programster\Stripe\Responses\PaymentIntent;
use Programster\Stripe\Responses\PaymentIntentCollection;
use Programster\Stripe\Responses\PaymentMethod;
use Programster\Stripe\Responses\PaymentMethodCollection;
use Programster\Stripe\Responses\Refund;
use Programster\Stripe\Responses\RefundCollection;
use Programster\Stripe\Responses\Subscription;
use Programster\Stripe\Responses\SubscriptionCollection;
use Programster\Stripe\Responses\SubscriptionItem;
use Programster\Stripe\Responses\SubscriptionItemCollection;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;

final readonly class StripeClient
{
    private \Stripe\StripeClient $m_underlyingStripeClient;


    public function __construct(#[\SensitiveParameter] string $secretKey)
    {
        $this->m_underlyingStripeClient = new \Stripe\StripeClient($secretKey);
    }


    /**
     * Keep the secret key out of var_dump() and print_r() output, e.g. in logs or error pages.
     * @return array<string, string>
     */
    public function __debugInfo() : array
    {
        return ['secretKey' => '[redacted]'];
    }


    /**
     * The client can't be serialized, as it holds the secret key.
     * @return array<string, mixed>
     * @throws \LogicException - always.
     */
    public function __serialize() : array
    {
        throw new \LogicException("The StripeClient can't be serialized, as it holds the secret key.");
    }


    /**
     * List subscriptions, newest first, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/subscriptions/list
     *
     * @param string|null $customerId - optionally, only list this customer's subscriptions.
     *
     * @param string|null $priceId - optionally, only list subscriptions with this recurring price.
     *
     * @param TimePeriod|null $created - optionally, only list subscriptions created in this time period.
     *
     * @param TimePeriod|null $currentPeriodEnd - optionally, only list subscriptions whose current period ends in this
     * time period.
     *
     * @param TimePeriod|null $currentPeriodStart - optionally, only list subscriptions whose current period started
     * in this time period.
     *
     * @param SubscriptionStatus|null $subscriptionStatus - optionally, only list subscriptions with this status, or
     * SubscriptionStatus::ALL for every status. Without one, Stripe lists every subscription that isn't cancelled.
     *
     * @param bool|null $automaticTaxEnabled - optionally, only list subscriptions with automatic tax on, or off.
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - optionally, only list subscriptions that are
     * charged automatically, or sent an invoice.
     *
     * @param string|null $testClock - optionally, only list subscriptions on this test clock.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return SubscriptionCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listSubscriptions(
        ?string $customerId = null,
        ?string $priceId = null,
        ?TimePeriod $created = null,
        ?TimePeriod $currentPeriodEnd = null,
        ?TimePeriod $currentPeriodStart = null,
        ?SubscriptionStatus $subscriptionStatus = null,
        ?bool $automaticTaxEnabled = null,
        ?SubscriptionCollectionMethod $collectionMethod = null,
        ?string $testClock = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : SubscriptionCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($priceId !== null) { $params['price'] = $priceId; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($currentPeriodEnd !== null) { $params['current_period_end'] = $currentPeriodEnd->toArray(); }
        if ($currentPeriodStart !== null) { $params['current_period_start'] = $currentPeriodStart->toArray(); }
        if ($subscriptionStatus !== null) { $params['status'] = $subscriptionStatus->value; }
        if ($automaticTaxEnabled !== null) { $params['automatic_tax'] = ['enabled' => $automaticTaxEnabled]; }
        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->subscriptions->all($pageParams);
        };

        $subscriptions = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'subscriptions') as $object)
        {
            $subscriptions[] = Subscription::createFromStripeObject($object, $this);
        }

        return new SubscriptionCollection(...$subscriptions);
    }


    /**
     * Create a customer - https://docs.stripe.com/api/customers/create?lang=php
     *
     * @param Address|null $address - the address of the customer (required if calculating taxes).
     *
     * @param string|null $description - An arbitrary string that you can attach to a customer object. It is displayed
     * alongside the customer in the dashboard.
     *
     * @param string|null $email - Customer’s email address. It’s displayed alongside the customer in your dashboard
     * and can be useful for searching and tracking. This may be up to 512 characters.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format.
     *
     * @param string|null $name - The customer’s full name or business name. The maximum length is 256 characters.
     *
     * @param string|null $paymentMethodId - The ID of the PaymentMethod to attach to the customer.
     *
     * @param string|null $phone - The customer’s phone number.
     *
     * @param CustomerShippingDetails|null $shippingDetails - The customer’s shipping information, which appears on
     * invoices emailed to this customer.
     *
     * @param CustomerTaxConfig|null $taxConfig - tax details about the customer, such as their IP address for tax
     * location inference.
     *
     * @param int|null $balance - An integer amount in cents/pennies that represents the customer’s current balance,
     * which affects the customer’s future invoices. A negative amount represents a credit that decreases the amount
     * due on an invoice, and a positive amount increases it.
     *
     * @param string|null $businessName - The customer’s business name. The maximum length is 150 characters.
     *
     * @param ReconciliationMode|null $cashBalanceReconciliationMode - how funds transferred by the customer into their
     * cash balance should be reconciled against their open invoices.
     *
     * @param string|null $individualName - The customer’s full name, if they are an individual. The maximum length is
     * 150 characters.
     *
     * @param string|null $invoicePrefix - The prefix for the customer used to generate unique invoice numbers. Must be
     * 3–12 uppercase letters or numbers.
     *
     * @param CustomerInvoiceSettings|null $invoiceSettings - Default invoice settings for this customer.
     *
     * @param int|null $nextInvoiceSequence - The sequence to be used on the customer’s next invoice. Defaults to 1.
     *
     * @param LocaleCollection|null $preferredLocales - The customer’s preferred languages, ordered by preference.
     *
     * @param string|null $source - the ID of a payment source (e.g. a token) to attach to the customer. Stripe
     * recommends using $paymentMethodId instead.
     *
     * @param TaxExempt|null $taxExempt - The customer’s tax exemption. One of none, exempt, or reverse.
     *
     * @param CustomerTaxIdDataCollection|null $taxIdData - The customer’s tax IDs.
     *
     * @param string|null $testClockId - ID of the test clock to attach to the customer, for testing.
     *
     * @param string|null $idempotencyKey - optionally, a unique key for this request (e.g. a UUID, or your own ID
     * for the order), so that if it's sent again, such as when retrying after a timeout, Stripe returns what the first
     * request made, rather than making it twice. Use a new key for each different request. Up to 255 characters.
     * https://docs.stripe.com/api/idempotent_requests
     *
     * @return Customer - the created customer.
     *
     * @throws ApiErrorException
     */
    public function createCustomer(
        ?Address $address = null,
        ?string $description = null,
        ?string $email = null,
        ?Metadata $metadata = null,
        ?string $name = null,
        ?string $paymentMethodId = null,
        ?string $phone = null,
        ?CustomerShippingDetails $shippingDetails = null,
        ?CustomerTaxConfig $taxConfig = null,
        ?int $balance = null,
        ?string $businessName = null,
        ?ReconciliationMode $cashBalanceReconciliationMode = null,
        ?string $individualName = null,
        ?string $invoicePrefix = null,
        ?CustomerInvoiceSettings $invoiceSettings = null,
        ?int $nextInvoiceSequence = null,
        ?LocaleCollection $preferredLocales = null,
        ?string $source = null,
        ?TaxExempt $taxExempt = null,
        ?CustomerTaxIdDataCollection $taxIdData = null,
        ?string $testClockId = null,
        ?string $idempotencyKey = null,
    ) : Customer
    {
        $params = [];

        if ($address !== null) { $params['address'] = $address->toArray(); }
        if ($description !== null) { $params['description'] = $description; }
        if ($email !== null) { $params['email'] = $email; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($name !== null) { $params['name'] = $name; }
        if ($paymentMethodId !== null) { $params['payment_method'] = $paymentMethodId; }
        if ($phone !== null) { $params['phone'] = $phone; }

        if ($shippingDetails !== null) { $params['shipping'] = $shippingDetails->toArray(); }

        if ($taxConfig !== null) { $params['tax'] = $taxConfig->toArray(); }
        if ($balance !== null) { $params['balance'] = $balance; }
        if ($businessName !== null) { $params['business_name'] = $businessName; }

        if ($cashBalanceReconciliationMode !== null)
        {
            $params['cash_balance'] = [
                'settings' => [
                    'reconciliation_mode' => $cashBalanceReconciliationMode->value
                ]
            ];
        }

        if ($individualName !== null) { $params['individual_name'] = $individualName; }
        if ($invoicePrefix !== null) { $params['invoice_prefix'] = $invoicePrefix; }
        if ($invoiceSettings !== null) { $params['invoice_settings'] = $invoiceSettings->toArray(); }
        if ($nextInvoiceSequence !== null) { $params['next_invoice_sequence'] = $nextInvoiceSequence; }
        if ($preferredLocales !== null) { $params['preferred_locales'] = $preferredLocales->toStripeArrayForm(); }
        if ($source !== null) { $params['source'] = $source; }
        if ($taxExempt !== null) { $params['tax_exempt'] = $taxExempt->value; }
        if ($taxIdData !== null) { $params['tax_id_data'] = $taxIdData->toStripeArrayForm(); }
        if ($testClockId !== null) { $params['test_clock'] = $testClockId; }

        $options = $this->getRequestOptions($idempotencyKey);
        $customer = $this->m_underlyingStripeClient->customers->create($params, $options);
        return Customer::createFromStripeObject($customer, $this);
    }


    /**
     * List a subscription's items, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/subscription_items/list
     *
     * @param string $subscriptionId - the ID of the subscription to list the items of.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return SubscriptionItemCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listSubscriptionItems(
        string $subscriptionId,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : SubscriptionItemCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);
        $params['subscription'] = $subscriptionId;

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->subscriptionItems->all($pageParams);
        };

        $items = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'subscription items') as $object)
        {
            $items[] = SubscriptionItem::createFromStripeObject($object, $this);
        }

        return new SubscriptionItemCollection(...$items);
    }


    /**
     * Updates the plan or quantity of an item on a current subscription.
     * https://docs.stripe.com/api/subscription_items/update
     *
     * @param string $subscriptionItemId - the ID of the subscription item we wish to update.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format.
     *
     * @param PaymentBehavior|null $paymentBehavior - how to handle a payment that is required by the update but
     * can't be made.
     *
     * @param SubscriptionItemPrice|null $price - optionally, the price to change the item to: an existing one, with
     * SubscriptionItemPrice::forPrice(), or one created inline, with SubscriptionItemPrice::forPriceData(). Leave as
     * null to not change it.
     *
     * @param ProrationBehavior|null $prorationBehavior - Determines how to handle prorations when the billing cycle
     * changes (e.g., when switching plans, resetting billing_cycle_anchor=now, or starting a trial), or if an item’s
     * quantity changes. The default value is create_prorations.
     *
     * @param int|null $quantity - The quantity you’d like to apply to the subscription item.
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period. A BillingThresholds with a null usage removes previously-defined
     * thresholds.
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the subscription item.
     *
     * @param bool|null $offSession - Indicates if a customer is on or off-session while an invoice payment is
     * attempted. Defaults to false (on-session).
     *
     * @param \DateTimeImmutable|null $prorationDate - If set, the proration will be calculated as though the
     * subscription was updated at the given time. This can be used to apply the same proration that was previewed
     * with the upcoming invoice endpoint.
     *
     * @param StringCollection|null $taxRateIds - A list of Tax Rate IDs. These Tax Rates will override the
     * default_tax_rates on the Subscription.
     *
     * @param string|null $idempotencyKey - optionally, a unique key for this request (e.g. a UUID, or your own ID
     * for the order), so that if it's sent again, such as when retrying after a timeout, Stripe returns what the first
     * request made, rather than making it twice. Use a new key for each different request. Up to 255 characters.
     * https://docs.stripe.com/api/idempotent_requests
     *
     * @return SubscriptionItem - the updated subscription item.
     *
     * @throws ApiErrorException
     */
    public function updateSubscriptionItem(
        string                                   $subscriptionItemId,
        ?Metadata                                $metadata = null,
        ?PaymentBehavior                         $paymentBehavior = null,
        ?SubscriptionItemPrice                   $price = null,
        ?ProrationBehavior                       $prorationBehavior = null,
        ?int                                     $quantity = null,
        ?BillingThresholds                       $billingThresholds = null,
        ?DiscountCollection                      $discounts = null,
        ?bool                                    $offSession = null,
        ?\DateTimeImmutable                      $prorationDate = null,
        ?StringCollection                        $taxRateIds = null,
        ?string                                  $idempotencyKey = null,
    ) : SubscriptionItem
    {
        $params = [];

        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($paymentBehavior !== null) { $params['payment_behavior'] = $paymentBehavior->value; }

        if ($price !== null) { $params = array_merge($params, $price->getStripeParams()); }

        if ($prorationBehavior !== null) { $params['proration_behavior'] = $prorationBehavior->value; }
        if ($quantity !== null) { $params['quantity'] = $quantity; }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }
        if ($discounts !== null) { $params['discounts'] = $discounts->toStripeArrayForm(); }
        if ($offSession !== null) { $params['off_session'] = $offSession; }
        if ($prorationDate !== null) { $params['proration_date'] = $prorationDate->getTimestamp(); }
        if ($taxRateIds !== null) { $params['tax_rates'] = $taxRateIds->toArray(); }

        $options = $this->getRequestOptions($idempotencyKey);
        $item = $this->m_underlyingStripeClient->subscriptionItems->update($subscriptionItemId, $params, $options);
        return SubscriptionItem::createFromStripeObject($item, $this);
    }


    /**
     * Cancel a subscription.
     * https://docs.stripe.com/api/subscriptions/cancel
     *
     * @param string $subscriptionId - the ID of the subscription to cancel.
     *
     * @param CancellationDetails|null $cancellationDetails - optionally provide details of why the user canceled.
     * https://docs.stripe.com/api/subscriptions/cancel#cancel_subscription-cancellation_details
     *
     * @param bool $invoiceNow - optionally set to true to generate a final invoice that invoices for any un-invoiced
     * metered usage and new/pending proration invoice items. Defaults to false.
     * https://docs.stripe.com/api/subscriptions/cancel#cancel_subscription-invoice_now
     *
     * @param bool $prorate - optionally set to true to generate a proration invoice item that credits remaining unused
     * time until the subscription period end. Defaults to false.
     * https://docs.stripe.com/api/subscriptions/cancel#cancel_subscription-prorate
     *
     * @return Subscription - the updated (canceled) subscription.
     *
     * @throws ApiErrorException
     */
    public function cancelSubscription(
        string $subscriptionId,
        ?CancellationDetails $cancellationDetails = null,
        bool $invoiceNow = false,
        bool $prorate = false
    ) : Subscription
    {
        $params = [
            'invoice_now' => $invoiceNow,
            'prorate' => $prorate,
        ];

        if ($cancellationDetails !== null) { $params['cancellation_details'] = $cancellationDetails->toArray(); }

        $subscription = $this->m_underlyingStripeClient->subscriptions->cancel($subscriptionId, $params);
        return Subscription::createFromStripeObject($subscription, $this);
    }


    /**
     * List payment intents, newest first, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/payment_intents/list
     *
     * @param string|null $customerId - optionally, only list this customer's payment intents.
     *
     * @param string|null $customerAccountId - optionally, only list this customer account's payment intents.
     *
     * @param TimePeriod|null $created - optionally, only list payment intents created in this time period.
     *
     * @param string|null $testClock - optionally, only list payment intents on this test clock.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return PaymentIntentCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listPaymentIntents(
        ?string $customerId = null,
        ?string $customerAccountId = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : PaymentIntentCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccountId !== null) { $params['customer_account'] = $customerAccountId; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->paymentIntents->all($pageParams);
        };

        $paymentIntents = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'payment intents') as $object)
        {
            $paymentIntents[] = PaymentIntent::createFromStripeObject($object, $this);
        }

        return new PaymentIntentCollection(...$paymentIntents);
    }


    /**
     * List invoices, newest first, asking Stripe for as many pages as it takes. They don't include their payments:
     * use listInvoicesWithPayments() for that.
     * https://docs.stripe.com/api/invoices/list
     *
     * @param string|null $customerId - optionally, only list this customer's invoices.
     *
     * @param string|null $customerAccount - optionally, only list this customer account's invoices.
     *
     * @param InvoiceStatus|null $status - optionally, only list invoices with this status.
     *
     * @param string|null $subscriptionId - optionally, only list this subscription's invoices.
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - optionally, only list invoices that are charged
     * automatically, or sent to the customer.
     *
     * @param TimePeriod|null $created - optionally, only list invoices created in this time period.
     *
     * @param string|null $testClock - optionally, only list invoices on this test clock.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return InvoiceCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listInvoices(
        ?string $customerId = null,
        ?string $customerAccount = null,
        ?InvoiceStatus $status = null,
        ?string $subscriptionId = null,
        ?SubscriptionCollectionMethod $collectionMethod = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : InvoiceCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccount !== null) { $params['customer_account'] = $customerAccount; }
        if ($status !== null) { $params['status'] = $status->value; }
        if ($subscriptionId !== null) { $params['subscription'] = $subscriptionId; }
        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->invoices->all($pageParams);
        };

        $invoices = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'invoices') as $object)
        {
            $invoices[] = Invoice::createFromStripeObject($object, $this);
        }

        return new InvoiceCollection(...$invoices);
    }


    /**
     * List invoices with their payments, newest first, asking Stripe for as many pages as it takes. This is the way to
     * find the payment intents that paid for a subscription: its invoices, then their payments.
     * https://docs.stripe.com/api/invoices/list
     *
     * @param string|null $customerId - optionally, only list this customer's invoices.
     *
     * @param string|null $customerAccount - optionally, only list this customer account's invoices.
     *
     * @param InvoiceStatus|null $status - optionally, only list invoices with this status.
     *
     * @param string|null $subscriptionId - optionally, only list this subscription's invoices.
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - optionally, only list invoices that are charged
     * automatically, or sent to the customer.
     *
     * @param TimePeriod|null $created - optionally, only list invoices created in this time period.
     *
     * @param string|null $testClock - optionally, only list invoices on this test clock.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return InvoiceWithPaymentsCollection
     *
     * @throws ExceptionIncompleteList - if Stripe left some of an invoice's payments out. Use
     * Invoice::fetchPayments() for that invoice instead.
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listInvoicesWithPayments(
        ?string $customerId = null,
        ?string $customerAccount = null,
        ?InvoiceStatus $status = null,
        ?string $subscriptionId = null,
        ?SubscriptionCollectionMethod $collectionMethod = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : InvoiceWithPaymentsCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccount !== null) { $params['customer_account'] = $customerAccount; }
        if ($status !== null) { $params['status'] = $status->value; }
        if ($subscriptionId !== null) { $params['subscription'] = $subscriptionId; }
        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }
        $params['expand'] = ['data.payments'];

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->invoices->all($pageParams);
        };

        $invoices = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'invoices') as $object)
        {
            $invoices[] = InvoiceWithPayments::createFromStripeObject($object, $this);
        }

        return new InvoiceWithPaymentsCollection(...$invoices);
    }


    /**
     * List the payments made against an invoice, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/invoice-payment/list
     *
     * @param string $invoiceId - the ID of the invoice.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return InvoicePaymentCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listInvoicePayments(
        string $invoiceId,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : InvoicePaymentCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);
        $params['invoice'] = $invoiceId;

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->invoicePayments->all($pageParams);
        };

        $payments = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'invoice payments') as $object)
        {
            $payments[] = InvoicePayment::createFromStripeObject($object, $this);
        }

        return new InvoicePaymentCollection(...$payments);
    }


    /**
     * Retrieve an invoice by ID.
     * https://docs.stripe.com/api/invoices/retrieve
     * @param string $invoiceId - the ID of the invoice, e.g. "in_123".
     * @return Invoice
     * @throws ApiErrorException - if there's no such invoice, or Stripe can't be reached.
     */
    public function retrieveInvoice(string $invoiceId) : Invoice
    {
        $invoice = $this->m_underlyingStripeClient->invoices->retrieve($invoiceId);
        return Invoice::createFromStripeObject($invoice, $this);
    }


    /**
     * List customers, newest first, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/customers/list
     *
     * @param string|null $email - optionally, only list customers with this exact email address (case-sensitive).
     *
     * @param TimePeriod|null $created - optionally, only list customers created in this time period.
     *
     * @param string|null $testClock - optionally, only list customers on this test clock.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return CustomerCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listCustomers(
        ?string $email = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : CustomerCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($email !== null) { $params['email'] = $email; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->customers->all($pageParams);
        };

        $customers = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'customers') as $object)
        {
            $customers[] = Customer::createFromStripeObject($object, $this);
        }

        return new CustomerCollection(...$customers);
    }


    /**
     * List refunds, newest first, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/refunds/list
     *
     * @param string|null $chargeId - optionally, only list this charge's refunds.
     *
     * @param string|null $paymentIntentId - optionally, only list this payment intent's refunds.
     *
     * @param TimePeriod|null $created - optionally, only list refunds created in this time period.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return RefundCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listRefunds(
        ?string $chargeId = null,
        ?string $paymentIntentId = null,
        ?TimePeriod $created = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : RefundCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($chargeId !== null) { $params['charge'] = $chargeId; }
        if ($paymentIntentId !== null) { $params['payment_intent'] = $paymentIntentId; }
        if ($created !== null) { $params['created'] = $created->toArray(); }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->refunds->all($pageParams);
        };

        $refunds = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'refunds') as $object)
        {
            $refunds[] = Refund::createFromStripeObject($object, $this);
        }

        return new RefundCollection(...$refunds);
    }


    /**
     * List charges, newest first, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/charges/list
     *
     * @param string|null $customerId - optionally, only list this customer's charges.
     *
     * @param TimePeriod|null $created - optionally, only list charges created in this time period.
     *
     * @param string|null $paymentIntent - optionally, only list this payment intent's charges.
     *
     * @param string|null $transferGroup - optionally, only list charges in this transfer group.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return ChargeCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listCharges(
        ?string $customerId = null,
        ?TimePeriod $created = null,
        ?string $paymentIntent = null,
        ?string $transferGroup = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : ChargeCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($paymentIntent !== null) { $params['payment_intent'] = $paymentIntent; }
        if ($transferGroup !== null) { $params['transfer_group'] = $transferGroup; }

        $requestPage = function (array $pageParams): Collection {
            return $this->m_underlyingStripeClient->charges->all($pageParams);
        };

        $charges = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'charges') as $object)
        {
            $charges[] = Charge::createFromStripeObject($object, $this);
        }

        return new ChargeCollection(...$charges);
    }


    /**
     * Create a checkout session object for a one-time payment, rather than a subscription or setting up a customer.
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig - whether the checkout is hosted by Stripe or embedded in your site, and the URLs
     * to send the customer to.
     *
     * @param SinglePaymentLineItemsCollection $items - A collection of SinglePaymentLineItem objects that are being
     * purchased.
     *
     * @param PaymentIntentData|null $paymentIntentData - A subset of parameters to be passed to PaymentIntent creation
     * for Checkout Sessions in payment mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data
     *
     * @param string|null $clientReferenceId - A unique string to reference the Checkout Session. This can be a
     * customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-client_reference_id
     *
     * @param Discount|null $discount - optionally provide a discount object for applying one of either a promotion
     * code, or a coupon code. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-discounts
     *
     * @param bool|null $allowPromoCodes - set to true if you want to add a box to the checkout that allows the user to
     * enter promo codes.
     *
     * @param Locale|null $locale - optionally specify the locale/language the stripe payment page should use. If null
     * (default), then stripe will use whatever the user's browser is set to.
     *
     * @param Currency|null $currency - optionally specify the currency. This is useful if you have products listed with
     * multiple possible currencies.
     *
     * @param SinglePaymentCheckoutCustomer|null $customer - optionally, who the session is for: an existing customer
     * (SinglePaymentCheckoutCustomer::existing()), an email address to fill in (withEmail()), or neither, with whether
     * Stripe creates a customer (create()). Leave as null for none of these, and Stripe's default of only creating a
     * customer if it's needed.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_creation
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice for the one-time payment
     * should be created. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param AutomaticTax|null $automaticTax - Details on the state of automatic tax for the session, including the
     * status of the latest tax calculation.
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-automatic_tax
     *
     * @param \DateTimeImmutable|null $expiresAt - when the checkout session should expire, from 30 minutes to 24 hours
     * after it's created. Defaults to 24 hours after.
     *
     * @param AfterExpiration|null $afterExpiration - Provides configuration for actions to take if this Checkout
     * Session expires. https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-after_expiration
     *
     * @param bool|null $adaptivePricing - if not provided, defaults to what is set on your Stripe dashboard.
     *
     * @param BillingAddressCollection $billingAddressCollection - Describe whether Checkout should collect the
     * customer’s billing address.
     *
     * @param CountryCodeCollection|null $shippingAddressCollection - provide this if you need Stripe to collect the
     * customer's shipping address for the transaction. This will need to be the country codes of the countries that
     * you will support shipping to. The default will be null, meaning Stripe would not collect shipping information.
     *
     * @param TaxIdCollectionConfig|null $taxIdCollection - Details on the state of tax ID collection for the session.
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-tax_id_collection
     *
     * @param ?bool $enablePhoneNumberCollection - optionally enable the collection of the user's phone number.
     * Stripe recommends that you review your privacy policy and check with your legal contacts before using this
     * feature. https://docs.stripe.com/payments/checkout/phone-numbers
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-phone_number_collection
     *
     * @param ConsentConfig|null $consentConfig - Configure fields for the Checkout Session to gather active consent
     * from customers. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
     *
     * @param CustomFieldCollection|null $customFields - extra fields to collect from the customer on the checkout
     * page, up to 3.
     *
     * @param string|null $paymentMethodConfigurationId - optionally provide the ID of the payment method configuration
     * to use with this Checkout session. https://docs.stripe.com/api/payment_methods
     *
     * @param PaymentMethodTypeCollection|null $allowedPaymentMethodTypes - optionally provide a list of the payment
     * method types you would allow the user to pay through on checkout. E.g. ['card']
     *
     * @param SavedPaymentMethodOptions|null $savedPaymentMethodOptions - Controls saved payment method settings for
     * the session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-saved_payment_method_options
     *
     * @param SubmitType $submitType - Describes the type of transaction being performed by Checkout in order to
     * customize relevant text on the page, such as the submit button. submit_type can only be specified on Checkout
     * Sessions in payment mode.
     *
     * @param Metadata|null $metadata - Set of key-value pairs to store against the checkout session.
     *
     * @param string|null $idempotencyKey - optionally, a unique key for this request (e.g. a UUID, or your own ID
     * for the order), so that if it's sent again, such as when retrying after a timeout, Stripe returns what the first
     * request made, rather than making it twice. Use a new key for each different request. Up to 255 characters.
     * https://docs.stripe.com/api/idempotent_requests
     *
     * @return CheckoutSession - the created checkout session.
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSessionForSinglePayment(
        FlowConfig                       $flowConfig,
        SinglePaymentLineItemsCollection $items,
        ?PaymentIntentData               $paymentIntentData = null,
        ?string                          $clientReferenceId = null,
        ?Discount                        $discount = null,
        ?bool                            $allowPromoCodes = null,
        ?Locale                          $locale = null,
        ?Currency                        $currency = null,
        ?SinglePaymentCheckoutCustomer   $customer = null,
        ?InvoiceCreation                 $invoiceCreation = null,
        ?CustomTextOptions               $customTextOptions = null,
        ?AutomaticTax                    $automaticTax = null,
        ?\DateTimeImmutable              $expiresAt = null,
        ?AfterExpiration                 $afterExpiration = null,
        ?bool                            $adaptivePricing = null,
        BillingAddressCollection         $billingAddressCollection = BillingAddressCollection::AUTO,
        ?CountryCodeCollection           $shippingAddressCollection = null,
        ?TaxIdCollectionConfig           $taxIdCollection = null,
        ?bool                            $enablePhoneNumberCollection = null,
        ?ConsentConfig                   $consentConfig = null,
        ?CustomFieldCollection           $customFields = null,
        ?string                          $paymentMethodConfigurationId = null,
        ?PaymentMethodTypeCollection     $allowedPaymentMethodTypes = null,
        ?SavedPaymentMethodOptions       $savedPaymentMethodOptions = null,
        SubmitType                       $submitType = SubmitType::AUTO,
        ?Metadata                        $metadata = null,
        ?string                          $idempotencyKey = null,
    ) : CheckoutSession
    {
        $params = $flowConfig->getStripeParams();
        $params['mode'] = SessionMode::PAYMENT->value;
        $params['line_items'] = $items->toStripeArrayForm();
        $params['submit_type'] = $submitType->value;
        if ($customer !== null) { $params = array_merge($params, $customer->getStripeParams()); }
        if ($paymentIntentData !== null) { $params['payment_intent_data'] = $paymentIntentData->toArray(); }
        if ($invoiceCreation !== null) { $params['invoice_creation'] = $invoiceCreation->toArray(); }

        $sharedParams = self::getSharedCheckoutParams(
            $clientReferenceId,
            $discount,
            $allowPromoCodes,
            $locale,
            $currency,
            $customTextOptions,
            $automaticTax,
            $expiresAt,
            $afterExpiration,
            $adaptivePricing,
            $billingAddressCollection,
            $shippingAddressCollection,
            $taxIdCollection,
            $enablePhoneNumberCollection,
            $consentConfig,
            $customFields,
            $paymentMethodConfigurationId,
            $allowedPaymentMethodTypes,
            $savedPaymentMethodOptions,
            $metadata
        );

        $params = array_merge($params, $sharedParams);
        $options = $this->getRequestOptions($idempotencyKey);
        $session = $this->m_underlyingStripeClient->checkout->sessions->create($params, $options);
        return CheckoutSession::createFromStripeObject($session, $this);
    }


    /**
     * Create a checkout session object for a subscription payment, rather than a one-time payment, or setting up a
     * customer. This can contain items that are paid for once, but should be used when there is at least one item
     * that has a subscription payment.
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig - whether the checkout is hosted by Stripe or embedded in your site, and the URLs
     * to send the customer to.
     *
     * @param SubscriptionCheckoutLineItemCollection $items - the items being subscribed to (or bought once alongside
     * the subscription).
     *
     * @param SubscriptionData|null $subscriptionData - A subset of parameters to be passed to subscription creation
     * for Checkout Sessions in subscription mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data
     *
     * @param string|null $clientReferenceId - A unique string to reference the Checkout Session. This can be a
     * customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-client_reference_id
     *
     * @param Discount|null $discount - optionally provide a discount object for applying one of either a promotion
     * code, or a coupon code. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-discounts
     *
     * @param bool|null $allowPromoCodes - set to true if you want to add a box to the checkout that allows the user to
     * enter promo codes.
     *
     * @param Locale|null $locale - optionally specify the locale/language the stripe payment page should use. If null
     * (default), then stripe will use whatever the user's browser is set to.
     *
     * @param Currency|null $currency - optionally specify the currency. This is useful if you have products listed with
     * multiple possible currencies.
     *
     * @param SubscriptionCheckoutCustomer|null $customer - optionally, who the session is for: an existing customer
     * (SubscriptionCheckoutCustomer::existing()), or an email address to fill in (withEmail()). Leave as null for
     * neither, so the customer enters their email address. Stripe always creates a customer for a new subscriber.
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice should be created.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param AutomaticTax|null $automaticTax - settings for automatic tax lookup for this session.
     *
     * @param \DateTimeImmutable|null $expiresAt - when the checkout session should expire, from 30 minutes to 24 hours
     * after it's created. Defaults to 24 hours after.
     *
     * @param AfterExpiration|null $afterExpiration - Provides configuration for actions to take if this Checkout
     * Session expires.
     *
     * @param bool|null $adaptivePricing - if not provided, defaults to what is set on your Stripe dashboard.
     *
     * @param BillingAddressCollection $billingAddressCollection - Describe whether Checkout should collect the
     * customer’s billing address.
     *
     * @param CountryCodeCollection|null $shippingAddressCollection - provide this if you need Stripe to collect the
     * customer's shipping address for the transaction. This will need to be the country codes of the countries that
     * you will support shipping to. The default will be null, meaning Stripe would not collect shipping information.
     *
     * @param TaxIdCollectionConfig|null $taxIdCollection - Details on the state of tax ID collection for the session.
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-tax_id_collection
     *
     * @param ?bool $enablePhoneNumberCollection - optionally enable the collection of the user's phone number.
     * Stripe recommends that you review your privacy policy and check with your legal contacts before using this
     * feature. https://docs.stripe.com/payments/checkout/phone-numbers
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-phone_number_collection
     *
     * @param ConsentConfig|null $consentConfig - Configure fields for the Checkout Session to gather active consent
     * from customers. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
     *
     * @param CustomFieldCollection|null $customFields - extra fields to collect from the customer on the checkout
     * page, up to 3.
     *
     * @param string|null $paymentMethodConfigurationId - optionally provide the ID of the payment method configuration
     * to use with this Checkout session. https://docs.stripe.com/api/payment_methods
     *
     * @param PaymentMethodTypeCollection|null $allowedPaymentMethodTypes - optionally provide a list of the payment
     * method types you would allow the user to pay through on checkout. E.g. ['card']
     *
     * @param SavedPaymentMethodOptions|null $savedPaymentMethodOptions - Controls saved payment method settings for
     * the session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-saved_payment_method_options
     *
     * @param Metadata|null $metadata - Set of key-value pairs to store against the checkout session.
     *
     * @param string|null $idempotencyKey - optionally, a unique key for this request (e.g. a UUID, or your own ID
     * for the order), so that if it's sent again, such as when retrying after a timeout, Stripe returns what the first
     * request made, rather than making it twice. Use a new key for each different request. Up to 255 characters.
     * https://docs.stripe.com/api/idempotent_requests
     *
     * @return CheckoutSession - the created checkout session.
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSessionForSubscription(
        FlowConfig                              $flowConfig,
        SubscriptionCheckoutLineItemCollection  $items,
        ?SubscriptionData                       $subscriptionData = null,
        ?string                                 $clientReferenceId = null,
        ?Discount                               $discount = null,
        ?bool                                   $allowPromoCodes = null,
        ?Locale                                 $locale = null,
        ?Currency                               $currency = null,
        ?SubscriptionCheckoutCustomer           $customer = null,
        ?InvoiceCreation                        $invoiceCreation = null,
        ?CustomTextOptions                      $customTextOptions = null,
        ?AutomaticTax                           $automaticTax = null,
        ?\DateTimeImmutable                     $expiresAt = null,
        ?AfterExpiration                        $afterExpiration = null,
        ?bool                                   $adaptivePricing = null,
        BillingAddressCollection                $billingAddressCollection = BillingAddressCollection::AUTO,
        ?CountryCodeCollection                  $shippingAddressCollection = null,
        ?TaxIdCollectionConfig                  $taxIdCollection = null,
        ?bool                                   $enablePhoneNumberCollection = null,
        ?ConsentConfig                          $consentConfig = null,
        ?CustomFieldCollection                  $customFields = null,
        ?string                                 $paymentMethodConfigurationId = null,
        ?PaymentMethodTypeCollection            $allowedPaymentMethodTypes = null,
        ?SavedPaymentMethodOptions              $savedPaymentMethodOptions = null,
        ?Metadata                               $metadata = null,
        ?string                                 $idempotencyKey = null,
    ) : CheckoutSession
    {
        $params = $flowConfig->getStripeParams();
        $params['mode'] = SessionMode::SUBSCRIPTION->value;
        $params['line_items'] = $items->toStripeArrayForm();
        if ($customer !== null) { $params = array_merge($params, $customer->getStripeParams()); }

        if ($subscriptionData !== null) { $params['subscription_data'] = $subscriptionData->toArray(); }
        if ($invoiceCreation !== null) { $params['invoice_creation'] = $invoiceCreation->toArray(); }

        $sharedParams = self::getSharedCheckoutParams(
            $clientReferenceId,
            $discount,
            $allowPromoCodes,
            $locale,
            $currency,
            $customTextOptions,
            $automaticTax,
            $expiresAt,
            $afterExpiration,
            $adaptivePricing,
            $billingAddressCollection,
            $shippingAddressCollection,
            $taxIdCollection,
            $enablePhoneNumberCollection,
            $consentConfig,
            $customFields,
            $paymentMethodConfigurationId,
            $allowedPaymentMethodTypes,
            $savedPaymentMethodOptions,
            $metadata
        );

        $params = array_merge($params, $sharedParams);
        $options = $this->getRequestOptions($idempotencyKey);
        $session = $this->m_underlyingStripeClient->checkout->sessions->create($params, $options);
        return CheckoutSession::createFromStripeObject($session, $this);
    }


    /**
     * Retrieve a Stripe checkout session by ID.
     * @param string $checkoutSessionId - the ID of the session to retrieve.
     * @return CheckoutSession - the checkout session.
     * @throws ApiErrorException
     */
    public function retrieveCheckoutSession(string $checkoutSessionId) : CheckoutSession
    {
        $session = $this->m_underlyingStripeClient->checkout->sessions->retrieve($checkoutSessionId);
        return CheckoutSession::createFromStripeObject($session, $this);
    }


    /**
     * Retrieve a customer by ID.
     * https://docs.stripe.com/api/customers/retrieve
     * @param string $customerId - the ID of the customer, e.g. "cus_123".
     * @return Customer
     * @throws ExceptionCustomerDeleted - if the customer has been deleted, as Stripe then only sends its ID.
     * @throws ApiErrorException - if there's no such customer, or Stripe can't be reached.
     */
    public function retrieveCustomer(string $customerId) : Customer
    {
        $customer = $this->m_underlyingStripeClient->customers->retrieve($customerId);
        return Customer::createFromStripeObject($customer, $this);
    }


    /**
     * Retrieve a subscription by ID.
     * https://docs.stripe.com/api/subscriptions/retrieve
     * @param string $subscriptionId - the ID of the subscription, e.g. "sub_123".
     * @return Subscription
     * @throws ApiErrorException - if there's no such subscription, or Stripe can't be reached.
     */
    public function retrieveSubscription(string $subscriptionId) : Subscription
    {
        $subscription = $this->m_underlyingStripeClient->subscriptions->retrieve($subscriptionId);
        return Subscription::createFromStripeObject($subscription, $this);
    }


    /**
     * Create a subscription for the user. Most of the time you will want to use the
     * createCheckoutSessionForSubscription method instead, but in certain circumstances, you may need
     * to just create the subscription and have it send an invoice to the customer instead.
     * https://docs.stripe.com/api/subscriptions/create
     *
     * @param SubscriptionCustomer $customer - who to subscribe: a customer, with SubscriptionCustomer::customer(), or
     * a customer account, with SubscriptionCustomer::customerAccount().
     *
     * @param SubscriptionLineItemCollection $items - items that should be charged in this subscription.
     *
     * @param Currency $currency - the currency of the subscription.
     *
     * @param string|null $description - The subscription’s description, meant to be displayable to the customer. Use
     * this field to optionally store an explanation of the subscription for rendering in Stripe surfaces and certain
     * local payment methods UIs. The maximum length is 500 characters.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-description
     *
     * @param AutomaticTax|null $automaticTax - Automatic tax settings for this subscription.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-automatic_tax
     *
     * @param string|null $defaultPaymentMethodId - ID of the default payment method for the subscription. It must
     * belong to the customer associated with the subscription. This takes precedence over default_source. If neither
     * are set, invoices will use the customer’s invoice_settings.default_payment_method or default_source.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-default_payment_method
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-metadata
     *
     * @param PaymentBehavior|null $paymentBehavior - Only applies to subscriptions with collection method set to
     * charge_automatically.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_behavior
     *
     * @param SubscriptionInvoiceItemCollection|null $addInvoiceItems - A list of prices and quantities that will
     * generate invoice items appended to the next invoice for this subscription. You may pass up to 20 items.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items
     *
     * @param float|null $applicationFeePercent - A non-negative decimal between 0 and 100, with at most two decimal
     * places. This represents the percentage of the subscription invoice total that will be transferred to the
     * application owner’s Stripe account. The request must be made by a platform account on a connected account in
     * order to set an application fee percentage.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-application_fee_percent
     *
     * @param \DateTimeImmutable|null $backdateStartDate - A past time to backdate the subscription’s start date to. If
     * set, the first invoice will contain line items for the timespan between the start date and the current time. Can
     * be combined with trials and the billing cycle anchor.
     *
     * @param BillingCycleAnchor|null $billingCycleAnchor - optionally, what to anchor the billing cycle to: a time,
     * with BillingCycleAnchor::at(), or a day of the month, with BillingCycleAnchor::onDayOfMonth(), which is only
     * valid with monthly and yearly prices.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor
     *
     * @param BillingMode|null $billingMode - Controls how prorations and invoices for subscriptions are calculated and
     * orchestrated. https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_mode
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_thresholds
     *
     * @param CancelAt|null $cancelAt - optionally, when the subscription cancels: at the end of the current period
     * (CancelAt::atPeriodEnd()), at a time (CancelAt::at()), or at the latest or earliest end of its items' billing
     * periods (CancelAt::atLongestItemPeriodEnd() or CancelAt::atShortestItemPeriodEnd()).
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - Either charge_automatically, or send_invoice. When
     * charging automatically, Stripe will attempt to pay this subscription at the end of the cycle using the default
     * source attached to the customer. When sending an invoice, Stripe will email your customer an invoice with
     * payment instructions and mark the subscription as active. Defaults to charge_automatically.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-collection_method
     *
     * @param int|null $daysUntilDue - Number of days a customer has to pay invoices generated by this subscription.
     * Valid only for subscriptions where collection_method is set to send_invoice.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-days_until_due
     *
     * @param string|null $defaultSource - ID of the default payment source for the subscription. It must belong to the
     * customer associated with the subscription and be in a chargeable state. If default_payment_method is also set,
     * default_payment_method will take precedence.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-default_source
     *
     * @param StringCollection|null $defaultTaxRates - The tax rates that will apply to any subscription item that does
     * not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-default_tax_rates
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the subscription. If not
     * specified or empty, inherits the discount from the subscription’s customer.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-discounts
     *
     * @param InvoiceSettings|null $invoiceSettings - All invoices will be billed using the specified settings.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-invoice_settings
     *
     * @param bool|null $offSession - Indicates if a customer is on or off-session while an invoice payment is
     * attempted. Defaults to false (on-session).
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-off_session
     *
     * @param string|null $onBehalfOf - The account on behalf of which to charge, for each of the subscription’s
     * invoices. https://docs.stripe.com/api/subscriptions/create#create_subscription-on_behalf_of
     *
     * @param PaymentSettings|null $paymentSettings - Payment settings to pass to invoices created by the subscription.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_settings
     *
     * @param RecurringConfig|null $pendingInvoiceItemInterval - how often to bill for any pending invoice items, if at
     * all.
     *
     * @param ProrationBehavior|null $prorationBehavior - how to handle prorations resulting from the
     * billing_cycle_anchor.
     *
     * @param SubscriptionTransferData|null $transferData - If specified, the funds from the subscription’s invoices
     * will be transferred to the connected account, optionally only a percentage of each.
     *
     * @param TrialConfig|null $trialConfig - how long a trial should last and how to behave when the trial ends.
     *
     * @param string|null $idempotencyKey - optionally, a unique key for this request (e.g. a UUID, or your own ID
     * for the order), so that if it's sent again, such as when retrying after a timeout, Stripe returns what the first
     * request made, rather than making it twice. Use a new key for each different request. Up to 255 characters.
     * https://docs.stripe.com/api/idempotent_requests
     *
     * @return Subscription - the created subscription.
     *
     * @throws ApiErrorException
     */
    public function createSubscription(
        SubscriptionCustomer                $customer,
        SubscriptionLineItemCollection      $items,
        Currency                            $currency,
        ?string                             $description = null,
        ?AutomaticTax                       $automaticTax = null,
        ?string                             $defaultPaymentMethodId = null,
        ?Metadata                           $metadata = null,
        ?PaymentBehavior                    $paymentBehavior = null,
        ?SubscriptionInvoiceItemCollection  $addInvoiceItems = null,
        ?float                              $applicationFeePercent = null,
        ?\DateTimeImmutable                 $backdateStartDate = null,
        ?BillingCycleAnchor                 $billingCycleAnchor = null,
        ?BillingMode                        $billingMode = null,
        ?BillingThresholds                  $billingThresholds = null,
        ?CancelAt                           $cancelAt = null,
        ?SubscriptionCollectionMethod       $collectionMethod = null,
        ?int                                $daysUntilDue = null,
        ?string                             $defaultSource = null,
        ?StringCollection                   $defaultTaxRates = null,
        ?DiscountCollection                 $discounts = null,
        ?InvoiceSettings                    $invoiceSettings = null,
        ?bool                               $offSession = null,
        ?string                             $onBehalfOf = null,
        ?PaymentSettings                    $paymentSettings = null,
        ?RecurringConfig                    $pendingInvoiceItemInterval = null,
        ?ProrationBehavior                  $prorationBehavior = null,
        ?SubscriptionTransferData           $transferData = null,
        ?TrialConfig                        $trialConfig = null,
        ?string                             $idempotencyKey = null,
    ) : Subscription
    {
        $params = [
            'items' => $items->toStripeArrayForm(),
            'currency' => $currency->value,
        ];

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }

        $params = array_merge($params, $customer->getStripeParams());

        if ($defaultPaymentMethodId !== null) { $params['default_payment_method'] = $defaultPaymentMethodId; }
        if ($description !== null) { $params['description'] = $description; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($paymentBehavior !== null) { $params['payment_behavior'] = $paymentBehavior->value; }
        if ($addInvoiceItems !== null) { $params['add_invoice_items'] = $addInvoiceItems->toStripeArrayForm(); }
        if ($applicationFeePercent !== null) { $params['application_fee_percent'] = $applicationFeePercent; }
        if ($backdateStartDate !== null) { $params['backdate_start_date'] = $backdateStartDate->getTimestamp(); }

        if ($billingCycleAnchor !== null) { $params = array_merge($params, $billingCycleAnchor->getStripeParams()); }

        if ($billingMode !== null) { $params['billing_mode'] = $billingMode->toArray(); }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }

        if ($cancelAt !== null) { $params = array_merge($params, $cancelAt->getStripeParams()); }

        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($daysUntilDue !== null) { $params['days_until_due'] = $daysUntilDue; }
        if ($defaultSource !== null) { $params['default_source'] = $defaultSource; }
        if ($defaultTaxRates !== null) { $params['default_tax_rates'] = $defaultTaxRates->toArray(); }
        if ($discounts !== null) { $params['discounts'] = $discounts->toStripeArrayForm(); }
        if ($invoiceSettings !== null) { $params['invoice_settings'] = $invoiceSettings->toArray(); }
        if ($offSession !== null) { $params['off_session'] = $offSession; }
        if ($onBehalfOf !== null) { $params['on_behalf_of'] = $onBehalfOf; }
        if ($paymentSettings !== null) { $params['payment_settings'] = $paymentSettings->toArray(); }

        if ($pendingInvoiceItemInterval !== null)
        {
            $params['pending_invoice_item_interval'] = $pendingInvoiceItemInterval->toArray();
        }

        if ($prorationBehavior !== null) { $params['proration_behavior'] = $prorationBehavior->value; }
        if ($transferData !== null) { $params['transfer_data'] = $transferData->toArray(); }
        if ($trialConfig !== null) { $params = array_merge($params, $trialConfig->getStripeParams()); }

        $options = $this->getRequestOptions($idempotencyKey);
        $subscription = $this->m_underlyingStripeClient->subscriptions->create($params, $options);
        return Subscription::createFromStripeObject($subscription, $this);
    }


    /**
     * List a customer's payment methods, asking Stripe for as many pages as it takes.
     * https://docs.stripe.com/api/payment_methods/customer_list
     *
     * @param string $customerId - the ID of the customer.
     *
     * @param PaymentMethodType|null $paymentMethodType - optionally, only list payment methods of this type.
     *
     * @param ResultLimit|null $limit - optionally, how many to get: ResultLimit::upTo() a number, or
     * ResultLimit::all(). Without one, up to ResultLimit::DEFAULT_SAFETY_CAP are got, and an ExceptionTooManyResults
     * is thrown if more match, rather than quietly giving back only some of them.
     *
     * @param string|null $startingAfter - optionally, the ID of the object to start after, e.g. the last one a
     * previous call gave back, to page through the list. The list is newest first, so this goes back in time.
     *
     * @param string|null $endingBefore - optionally, the ID of the object to end before, to page back towards the
     * newest.
     *
     * @return PaymentMethodCollection
     *
     * @throws ExceptionTooManyResults - if more than the limit match, and no limit was given.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function listCustomerPaymentMethods(
        string $customerId,
        ?PaymentMethodType $paymentMethodType = null,
        ?ResultLimit $limit = null,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ) : PaymentMethodCollection
    {
        $params = self::getPagingParams($startingAfter, $endingBefore);

        if ($paymentMethodType !== null) { $params['type'] = $paymentMethodType->value; }

        $requestPage = function (array $pageParams) use ($customerId): Collection {
            return $this->m_underlyingStripeClient->customers->allPaymentMethods($customerId, $pageParams);
        };

        $paymentMethods = [];

        foreach ($this->fetchEveryPage($requestPage, $params, $limit, 'payment methods') as $object)
        {
            $paymentMethods[] = PaymentMethod::createFromStripeObject($object, $this);
        }

        return new PaymentMethodCollection(...$paymentMethods);
    }




    /**
     * Get every object a list request matches, up to its limit, asking Stripe for up to 100 at a time (its maximum),
     * with the SDK's own paging, which carries the filters and cursor from one page to the next.
     *
     * @param callable(array<string, mixed>): Collection<covariant \Stripe\StripeObject> $requestPage - asks Stripe
     * for one page, with the parameters given.
     *
     * @param array<string, mixed> $params - the list's filters and cursor.
     *
     * @param ResultLimit|null $limit - how many to get, or null for up to the safety cap, which throws if more match.
     *
     * @param string $whatIsListed - what's being listed, for the exception's message, e.g. "customers".
     *
     * @return list<\Stripe\StripeObject>
     *
     * @throws ExceptionTooManyResults - if no limit was given, and more than the safety cap match.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses a request.
     */
    private function fetchEveryPage(
        callable $requestPage,
        array $params,
        ?ResultLimit $limit,
        string $whatIsListed
    ) : array
    {
        if ($limit === null)
        {
            $maximum = ResultLimit::DEFAULT_SAFETY_CAP;
            $throwIfMore = true;
        }
        elseif ($limit->isUnlimited())
        {
            $maximum = null;
            $throwIfMore = false;
        }
        else
        {
            $maximum = $limit->getMaximum();
            $throwIfMore = false;
        }

        # Don't ask for more than are wanted, though up to the safety cap, a full page is asked for, to find out if
        # there are more.
        $params['limit'] = ($maximum === null || $throwIfMore) ? 100 : min(100, $maximum);
        $objects = [];
        $isOverLimit = false;

        foreach ($requestPage($params)->autoPagingIterator() as $object)
        {
            if ($maximum !== null && count($objects) >= $maximum)
            {
                $isOverLimit = true;
                break;
            }

            $objects[] = $object;
        }

        if ($isOverLimit && $throwIfMore)
        {
            throw new ExceptionTooManyResults(
                "More than {$maximum} {$whatIsListed} match. Narrow the filters, or pass a limit: " .
                "ResultLimit::upTo() a number, or ResultLimit::all()."
            );
        }

        return $objects;
    }


    /**
     * Get the parameters for where a list should start or end, for paging through it.
     * @param string|null $startingAfter - the ID of the object to start after, or null.
     * @param string|null $endingBefore - the ID of the object to end before, or null.
     * @return array<string, mixed>
     */
    private static function getPagingParams(?string $startingAfter, ?string $endingBefore) : array
    {
        $params = [];

        if ($startingAfter !== null) { $params['starting_after'] = $startingAfter; }
        if ($endingBefore !== null) { $params['ending_before'] = $endingBefore; }

        return $params;
    }

    /**
     * Get the options for a request that changes something: just the idempotency key, if one was given.
     * @param string|null $idempotencyKey - the key, or null for none.
     * @return array{idempotency_key?: string}
     * @throws ExceptionInvalidValue - if the key is empty, or longer than Stripe's 255 characters.
     */
    private function getRequestOptions(?string $idempotencyKey) : array
    {
        $options = [];

        if ($idempotencyKey !== null)
        {
            if ($idempotencyKey === '' || strlen($idempotencyKey) > 255)
            {
                throw new ExceptionInvalidValue(
                    "An idempotency key must be 1 to 255 characters, but was " . strlen($idempotencyKey) . "."
                );
            }

            $options['idempotency_key'] = $idempotencyKey;
        }

        return $options;
    }


    /**
     * Get the checkout session parameters that are the same for a single payment and a subscription. Each argument
     * is the parameter of the same name in createCheckoutSessionForSinglePayment(), and is left out if null.
     *
     * @param string|null $clientReferenceId
     * @param Discount|null $discount
     * @param bool|null $allowPromoCodes
     * @param Locale|null $locale
     * @param Currency|null $currency
     * @param CustomTextOptions|null $customTextOptions
     * @param AutomaticTax|null $automaticTax
     * @param \DateTimeImmutable|null $expiresAt
     * @param AfterExpiration|null $afterExpiration
     * @param bool|null $adaptivePricing
     * @param BillingAddressCollection $billingAddressCollection
     * @param CountryCodeCollection|null $shippingAddressCollection
     * @param TaxIdCollectionConfig|null $taxIdCollection
     * @param bool|null $enablePhoneNumberCollection
     * @param ConsentConfig|null $consentConfig
     * @param CustomFieldCollection|null $customFields
     * @param string|null $paymentMethodConfigurationId
     * @param PaymentMethodTypeCollection|null $allowedPaymentMethodTypes
     * @param SavedPaymentMethodOptions|null $savedPaymentMethodOptions
     * @param Metadata|null $metadata
     *
     * @return array<string, mixed> - the parameters to add to the checkout session.
     */
    private static function getSharedCheckoutParams(
        ?string $clientReferenceId,
        ?Discount $discount,
        ?bool $allowPromoCodes,
        ?Locale $locale,
        ?Currency $currency,
        ?CustomTextOptions $customTextOptions,
        ?AutomaticTax $automaticTax,
        ?\DateTimeImmutable $expiresAt,
        ?AfterExpiration $afterExpiration,
        ?bool $adaptivePricing,
        BillingAddressCollection $billingAddressCollection,
        ?CountryCodeCollection $shippingAddressCollection,
        ?TaxIdCollectionConfig $taxIdCollection,
        ?bool $enablePhoneNumberCollection,
        ?ConsentConfig $consentConfig,
        ?CustomFieldCollection $customFields,
        ?string $paymentMethodConfigurationId,
        ?PaymentMethodTypeCollection $allowedPaymentMethodTypes,
        ?SavedPaymentMethodOptions $savedPaymentMethodOptions,
        ?Metadata $metadata,
    ) : array
    {
        $params = ['billing_address_collection' => $billingAddressCollection->value];

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }
        if ($clientReferenceId !== null) { $params['client_reference_id'] = $clientReferenceId; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($adaptivePricing !== null) { $params['adaptive_pricing'] = $adaptivePricing; }
        if ($afterExpiration !== null) { $params['after_expiration'] = $afterExpiration->toArray(); }
        if ($allowPromoCodes !== null) { $params['allow_promotion_codes'] = $allowPromoCodes; }
        if ($consentConfig !== null) { $params['consent_collection'] = $consentConfig->toArray(); }
        if ($currency !== null) { $params['currency'] = $currency->value; }
        if ($customFields !== null) { $params['custom_fields'] = $customFields->toStripeArrayForm(); }
        if ($customTextOptions !== null) { $params['custom_text'] = $customTextOptions->toArray(); }
        if ($taxIdCollection !== null) { $params['tax_id_collection'] = $taxIdCollection->toArray(); }

        if ($shippingAddressCollection !== null)
        {
            $params['shipping_address_collection'] = [
                'allowed_countries' => $shippingAddressCollection->toStripeArrayForm()
            ];
        }

        // Stripe takes a list here, but only accepts one discount.
        if ($discount !== null) { $params['discounts'] = [$discount->toArray()]; }
        if ($expiresAt !== null) { $params['expires_at'] = $expiresAt->getTimestamp(); }

        if ($enablePhoneNumberCollection !== null)
        {
            $params['phone_number_collection'] = ['enabled' => $enablePhoneNumberCollection];
        }

        if ($locale !== null) { $params['locale'] = $locale->value; }

        if ($paymentMethodConfigurationId !== null)
        {
            $params['payment_method_configuration'] = $paymentMethodConfigurationId;
        }

        if ($allowedPaymentMethodTypes !== null)
        {
            $params['payment_method_types'] = $allowedPaymentMethodTypes->toStripeArrayForm();
        }

        if ($savedPaymentMethodOptions !== null)
        {
            $params['saved_payment_method_options'] = $savedPaymentMethodOptions->toArray();
        }

        return $params;
    }
}
