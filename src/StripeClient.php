<?php

namespace Programster\Stripe;

use DateTime;
use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\CustomerShippingDetailsCollection;
use Programster\Stripe\Collections\CustomerTaxIdDataCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\LocaleCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\SubscriptionInvoiceItemCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\BillingAddressCollection;
use Programster\Stripe\Enums\CancelAtEnum;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\CustomerCreation;
use Programster\Stripe\Enums\ExceptionInvalidValue;
use Programster\Stripe\Enums\InvoiceStatus;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\PaymentBehavior;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\ReconciliationMode;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Enums\SessionMode;
use Programster\Stripe\Enums\SubmitType;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Enums\TaxExempt;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingCycleAnchorConfig;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\BillingThresholds;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\InvoiceSettings;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\PaymentSettings;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\ShippingConfig;
use Programster\Stripe\Models\StripeConnectPaymentConfig;
use Programster\Stripe\Models\StripeConnectSubscriptionConfig;
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


    public function __construct(private string $secretKey)
    {
        $this->m_underlyingStripeClient = new \Stripe\StripeClient($secretKey);
        Stripe::setApiKey($this->secretKey);
    }



    /**
     * List subscriptions - https://docs.stripe.com/api/subscriptions/list?lang=php
     * @param string|null $customerId - optionally pass the ID of a customer to filter subscriptions to those belonging
     * to a specific customer.
     * @param string|null $cursorStartingAfter - optionally provide a cursor for use in pagination. This will find
     * subscriptions after this pointer.
     * @param string|null $cursorEndingBefore - optionally provide a cursor for use in pagination. This will find
     *  subscriptions before this pointer.
     * @param string|null $priceId - optionally filter for subscriptions that contain this recurring price ID.
     * @param TimePeriod|null $created - optionally filter subscriptions created within a certain time period
     * @param TimePeriod|null $currentPeriodEnd - Only return subscriptions whose current_period_end falls within the
     * given date interval.
     * @param TimePeriod|null $currentPeriodStart - Only return subscriptions whose current_period_start falls within
     * the given date interval.
     * @param SubscriptionStatus|null $subscriptionStatus - filter subscriptions by their status.
     * @param bool|null $automaticTaxEnabled - filter subscriptions based on whether they have auto tax enabled or not.
     * @param SubscriptionCollectionMethod|null $collectionMethod - filter subscriptions based on whether they auto
     * charge or send an invoice to be paid.
     * @param string|null $testClock - optionally filter subscriptions that have the specified test clock in development.
     * @param int $limit - optionally specify the limit to the number of subscriptions in the result (pagination). This
     * package's default is 100, which is the maximum, so you can only override it to reduce the number.
     * @return \Stripe\Collection
     * @throws ApiErrorException
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
    )
    {
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($priceId !== null) { $params['price'] = $priceId; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($currentPeriodEnd !== null) { $params['current_period_end'] = $currentPeriodEnd->toArray(); }
        if ($currentPeriodStart !== null) { $params['current_period_start'] = $currentPeriodStart->toArray(); }
        if ($subscriptionStatus !== null) { $params['status'] = $subscriptionStatus->value; }
        if ($automaticTaxEnabled !== null) { $params['automatic_tax'] = $automaticTaxEnabled; }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $subscriptions = $this->m_underlyingStripeClient->subscriptions->all($params);
        return $subscriptions;
    }


    /**
     * Create a customer - https://docs.stripe.com/api/customers/create?lang=php
     *
     * @param Address|null $address - the address of the customer (required if calculating taxes)
     *
     * @param string|null $description - An arbitrary string that you can attach to a customer object. It is displayed
     * alongside the customer in the dashboard.
     *
     * @param string|null $email - Customer’s email address. It’s displayed alongside the customer in your dashboard
     * and can be useful for searching and tracking. This may be up to 512 characters.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     *
     * @param string|null $name - The customer’s full name or business name. The maximum length is 256 characters.
     *
     * @param string|null $paymentMethodId - The ID of the PaymentMethod to attach to the customer.
     *
     * @param string|null $phone - The customer’s phone number.
     *
     * @param CustomerShippingDetailsCollection|null $shippingDetails - The customer’s shipping information. Appears on
     * invoices emailed to this customer.
     *
     * @param CustomerTaxConfig|null $taxConfig
     *
     * @param int|null $balance
     *
     * @param string|null $businessName
     *
     * @param ReconciliationMode|null $cashBalanceReconciliationMode
     *
     * @param string|null $individualName
     *
     * @param string|null $invoicePrefix
     *
     * @param CustomerInvoiceSettings|null $invoiceSettings
     *
     * @param int|null $nextInvoiceSequence
     *
     * @param LocaleCollection|null $preferredLocales
     *
     * @param string|null $source
     *
     * @param TaxExempt|null $taxExempt
     *
     * @param CustomerTaxIdDataCollection|null $taxIdData
     *
     * @param string|null $testClockId
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
    ) : Customer
    {
        $params = [];

        if ($address !== null) { $params['address'] = $address; }
        if ($description !== null) { $params['description'] = $description; }
        if ($email !== null) { $params['email'] = $email; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($name !== null) { $params['name'] = $name; }
        if ($paymentMethodId !== null) { $params['payment_method'] = $paymentMethodId; }
        if ($phone !== null) { $params['phone'] = $phone; }
        if ($shippingDetails !== null) { $params['shipping_details'] = $shippingDetails->toStripeArrayForm(); }
        if ($taxConfig !== null) { $params['tax_config'] = $taxConfig; }
        if ($balance !== null) { $params['balance'] = $balance; }
        if ($businessName !== null) { $params['business_name'] = $businessName; }

        if ($cashBalanceReconciliationMode !== null)
        {
            $params['cash_balance'] = [
                'settings' => [
                    'reconciliation_mode' => $cashBalanceReconciliationMode
                ]
            ];
        }

        if ($individualName !== null) { $params['individual_name'] = $individualName; }
        if ($invoicePrefix !== null) { $params['invoice_prefix'] = $invoicePrefix; }
        if ($invoiceSettings !== null) { $params['invoice_settings'] = $invoiceSettings; }
        if ($nextInvoiceSequence !== null) { $params['next_invoice_sequence'] = $nextInvoiceSequence; }
        if ($preferredLocales !== null) { $params['preferred_locales'] = $preferredLocales->toStripeArrayForm(); }
        if ($source !== null) { $params['source'] = $source; }
        if ($taxExempt !== null) { $params['tax_exempt'] = $taxExempt->value; }
        if ($taxIdData !== null) { $params['tax_id_data'] = $taxIdData->toStripeArrayForm(); }
        if ($testClockId !== null) { $params['test_clock'] = $testClockId; }

        return $this->m_underlyingStripeClient->customers->create($params);
    }


    /**
     * List all subscription items
     * Returns a list of your subscription items for a given subscription.
     * @param string $subscriptionId - the ID of the subscription to get the items for.
     * @param string|null $cursorStartingAfter
     * @param string|null $cursorEndingBefore
     * @param string|null $testClock
     * @param int $limit
     * @return Collection<SubscriptionItem>
     * @throws ApiErrorException
     */
    public function listSubscriptionItems(
        string $subscriptionId,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?string $testClock = null,
        int $limit = 100,
    ): Collection
    {
        $params = ['limit' => $limit];

        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        return $this->m_underlyingStripeClient->subscriptionItems->all($params);
    }


    /**
     * Updates the plan or quantity of an item on a current subscription.
     * https://docs.stripe.com/api/subscription_items/update
     *
     * @param string $subscriptionItemId - the ID of the subscription we wish to update.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     *
     * @param PaymentBehavior|null $paymentBehavior
     *
     * @param string|PriceDataForSubscriptionCheckoutSession|null $priceDataOrPriceId - either the ID of a price object to use for
     * the subscription item, or the object form that fully provides all of the details. Leave as null to not change.
     *
     * @param ProrationBehavior|null $prorationBehavior - Determines how to handle prorations when the billing cycle
     * changes (e.g., when switching plans, resetting billing_cycle_anchor=now, or starting a trial), or if an item’s
     * quantity changes. The default value is create_prorations.
     *
     * @param int|null $quantity - The quantity you’d like to apply to the subscription item you’re creating.
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period. Pass an empty string to remove previously-defined thresholds.
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the
     * subscription item.
     *
     * @param bool|null $offSession - Indicates if a customer is on or off-session while an invoice payment is
     * attempted. Defaults to false (on-session).
     *
     * @param int|null $prorationDate - If set, the proration will be calculated as though the subscription was updated
     * at the given time. This can be used to apply the same proration that was previewed with the upcoming invoice
     * endpoint.
     *
     * @param StringCollection|null $taxRateIds - A list of Tax Rate IDs. These Tax Rates will override the
     * default_tax_rates on the Subscription. When updating, pass an empty string to remove previously-defined tax rates.
     *
     * @return void
     * @throws ApiErrorException
     */
    public function updateSubscriptionItem(
        string                                              $subscriptionItemId,
        ?Metadata                                           $metadata = null,
        ?PaymentBehavior                                    $paymentBehavior = null,
        null|string|PriceDataForSubscriptionCheckoutSession $priceDataOrPriceId = null,
        ?ProrationBehavior                                  $prorationBehavior = null,
        ?int                                                $quantity = null,
        ?BillingThresholds                                  $billingThresholds = null,
        ?DiscountCollection                                 $discounts = null,
        ?bool                                               $offSession = null,
        ?int                                                $prorationDate = null,
        ?StringCollection                                   $taxRateIds = null,
    ) : void
    {
        $params = [];

        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($paymentBehavior !== null) { $params['payment_behavior'] = $paymentBehavior->value; }

        if ($priceDataOrPriceId !== null)
        {
            if (is_string($priceDataOrPriceId))
            {
                $params['price'] = $priceDataOrPriceId;
            }
            else
            {
                $params['price_data'] = $priceDataOrPriceId->toArray();
            }
        }

        if ($prorationBehavior !== null) { $params['proration_behavior'] = $prorationBehavior->value; }
        if ($quantity !== null) { $params['quantity'] = $quantity; }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }
        if ($discounts !== null) { $params['discounts'] = $discounts->toStripeArrayForm(); }
        if ($offSession !== null) { $params['off_session'] = $offSession; }
        if ($prorationDate !== null) { $params['proration_date'] = $prorationDate; }
        if ($taxRateIds !== null) {$params['tax_rates'] = $taxRateIds->toArray(); }

        $this->m_underlyingStripeClient->subscriptionItems->update($subscriptionItemId, $params);
    }


    /**
     * Cancel a subscription
     *
     * @param string $subscriptionId - the ID of the subscription to delete.
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
     * @param string|null $customerId - optionally specify the (Stripe) ID of a customer to retrieve payment intents
     * specific to them.
     * @param string|null $customerAccountId - optionally specify the Stripe account ID to retrieve payments related
     * to them.
     * @param TimePeriod|null $created
     * @param string|null $cursorStartingAfter
     * @param string|null $cursorEndingBefore
     * @param string|null $testClock
     * @param int $limit - A limit on the number of objects to be returned. Limit can range between 1 and 100.
     * @return Collection
     * @throws ApiErrorException
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
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccountId !== null) { $params['customer_account'] = $customerAccountId; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $paymentIntents = $this->m_underlyingStripeClient->paymentIntents->all($params);
        return $paymentIntents;
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
     * @param string|null $cursorStartingAfter
     *
     * @param string|null $cursorEndingBefore
     *
     * @param TimePeriod|null $created
     *
     * @param bool $expandPayments - if set to true (default), then this will include details of all the payments made
     * against an invoice. You need this if you wish to look up the payment intents that relate to an invoice (which
     * is generally the only way to work out which payment intents were against a subscription.
     * E.g. subscription -> invoices -> payment intents)
     *
     * @param string|null $testClock
     *
     * @param int $limit
     *
     * @return Collection
     *
     * @throws ApiErrorException
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
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($customerAccount !== null) { $params['customer_account'] = $customerId; }
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


    public function listCustomers(
        ?string $email = null,
        ?string $cursorStartingAfter = null,
        ?string $cursorEndingBefore = null,
        ?TimePeriod $created = null,
        ?string $testClock = null,
        int $limit = 100,
    ) : Collection
    {
        $params = ['limit' => $limit];

        if ($email !== null) { $params['email'] = $email; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($testClock !== null) { $params['test_clock'] = $testClock; }

        $customers = $this->m_underlyingStripeClient->customers->all($params);
        return $customers;
    }


    /**
     * Returns a list of all refunds you created. We return the refunds in sorted order, with the most recent refunds
     * appearing first. The 10 most recent refunds are always available by default on the Charge object.
     * https://docs.stripe.com/api/refunds/list
     * @param string|null $chargeId - optionally provide the ID of a charge, in order to only get refunds for that
     * charge.
     * @param string|null $paymentIntentId - optionally provide the ID of a charge, in order to only get refunds for
     * that payment intent.
     * @param string|null $cursorStartingAfter
     * @param string|null $cursorEndingBefore
     * @param TimePeriod|null $created
     * @param int $limit
     * @return Collection
     * @throws ApiErrorException
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
     * @param string|null $customerId
     * @param string|null $cursorStartingAfter
     * @param string|null $cursorEndingBefore
     * @param TimePeriod|null $created
     * @param string|null $paymentIntent
     * @param string|null $transferGroup
     * @param int $limit
     * @return Collection
     * @throws ApiErrorException
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
        $params = ['limit' => $limit];

        if ($customerId !== null) { $params['customer'] = $customerId; }
        if ($paymentIntent !== null) { $params['payment_intent'] = $paymentIntent; }
        if ($cursorStartingAfter !== null) { $params['starting_after'] = $cursorStartingAfter; }
        if ($cursorEndingBefore !== null) { $params['ending_before'] = $cursorEndingBefore; }
        if ($created !== null) { $params['created'] = $created->toArray(); }
        if ($transferGroup !== null) { $params['transfer_group'] = $transferGroup; }

        $customers = $this->m_underlyingStripeClient->charges->all($params);
        return $customers;
    }


    /**
     * Create a checkout session object for a one-time payment, rather than a subscription or setting up a customer
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig
     *
     * @param SinglePaymentLineItemsCollection $items - A collection of SinglePaymentLineItem objects that are being
     * purchased.
     *
     * @param PaymentIntentData|null $paymentIntentData - A subset of parameters to be passed to PaymentIntent creation
     * for Checkout Sessions in payment mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data
     *
     * @param string|null $customerEmail - optionally provide the customer's email address. If provided, this value
     * will be used when the Customer object is created. If not provided, customers will be asked to enter their email
     * address. Use this parameter to prefill customer data if you already have an email on file.
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
     * @param Locale|null $locale optionally specify the locale/language the stripe payment page should use. If null
     * (default), then stripe will use whatever the user's browser is set to.
     *
     * @param Currency|null $currency - optionally specify the currency. This is useful if you have products listed with
     * multiple possible currencies.
     *
     * @param ExistingCustomer|string|null $existingStripeCustomerOrEmail - if you have an existing stripe customer ID,
     * create one of these objects with it, and optionally a configuration for whether that customer is updatable.
     * Alternatively, if you know the customer's email address, then provide that as a string. This parameter is a
     * combination of the existing_customer and customer_email parameters, as only one of these can be set.
     * WARNING - do not simply provide the string ID of the customer as this will not work
     *
     * @param CustomerCreation|null $customerCreation - specify if a customer should be created in Stripe or not for this
     * payment. The value and docs talk about "if required", which is the case for a subscription, but this method is
     * for a payment, so creating a customer should not be required.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer_creation
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice for the one-time payment
     * should be created. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param StripeConnectPaymentConfig|null $stripeConnectConfig - a configuration for configuring the various settings
     * to do with if another account is involved, such as how much of a percentage they should get, and whether
     * the payment is on their behalf or not etc.
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
     * feature. Learn more about collecting phone numbers with Checkout.
     * https://docs.stripe.com/payments/checkout/phone-numbers
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-phone_number_collection
     *
     * @param ConsentConfig|null $consentConfig
     *
     * @param CustomFieldCollection|null $customFields
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
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param Metadata|null $metadata
     *
     * @return Session - the created checkout session.
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
    ) : Session
    {
        $params = $flowConfig->getStripeParams();

        $params['mode'] = SessionMode::PAYMENT->value;
        $params['line_items'] = $items->toStripeArrayForm();

        if ($customerCreation !== null)
        {
            $params['customer_creation'] = $customerCreation->value;
        }

        if ($paymentIntentData !== null)
        {
            $params['payment_intent_data'] = $paymentIntentData->toArray();
        }

        if ($invoiceCreation !== null)
        {
            $params['invoice_creation'] = $invoiceCreation->toArray();
        }

        $params['submit_type'] = $submitType->value;

        if (is_object($existingStripeCustomerOrEmail) && get_class($existingStripeCustomerOrEmail) === ExistingCustomer::class)
        {
            $params = array_merge($params, $existingStripeCustomerOrEmail->getStripeParams());
        }
        elseif (is_string($existingStripeCustomerOrEmail))
        {
            if (filter_var($existingStripeCustomerOrEmail, FILTER_VALIDATE_EMAIL) === false )
            {
                throw new ExceptionInvalidValue(
                    "existingStripeCustomerOrEmail needs to be either an ExistingCustomer object, or the customer's email address"
                );
            }

            $params['customer_email'] = $existingStripeCustomerOrEmail;
        }

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }
        if ($clientReferenceId !== null) { $params['client_reference_id'] = $clientReferenceId; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($adaptivePricing !== null) { $params['adaptive_pricing'] = $adaptivePricing; }
        if ($afterExpiration !== null) { $params['after_expiration'] = $afterExpiration->toArray(); }
        if ($allowPromoCodes !== null) { $params['allow_promotion_codes'] = $allowPromoCodes; }
        if ($billingAddressCollection !== null) { $params['billing_address_collection'] = $billingAddressCollection->value; }
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

        if ($discount !== null)
        {
            // this looks strange, because Stripe needs it to be an array list, but only accepts one. It looks like they
            // may allow specifying multiple in the future, but not now?
            $params['discounts'] = [$discount->toArray()];
        }

        if ($expiresAt !== null) { $params['expires_at'] = $expiresAt; }

        if ($enablePhoneNumberCollection !== null)
        {
            $params['phone_number_collection'] = ['enabled' => $enablePhoneNumberCollection];
        }

        if ($locale !== null) { $params['locale'] = $locale->value; }
        if ($paymentMethodConfigurationId !== null) { $params['payment_method_configuration'] = $paymentMethodConfigurationId; }
        if ($allowedPaymentMethodTypes !== null ) { $params['payment_method_types'] = $allowedPaymentMethodTypes->toStripeArrayForm(); }
        if ($savedPaymentMethodOptions !== null) { $params['saved_payment_method_options'] = $savedPaymentMethodOptions->toArray(); }

        if ($customFields !== null)
        {
            $customFieldsConverted = [];

            foreach ($customFields as $customField)
            {
                /* @var $customField \Programster\Stripe\Models\CustomField */
                $customFieldsConverted[] = $customField->toArray();
            }

            $params['custom_fields'] = $customFieldsConverted;
        }

        return Session::create($params);
    }


    /**
     * Create a checkout session object for a subscription payment, rather than a one-time payment, or setting up a
     * customer. This can contain items that are paid for once, but should be used when there is at least one item
     * that has a subscription payment. Please note that customerCreation is deliberately not included in this method
     * as Stripe does not allow it, as it is not applicable, like it is for createCheckoutSessionForSinglePayment
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig
     *
     * @param SubscriptionCheckoutLineItemCollection $items
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
     * @param Locale|null $locale optionally specify the locale/language the stripe payment page should use. If null
     * (default), then stripe will use whatever the user's browser is set to.
     *
     * @param Currency|null $currency - optionally specify the currency. This is useful if you have products listed with
     * multiple possible currencies.
     *
     * @param ExistingCustomer|string|null $existingStripeCustomerOrEmail - if you have an existing stripe customer ID,
     * create one of these objects with it, and optionally a configuration for whether that customer is updatable.
     * Alternatively, if you know the customer's email address, then provide that as a string. This parameter is a
     * combination of the existing_customer and customer_email parameters, as only one of these can be set.
     * WARNING - do not simply provide the string ID of the customer as this will not work
     *
     * @param InvoiceCreation|null $invoiceCreation - specify whether a post-purchase invoice for the one-time payment
     * should be created. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param CustomTextOptions|null $customTextOptions - optionally provide custom text messages at various points
     * during the checkout flow.
     *
     * @param AutomaticTax|null $automaticTax
     *
     * @param int|null $expiresAt - the unix timestamp when the checkout session should expire. Defaults to 24 hours
     * from creation.
     *
     * @param AfterExpiration|null $afterExpiration
     *
     * @param bool|null $adaptivePricing - if not provided, defaults to what is set on your Stripe dashboard.
     *
     * @param BillingAddressCollection $billingAddressCollection
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
     * feature. Learn more about collecting phone numbers with Checkout.
     * https://docs.stripe.com/payments/checkout/phone-numbers
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-phone_number_collection
     *
     * @param ConsentConfig|null $consentConfig - Configure fields for the Checkout Session to gather active consent
     * from customers. https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-consent_collection
     *
     * @param CustomFieldCollection|null $customFields
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
     * @param Metadata|null $metadata
     *
     * @return Session - the created checkout session.
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
    ) : Session
    {
        $params = $flowConfig->getStripeParams();

        $params['mode'] = SessionMode::SUBSCRIPTION->value;
        $params['line_items'] = $items->toStripeArrayForm();

        if (is_object($existingStripeCustomerOrEmail) && get_class($existingStripeCustomerOrEmail) === ExistingCustomer::class)
        {
            $params = array_merge($params, $existingStripeCustomerOrEmail->getStripeParams());
        }
        elseif (is_string($existingStripeCustomerOrEmail))
        {
            if (filter_var($existingStripeCustomerOrEmail, FILTER_VALIDATE_EMAIL) === false )
            {
                throw new ExceptionInvalidValue(
                    "existingStripeCustomerOrEmail needs to be either an ExistingCustomer object, or the customer's email address"
                );
            }

            $params['customer_email'] = $existingStripeCustomerOrEmail;
        }

        if ($subscriptionData         !== null) { $params['subscription_data'] = $subscriptionData->toArray(); }
        if ($invoiceCreation          !== null) { $params['invoice_creation'] = $invoiceCreation->toArray(); }
        if ($automaticTax             !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }
        if ($clientReferenceId        !== null) { $params['client_reference_id'] = $clientReferenceId; }
        if ($metadata                 !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($adaptivePricing          !== null) { $params['adaptive_pricing'] = $adaptivePricing; }
        if ($afterExpiration          !== null) { $params['after_expiration'] = $afterExpiration->toArray(); }
        if ($allowPromoCodes          !== null) { $params['allow_promotion_codes'] = $allowPromoCodes; }
        if ($billingAddressCollection !== null) { $params['billing_address_collection'] = $billingAddressCollection->value; }
        if ($consentConfig            !== null) { $params['consent_collection'] = $consentConfig->toArray(); }
        if ($currency                 !== null) { $params['currency'] = $currency->value; }
        if ($customFields             !== null) { $params['custom_fields'] = $customFields->toStripeArrayForm(); }
        if ($customTextOptions        !== null) { $params['custom_text'] = $customTextOptions->toArray(); }
        if ($taxIdCollection          !== null) { $params['tax_id_collection'] = $taxIdCollection->toArray(); }

        if ($shippingAddressCollection !== null)
        {
            $params['shipping_address_collection'] = [
                'allowed_countries' => $shippingAddressCollection->toStripeArrayForm()
            ];
        }

        if ($discount !== null)
        {
            // this looks strange, because Stripe needs it to be an array list, but only accepts one. It looks like they
            // may allow specifying multiple in the future, but not now?
            $params['discounts'] = [$discount->toArray()];
        }

        if ($expiresAt !== null) { $params['expires_at'] = $expiresAt; }

        if ($enablePhoneNumberCollection !== null)
        {
            $params['phone_number_collection'] = ['enabled' => $enablePhoneNumberCollection];
        }

        if ($locale !== null) { $params['locale'] = $locale->value; }
        if ($paymentMethodConfigurationId !== null) { $params['payment_method_configuration'] = $paymentMethodConfigurationId; }
        if ($allowedPaymentMethodTypes !== null ) { $params['payment_method_types'] = $allowedPaymentMethodTypes->toStripeArrayForm(); }
        if ($savedPaymentMethodOptions !== null) { $params['saved_payment_method_options'] = $savedPaymentMethodOptions->toArray(); }

        if ($customFields !== null)
        {
            $customFieldsConverted = [];

            foreach ($customFields as $customField)
            {
                /* @var $customField \Programster\Stripe\Models\CustomField */
                $customFieldsConverted[] = $customField->toArray();
            }

            $params['custom_fields'] = $customFieldsConverted;
        }

        return Session::create($params);
    }


    /**
     * Retrieve a Stripe checkout session by ID.
     * @param string $checkoutSessionId - the ID of the session to retrieve.
     * @return Session - The Stripe checkout session.
     * @throws ApiErrorException
     */
    public function retrieveCheckoutSession(string $checkoutSessionId) : Session
    {
        return Session::retrieve($checkoutSessionId);
    }


    /**
     * Create a subscription for the user. Most of the time you will want to use the
     * createCheckoutSessionForSubscription method instead, but in certain circumstances, you may need
     * to just create the subscription and have it send an invoice to the customer instead.
     *
     * @param ExistingCustomer|string $existingCustomerOrCustomerAccount
     *
     * @param SubscriptionLineItemCollection $items - items that should be charged in this subscription.
     *
     * @param Currency $currency
     *
     * @param string|null $description - The subscription’s description, meant to be displayable to the customer. Use
     * this field to optionally store an explanation of the subscription for rendering in Stripe surfaces and certain
     * local payment methods UIs.
     * The maximum length is 500 characters.
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
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-metadata
     *
     * @param PaymentBehavior|null $paymentBehavior - Only applies to subscriptions with collection method set to
     * charge_automatically.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_behavior
     *
     * @param SubscriptionInvoiceItemCollection|null $addInvoiceItems - A list of prices and quantities that will
     * generate invoice items appended to the next invoice for this subscription. You may pass up to 20 items.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items
     *
     * @param int|null $applicationFeePercent - A non-negative decimal between 0 and 100, with at most two decimal
     * places. This represents the percentage of the subscription invoice total that will be transferred to the
     * application owner’s Stripe account. The request must be made by a platform account on a connected account in
     * order to set an application fee percentage. For more information, see the application fees documentation.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-application_fee_percent
     *
     * @param int|null $backdateStartDate - A past timestamp to backdate the subscription’s start date to. If set, the
     * first invoice will contain line items for the timespan between the start date and the current time. Can be
     * combined with trials and the billing cycle anchor.
     *
     * @param int|BillingCycleAnchorConfig|null $billingCycleAnchorTimestampOrConfig - Mutually exclusive with
     * billing_cycle_anchor and only valid with monthly and yearly price intervals. When provided, the
     * billing_cycle_anchor is set to the next occurrence of the day_of_month at the hour, minute, and second UTC.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor_config
     *
     * @param BillingMode|null $billingMode - Controls how prorations and invoices for subscriptions are calculated and
     * orchestrated.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_mode
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period. When updating, pass an empty string to remove previously-defined
     * thresholds.
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
     * default_payment_method will take precedence. If neither are set, invoices will use the customer’s
     * invoice_settings.default_payment_method or default_source.
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
     * @param string|null $onBehalfOf - The account on behalf of which to charge, for each of the subscription’s invoices.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-on_behalf_of
     *
     * @param PaymentSettings|null $paymentSettings - Payment settings to pass to invoices created by the subscription.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-payment_settings
     *
     * @param RecurringConfig|null $pendingInvoiceItemInterval
     *
     * @param ProrationBehavior|null $prorationBehavior
     *
     * @param TransferData|null $transferData
     *
     * @param TrialConfig|null $trialConfig
     *
     * @return Subscription
     *
     * @throws ApiErrorException
     */
    public function createSubscription(
        ExistingCustomer|string             $existingCustomerOrCustomerAccount,
        SubscriptionLineItemCollection      $items,
        Currency                            $currency,
        ?string                             $description = null,
        ?AutomaticTax                       $automaticTax = null,
        ?string                             $defaultPaymentMethodId = null,
        ?Metadata                           $metadata = null,
        ?PaymentBehavior                    $paymentBehavior = null, // only applies if collection_method=charge_automatically
        ?SubscriptionInvoiceItemCollection  $addInvoiceItems = null,
        ?int                                $applicationFeePercent = null,
        ?int                                $backdateStartDate = null, // backdate the start of the subcription to this point in time. May be the most optimal way to charge full amount for year.
        null|int|BillingCycleAnchorConfig   $billingCycleAnchorTimestampOrConfig = null,
        ?BillingMode                        $billingMode = null,
        BillingThresholds                   $billingThresholds = null,
        null|int|CancelAtEnum|bool          $cancelAt = null,
        ?SubscriptionCollectionMethod       $collectionMethod = null,
        ?int                                $daysUntilDue = null,
        ?string                             $defaultSource = null,
        ?StringCollection                   $defaultTaxRates = null,
        ?DiscountCollection                 $discounts = null,
        InvoiceSettings                     $invoiceSettings = null,
        ?bool                               $offSession = null,
        ?string                             $onBehalfOf = null,
        ?PaymentSettings                    $paymentSettings = null,
        ?RecurringConfig                    $pendingInvoiceItemInterval = null,
        ?ProrationBehavior                  $prorationBehavior = null,
        ?TransferData                       $transferData = null,
        ?TrialConfig                        $trialConfig = null,
    ) : Subscription
    {
        $params = ['items' => $items->toStripeArrayForm()];

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax; }
        if ($currency !== null)     { $params['currency'] = $currency->value; }

        if (is_string($existingCustomerOrCustomerAccount))
        {
            $params['customer_account'] = $existingCustomerOrCustomerAccount;
        }
        else
        {
            $params['customer'] = $existingCustomerOrCustomerAccount->getCustomerId();
        }

        if ($defaultPaymentMethodId !== null) { $params['default_payment_method_id'] = $defaultPaymentMethodId; }
        if ($description !== null)            { $params['description'] = $description; }
        if ($metadata !== null)               { $params['metadata'] = $metadata; }
        if ($paymentBehavior !== null)        { $params['payment_behavior'] = $paymentBehavior->value; }
        if ($addInvoiceItems !== null)        { $params['add_invoice_items'] = $addInvoiceItems->toStripeArrayForm(); }
        if ($applicationFeePercent !== null)  { $params['application_fee_percent'] = $applicationFeePercent; }
        if ($backdateStartDate !== null)      { $params['backdate_start_date'] = $backdateStartDate; }

        if ($billingCycleAnchorTimestampOrConfig !== null)
        {
            if (is_int($billingCycleAnchorTimestampOrConfig))
            {
                $params['billing_cycle_anchor'] = $billingCycleAnchorTimestampOrConfig;
            }
            else
            {
                $params['billing_cycle_anchor_config'] = $billingCycleAnchorTimestampOrConfig->toArray();
            }
        }

        if ($billingMode !== null)       { $params['billing_mode']       = $billingMode->toArray(); }
        if ($billingThresholds !== null) { $params['billing_thresholds'] = $billingThresholds->toStripeForm(); }

        if ($cancelAt !== null)
        {
            if (is_int($cancelAt))
            {
                $params['cancel_at'] = $cancelAt;
            }
            elseif (is_bool($cancelAt))
            {
                $params['cancel_at_period_end'] = $cancelAt;
            }
            else
            {
                $params['cancel_at'] = $cancelAt->value;
            }
        }

        if ($collectionMethod !== null) { $params['collection_method'] = $collectionMethod->value; }
        if ($daysUntilDue !== null)     { $params['days_until_due'] = $daysUntilDue; }
        if ($defaultSource !== null)    { $params['default_source'] = $defaultSource; }
        if ($defaultTaxRates !== null)  { $params['default_tax_rates'] = $defaultTaxRates->toArray(); }
        if ($discounts !== null)        { $params['discounts'] = $discounts->toStripeArrayForm(); }
        if ($invoiceSettings !== null)  { $params['invoice_settings'] = $invoiceSettings->toArray(); }
        if ($offSession !== null)       { $params['off_session'] = $offSession; }
        if ($onBehalfOf !== null)       { $params['on_behalf_of'] = $onBehalfOf; }
        if ($paymentSettings !== null)  { $params['payment_settings'] = $paymentSettings->toArray(); }

        if ($pendingInvoiceItemInterval !== null)
        {
            $params['pending_invoice_item_interval'] = $pendingInvoiceItemInterval->toArray();
        }

        if ($prorationBehavior !== null) { $params['proration_behavior'] = $prorationBehavior->value; }
        if ($transferData !== null)      { $params['transfer_data'] = $transferData->toArray(); }
        if ($trialConfig !== null)       { $params = array_merge($params, $trialConfig->getStripeParams()); }

        return $this->m_underlyingStripeClient->subscriptions->create($params);
    }
}
