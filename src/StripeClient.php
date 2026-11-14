<?php

namespace Programster\Stripe;

use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\CustomerShippingDetailsCollection;
use Programster\Stripe\Collections\CustomerTaxIdDataCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
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
use Programster\Stripe\Enums\CancelAtEnum;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\CustomerCreation;
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
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingCycleAnchorConfig;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\BillingThresholds;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\InvoiceSettings;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\PaymentSettings;
use Programster\Stripe\Models\PriceDataForSubscriptionItem;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\StripeConnectPaymentConfig;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\TaxIdCollectionConfig;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\TransferData;
use Programster\Stripe\Models\TrialConfig;
use Stripe\Checkout\Session;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionItem;

readonly class StripeClient
{
    private \Stripe\StripeClient $m_underlyingStripeClient;


    public function __construct(#[\SensitiveParameter] string $secretKey)
    {
        $this->m_underlyingStripeClient = new \Stripe\StripeClient($secretKey);

        // This package no longer relies on the global key, but it is still set for anyone who relies on it being
        // set as a side effect of creating this client.
        Stripe::setApiKey($secretKey);
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
     * List subscriptions - https://docs.stripe.com/api/subscriptions/list?lang=php
     *
     * @param string|null $customerId - optionally pass the ID of a customer to filter subscriptions to those belonging
     * to a specific customer.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * subscriptions after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * subscriptions before this pointer.
     *
     * @param string|null $priceId - optionally filter for subscriptions that contain this recurring price ID.
     *
     * @param TimePeriod|null $created - optionally filter subscriptions created within a certain time period.
     *
     * @param TimePeriod|null $currentPeriodEnd - Only return subscriptions whose current_period_end falls within the
     * given date interval.
     *
     * @param TimePeriod|null $currentPeriodStart - Only return subscriptions whose current_period_start falls within
     * the given date interval.
     *
     * @param SubscriptionStatus|null $subscriptionStatus - filter subscriptions by their status.
     *
     * @param bool|null $automaticTaxEnabled - filter subscriptions based on whether they have auto tax enabled or not.
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - filter subscriptions based on whether they auto
     * charge or send an invoice to be paid.
     *
     * @param string|null $testClock - optionally filter subscriptions that have the specified test clock in
     * development.
     *
     * @param int $limit - optionally specify the limit to the number of subscriptions in the result (pagination). This
     * package's default is 100, which is the maximum, so you can only override it to reduce the number.
     *
     * @return Collection<\Stripe\Subscription>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listSubscriptions(
        ?string $customerId = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?string $priceId = null,
        ?TimePeriod $created = null,
        ?TimePeriod $currentPeriodEnd = null,
        ?TimePeriod $currentPeriodStart = null,
        ?SubscriptionStatus $subscriptionStatus = null,
        ?bool $automaticTaxEnabled = null,
        ?SubscriptionCollectionMethod $collectionMethod = null,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($priceId !== null) { $params['price'] = $priceId; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($currentPeriodEnd !== null) { $params['current_period_end'] = $currentPeriodEnd->toArray(); }
        if ($currentPeriodStart !== null) { $params['current_period_start'] = $currentPeriodStart->toArray(); }
        if ($subscriptionStatus !== null) { $params['status'] = $subscriptionStatus->value; }
        if ($automaticTaxEnabled !== null) { $params['automatic_tax'] = ['enabled' => $automaticTaxEnabled]; }
        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        return $this->m_underlyingStripeClient->subscriptions->all($params);
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
     * @param CustomerShippingDetailsCollection|null $shippingDetails - The customer’s shipping information, which
     * appears on invoices emailed to this customer. Stripe only allows one, so the collection must contain exactly
     * one item.
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
     * @throws ExceptionInvalidValue - if $shippingDetails doesn't contain exactly one item.
     */
    public function createCustomer(
        ?Address $address = null,
        ?string $description = null,
        ?string $email = null,
        ?Metadata $metadata = null,
        ?string $name = null,
        ?string $paymentMethodId = null,
        ?string $phone = null,
        ?CustomerShippingDetailsCollection $shippingDetails = null,
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

        if ($shippingDetails !== null)
        {
            // Stripe only takes a single shipping address for a customer, under "shipping".
            if (count($shippingDetails) !== 1)
            {
                throw new ExceptionInvalidValue(
                    "A customer can only have one set of shipping details, but " . count($shippingDetails) .
                    " were provided."
                );
            }

            $params['shipping'] = $shippingDetails->toStripeArrayForm()[0];
        }

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
        return $this->m_underlyingStripeClient->customers->create($params, $options);
    }


    /**
     * List all subscription items. Returns a list of your subscription items for a given subscription.
     * https://docs.stripe.com/api/subscription_items/list?lang=php
     *
     * @param string $subscriptionId - the ID of the subscription to get the items for.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * items after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * items before this pointer.
     *
     * @param string|null $testClock - optionally filter by the specified test clock in development.
     *
     * @param int $limit - the maximum number of items to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<SubscriptionItem>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listSubscriptionItems(
        string $subscriptionId,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);

        $params = [
            'subscription' => $subscriptionId,
            'limit' => $limit,
        ];

        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        return $this->m_underlyingStripeClient->subscriptionItems->all($params);
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
     * @param string|PriceDataForSubscriptionItem|null $priceDataOrPriceId - either the ID of a price object to use for
     * the subscription item, or the object form that fully provides all of the details. Leave as null to not change.
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
     * @param int|null $prorationDate - If set, the proration will be calculated as though the subscription was updated
     * at the given time. This can be used to apply the same proration that was previewed with the upcoming invoice
     * endpoint.
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
        null|string|PriceDataForSubscriptionItem $priceDataOrPriceId = null,
        ?ProrationBehavior                       $prorationBehavior = null,
        ?int                                     $quantity = null,
        ?BillingThresholds                       $billingThresholds = null,
        ?DiscountCollection                      $discounts = null,
        ?bool                                    $offSession = null,
        ?int                                     $prorationDate = null,
        ?StringCollection                        $taxRateIds = null,
        ?string                                  $idempotencyKey = null,
    ) : SubscriptionItem
    {
        $params = [];

        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($paymentBehavior !== null) { $params['payment_behavior'] = $paymentBehavior->value; }

        if (is_string($priceDataOrPriceId))
        {
            $params['price'] = $priceDataOrPriceId;
        }
        elseif ($priceDataOrPriceId !== null)
        {
            $params['price_data'] = $priceDataOrPriceId->toArray();
        }

        if ($prorationBehavior !== null) { $params['proration_behavior'] = $prorationBehavior->value; }
        if ($quantity !== null) { $params['quantity'] = $quantity; }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }
        if ($discounts !== null) { $params['discounts'] = $discounts->toStripeArrayForm(); }
        if ($offSession !== null) { $params['off_session'] = $offSession; }
        if ($prorationDate !== null) { $params['proration_date'] = $prorationDate; }
        if ($taxRateIds !== null) { $params['tax_rates'] = $taxRateIds->toArray(); }

        $options = $this->getRequestOptions($idempotencyKey);
        return $this->m_underlyingStripeClient->subscriptionItems->update($subscriptionItemId, $params, $options);
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

        return $this->m_underlyingStripeClient->subscriptions->cancel($subscriptionId, $params);
    }


    /**
     * Retrieve payment intents in the account.
     * https://docs.stripe.com/api/payment_intents/list
     *
     * @param string|null $customerId - optionally specify the (Stripe) ID of a customer to retrieve payment intents
     * specific to them.
     *
     * @param string|null $customerAccountId - optionally specify the Stripe account ID to retrieve payments related
     * to them.
     *
     * @param TimePeriod|null $created - optionally only return payment intents created within this time period.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * payment intents after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * payment intents before this pointer.
     *
     * @param string|null $testClock - optionally filter by the specified test clock in development.
     *
     * @param int $limit - A limit on the number of objects to be returned. Limit can range between 1 and 100.
     *
     * @return Collection<\Stripe\PaymentIntent>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listPaymentIntents(
        ?string $customerId = null,
        ?string $customerAccountId = null,
        ?TimePeriod $created = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccountId !== null) { $params['customer_account'] = $customerAccountId; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        return $this->m_underlyingStripeClient->paymentIntents->all($params);
    }


    /**
     * You can list all invoices, or list the invoices for a specific customer. The invoices are returned sorted by
     * creation date, with the most recently created invoices appearing first.
     * https://docs.stripe.com/api/invoices/list
     *
     * @param string|null $customerId - optionally specify a customer ID to only fetch invoices relating to that
     * customer.
     *
     * @param string|null $customerAccount - optionally specify a customer account to only fetch invoices relating to
     * that customer.
     *
     * @param InvoiceStatus|null $status - optionally provide a status to only show invoices with that status.
     *
     * @param string|null $subscriptionId - optionally provide the ID of a subscription to only retrieve invoices
     * related to that subscription.
     *
     * @param SubscriptionCollectionMethod|null $collectionMethod - optionally specify a collection method to filter by.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * invoices after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * invoices before this pointer.
     *
     * @param TimePeriod|null $created - optionally only return invoices created within this time period.
     *
     * @param bool $expandPayments - if set to true (default), then this will include details of all the payments made
     * against an invoice. You need this if you wish to look up the payment intents that relate to an invoice (which
     * is generally the only way to work out which payment intents were against a subscription.
     * E.g. subscription -> invoices -> payment intents)
     *
     * @param string|null $testClock - optionally filter by the specified test clock in development.
     *
     * @param int $limit - the maximum number of invoices to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<\Stripe\Invoice>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listInvoices(
        ?string $customerId = null,
        ?string $customerAccount = null,
        ?InvoiceStatus $status = null,
        ?string $subscriptionId = null,
        ?SubscriptionCollectionMethod $collectionMethod = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?TimePeriod $created = null,
        bool $expandPayments = true,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccount !== null) { $params['customer_account'] = $customerAccount; }
        if ($status !== null) { $params['status'] = $status->value; }
        if ($subscriptionId !== null) { $params['subscription'] = $subscriptionId; }
        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }
        if ($expandPayments === true) { $params['expand'] = ['data.payments']; }

        return $this->m_underlyingStripeClient->invoices->all($params);
    }


    /**
     * Returns a list of your customers. The customers are returned sorted by creation date, with the most recent
     * customers appearing first.
     * https://docs.stripe.com/api/customers/list
     *
     * @param string|null $email - optionally only return customers with this exact email address (case-sensitive).
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * customers after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * customers before this pointer.
     *
     * @param TimePeriod|null $created - optionally only return customers created within this time period.
     *
     * @param string|null $testClock - optionally only return customers attached to this test clock.
     *
     * @param int $limit - the maximum number of customers to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<\Stripe\Customer>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listCustomers(
        ?string $email = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($email !== null) { $params['email'] = $email; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        return $this->m_underlyingStripeClient->customers->all($params);
    }


    /**
     * Returns a list of all refunds you created. We return the refunds in sorted order, with the most recent refunds
     * appearing first. The 10 most recent refunds are always available by default on the Charge object.
     * https://docs.stripe.com/api/refunds/list
     *
     * @param string|null $chargeId - optionally provide the ID of a charge, in order to only get refunds for that
     * charge.
     *
     * @param string|null $paymentIntentId - optionally provide the ID of a payment intent, in order to only get
     * refunds for that payment intent.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * refunds after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * refunds before this pointer.
     *
     * @param TimePeriod|null $created - optionally only return refunds created within this time period.
     *
     * @param int $limit - the maximum number of refunds to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<\Stripe\Refund>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listRefunds(
        ?string $chargeId = null,
        ?string $paymentIntentId = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?TimePeriod $created = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($chargeId !== null) { $params['charge'] = $chargeId; }
        if ($paymentIntentId !== null) { $params['payment_intent'] = $paymentIntentId; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }

        return $this->m_underlyingStripeClient->refunds->all($params);
    }


    /**
     * Returns a list of charges you’ve previously created. The charges are returned in sorted order, with the most
     * recent charges appearing first.
     * https://docs.stripe.com/api/charges/list
     *
     * @param string|null $customerId - optionally only return charges for the customer with this ID.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * charges after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * charges before this pointer.
     *
     * @param TimePeriod|null $created - optionally only return charges created within this time period.
     *
     * @param string|null $paymentIntent - optionally only return charges that were created by the payment intent with
     * this ID.
     *
     * @param string|null $transferGroup - optionally only return charges for this transfer group.
     *
     * @param int $limit - the maximum number of charges to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<\Stripe\Charge>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listCharges(
        ?string $customerId = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?TimePeriod $created = null,
        ?string $paymentIntent = null,
        ?string $transferGroup = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($paymentIntent !== null) { $params['payment_intent'] = $paymentIntent; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($transferGroup !== null) { $params['transfer_group'] = $transferGroup; }

        return $this->m_underlyingStripeClient->charges->all($params);
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
     * @param ExistingCustomer|string|null $existingStripeCustomerOrEmail - if you have an existing stripe customer ID,
     * create one of these objects with it, and optionally a configuration for whether that customer is updatable.
     * Alternatively, if you know the customer's email address, then provide that as a string. This parameter is a
     * combination of the existing_customer and customer_email parameters, as only one of these can be set.
     * WARNING - do not simply provide the string ID of the customer as this will not work.
     *
     * @param CustomerCreation|null $customerCreation - specify if a customer should be created in Stripe or not for
     * this payment. The value and docs talk about "if required", which is the case for a subscription, but this method
     * is for a payment, so creating a customer should not be required.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_creation
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice for the one-time payment
     * should be created. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param StripeConnectPaymentConfig|null $stripeConnectConfig - NOTE: this currently has no effect, and can only
     * be null, as a StripeConnectPaymentConfig can't be created yet. It is planned to be redesigned in the next major
     * version. Use $paymentIntentData (on_behalf_of, transfer_data, application_fee_amount) for Stripe Connect.
     *
     * @param AutomaticTax|null $automaticTax - Details on the state of automatic tax for the session, including the
     * status of the latest tax calculation.
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-automatic_tax
     *
     * @param int|null $expiresAt - the unix timestamp when the checkout session should expire. Defaults to 24 hours
     * from creation.
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
     * @return Session - the created checkout session.
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $existingStripeCustomerOrEmail is a string that isn't an email address.
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
        ExistingCustomer|string|null     $existingStripeCustomerOrEmail = null,
        ?CustomerCreation                $customerCreation = null,
        ?InvoiceCreation                 $invoiceCreation = null,
        ?CustomTextOptions               $customTextOptions = null,
        ?StripeConnectPaymentConfig      $stripeConnectConfig = null,
        ?AutomaticTax                    $automaticTax = null,
        ?int                             $expiresAt = null,
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
    ) : Session
    {
        $params = $flowConfig->getStripeParams();
        $params['mode'] = SessionMode::PAYMENT->value;
        $params['line_items'] = $items->toStripeArrayForm();
        $params['submit_type'] = $submitType->value;
        $params = array_merge($params, self::getCustomerParams($existingStripeCustomerOrEmail));

        if ($customerCreation !== null) { $params['customer_creation'] = $customerCreation->value; }
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
        return $this->m_underlyingStripeClient->checkout->sessions->create($params, $options);
    }


    /**
     * Create a checkout session object for a subscription payment, rather than a one-time payment, or setting up a
     * customer. This can contain items that are paid for once, but should be used when there is at least one item
     * that has a subscription payment. Please note that customerCreation is deliberately not included in this method
     * as Stripe does not allow it, as it is not applicable, like it is for createCheckoutSessionForSinglePayment.
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
     * @param ExistingCustomer|string|null $existingStripeCustomerOrEmail - if you have an existing stripe customer ID,
     * create one of these objects with it, and optionally a configuration for whether that customer is updatable.
     * Alternatively, if you know the customer's email address, then provide that as a string. This parameter is a
     * combination of the existing_customer and customer_email parameters, as only one of these can be set.
     * WARNING - do not simply provide the string ID of the customer as this will not work.
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice should be created.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param AutomaticTax|null $automaticTax - settings for automatic tax lookup for this session.
     *
     * @param int|null $expiresAt - the unix timestamp when the checkout session should expire. Defaults to 24 hours
     * from creation.
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
     * @return Session - the created checkout session.
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $existingStripeCustomerOrEmail is a string that isn't an email address.
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
        ExistingCustomer|string|null            $existingStripeCustomerOrEmail = null,
        ?InvoiceCreation                        $invoiceCreation = null,
        ?CustomTextOptions                      $customTextOptions = null,
        ?AutomaticTax                           $automaticTax = null,
        ?int                                    $expiresAt = null,
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
    ) : Session
    {
        $params = $flowConfig->getStripeParams();
        $params['mode'] = SessionMode::SUBSCRIPTION->value;
        $params['line_items'] = $items->toStripeArrayForm();
        $params = array_merge($params, self::getCustomerParams($existingStripeCustomerOrEmail));

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
        return $this->m_underlyingStripeClient->checkout->sessions->create($params, $options);
    }


    /**
     * Retrieve a Stripe checkout session by ID.
     * @param string $checkoutSessionId - the ID of the session to retrieve.
     * @return Session - The Stripe checkout session.
     * @throws ApiErrorException
     */
    public function retrieveCheckoutSession(string $checkoutSessionId) : Session
    {
        return $this->m_underlyingStripeClient->checkout->sessions->retrieve($checkoutSessionId);
    }


    /**
     * Create a subscription for the user. Most of the time you will want to use the
     * createCheckoutSessionForSubscription method instead, but in certain circumstances, you may need
     * to just create the subscription and have it send an invoice to the customer instead.
     * https://docs.stripe.com/api/subscriptions/create
     *
     * @param ExistingCustomer|string $existingCustomerOrCustomerAccount - the customer to subscribe, as an
     * ExistingCustomer, or the ID of a customer account (acct_...) as a string. A customer ID (cus_...) given as a
     * string is rejected, as it must be given as an ExistingCustomer.
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
     * @param int|null $backdateStartDate - A past timestamp to backdate the subscription’s start date to. If set, the
     * first invoice will contain line items for the timespan between the start date and the current time. Can be
     * combined with trials and the billing cycle anchor.
     *
     * @param int|BillingCycleAnchorConfig|null $billingCycleAnchorTimestampOrConfig - either a timestamp to anchor the
     * billing cycle to, or a config for the day of the month (etc.) to anchor it to, which is only valid with monthly
     * and yearly price intervals.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor_config
     *
     * @param BillingMode|null $billingMode - Controls how prorations and invoices for subscriptions are calculated and
     * orchestrated. https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_mode
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_thresholds
     *
     * @param int|CancelAtEnum|bool|null $cancelAt - optionally provide a boolean true to specify that this should
     * cancel at the period end. Alternatively, you can provide an integer timestamp of when to cancel. Alternatively
     * to that, you can even provide a CancelAtEnum to specify if you wish to cancel the subscription at the maximum
     * or the minimum of the subscription items' billing periods.
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
     * @param TransferData|null $transferData - If specified, the funds from the subscription’s invoices will be
     * transferred to the destination, with its amount used as the percentage of each invoice to transfer.
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
     * @throws ExceptionInvalidValue - if $existingCustomerOrCustomerAccount is a customer ID given as a string.
     */
    public function createSubscription(
        ExistingCustomer|string             $existingCustomerOrCustomerAccount,
        SubscriptionLineItemCollection      $items,
        Currency                            $currency,
        ?string                             $description = null,
        ?AutomaticTax                       $automaticTax = null,
        ?string                             $defaultPaymentMethodId = null,
        ?Metadata                           $metadata = null,
        ?PaymentBehavior                    $paymentBehavior = null,
        ?SubscriptionInvoiceItemCollection  $addInvoiceItems = null,
        ?float                              $applicationFeePercent = null,
        ?int                                $backdateStartDate = null,
        null|int|BillingCycleAnchorConfig   $billingCycleAnchorTimestampOrConfig = null,
        ?BillingMode                        $billingMode = null,
        ?BillingThresholds                  $billingThresholds = null,
        null|int|CancelAtEnum|bool          $cancelAt = null,
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
        ?TransferData                       $transferData = null,
        ?TrialConfig                        $trialConfig = null,
        ?string                             $idempotencyKey = null,
    ) : Subscription
    {
        $params = [
            'items' => $items->toStripeArrayForm(),
            'currency' => $currency->value,
        ];

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }

        if (is_string($existingCustomerOrCustomerAccount))
        {
            if (str_starts_with($existingCustomerOrCustomerAccount, "cus_"))
            {
                throw new ExceptionInvalidValue(
                    "A string is treated as a customer account ID, but {$existingCustomerOrCustomerAccount} is a " .
                    "customer ID. Pass it as new ExistingCustomer('{$existingCustomerOrCustomerAccount}') instead."
                );
            }

            $params['customer_account'] = $existingCustomerOrCustomerAccount;
        }
        else
        {
            $params['customer'] = $existingCustomerOrCustomerAccount->getCustomerId();
        }

        if ($defaultPaymentMethodId !== null) { $params['default_payment_method'] = $defaultPaymentMethodId; }
        if ($description !== null) { $params['description'] = $description; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($paymentBehavior !== null) { $params['payment_behavior'] = $paymentBehavior->value; }
        if ($addInvoiceItems !== null) { $params['add_invoice_items'] = $addInvoiceItems->toStripeArrayForm(); }
        if ($applicationFeePercent !== null) { $params['application_fee_percent'] = $applicationFeePercent; }
        if ($backdateStartDate !== null) { $params['backdate_start_date'] = $backdateStartDate; }

        if (is_int($billingCycleAnchorTimestampOrConfig))
        {
            $params['billing_cycle_anchor'] = $billingCycleAnchorTimestampOrConfig;
        }
        elseif ($billingCycleAnchorTimestampOrConfig !== null)
        {
            $params['billing_cycle_anchor_config'] = $billingCycleAnchorTimestampOrConfig->toArray();
        }

        if ($billingMode !== null) { $params['billing_mode'] = $billingMode->toArray(); }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }

        if (is_int($cancelAt))
        {
            $params['cancel_at'] = $cancelAt;
        }
        elseif (is_bool($cancelAt))
        {
            $params['cancel_at_period_end'] = $cancelAt;
        }
        elseif ($cancelAt !== null)
        {
            $params['cancel_at'] = $cancelAt->value;
        }

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
        if ($transferData !== null) { $params['transfer_data'] = $transferData->toSubscriptionArray(); }
        if ($trialConfig !== null) { $params = array_merge($params, $trialConfig->getStripeParams()); }

        $options = $this->getRequestOptions($idempotencyKey);
        return $this->m_underlyingStripeClient->subscriptions->create($params, $options);
    }


    /**
     * Retrieve a customer's payment methods.
     * https://docs.stripe.com/api/payment_methods/customer_list
     *
     * @param string $customerId - the ID of the customer whose payment methods to list.
     *
     * @param PaymentMethodType|null $paymentMethodType - optionally only list payment methods of this type.
     *
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * payment methods after this pointer.
     *
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     * payment methods before this pointer.
     *
     * @param int $limit - the maximum number of payment methods to return, from 1 to 100. Defaults to 100.
     *
     * @return Collection<\Stripe\PaymentMethod>
     *
     * @throws ApiErrorException
     * @throws ExceptionInvalidValue - if $limit isn't from 1 to 100.
     */
    public function listCustomerPaymentMethods(
        string $customerId,
        ?PaymentMethodType $paymentMethodType = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        int $limit = 100,
    ) : Collection
    {
        self::assertValidLimit($limit);
        $params = ['limit' => $limit];

        if ($paymentMethodType !== null) { $params['type'] = $paymentMethodType->value; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }

        return $this->m_underlyingStripeClient->customers->allPaymentMethods($customerId, $params);
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
     * Check a list method's limit is within the range Stripe allows.
     * @param int $limit - the maximum number of objects to return.
     * @return void
     * @throws ExceptionInvalidValue - if it isn't from 1 to 100.
     */
    private static function assertValidLimit(int $limit) : void
    {
        if ($limit < 1 || $limit > 100)
        {
            throw new ExceptionInvalidValue("The limit must be from 1 to 100, but {$limit} was given.");
        }
    }


    /**
     * Get the checkout session parameters for an existing customer, or the email address of a customer.
     *
     * @param ExistingCustomer|string|null $existingStripeCustomerOrEmail - the existing customer, their email address,
     * or null for neither.
     *
     * @return array<string, mixed> - the parameters to add to the checkout session.
     *
     * @throws ExceptionInvalidValue - if it is a string that isn't an email address.
     */
    private static function getCustomerParams(ExistingCustomer|string|null $existingStripeCustomerOrEmail) : array
    {
        $params = [];

        if ($existingStripeCustomerOrEmail instanceof ExistingCustomer)
        {
            $params = $existingStripeCustomerOrEmail->getStripeParams();
        }
        elseif (is_string($existingStripeCustomerOrEmail))
        {
            if (filter_var($existingStripeCustomerOrEmail, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new ExceptionInvalidValue(
                    "existingStripeCustomerOrEmail needs to be either an ExistingCustomer object, or the customer's " .
                    "email address"
                );
            }

            $params['customer_email'] = $existingStripeCustomerOrEmail;
        }

        return $params;
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
     * @param int|null $expiresAt
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
        ?int $expiresAt,
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
        if ($expiresAt !== null) { $params['expires_at'] = $expiresAt; }

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
