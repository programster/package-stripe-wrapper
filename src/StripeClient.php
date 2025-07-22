<?php

namespace Programster\Stripe;

use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\collections\SubscriptionLineItemCollection;
use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Enums\BillingAddressCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\CustomerCreation;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\SessionMode;
use Programster\Stripe\Enums\SubmitType;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\StripeConnectPaymentConfig;
use Programster\Stripe\Models\StripeConnectSubscriptionConfig;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\TaxIdCollection;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

readonly class StripeClient
{
    public function __construct(private string $secretKey)
    {
        Stripe::setApiKey($this->secretKey);
    }


    /**
     * Create a checkout session object for a one-time payment, rather than a subscription or setting up a customer
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig
     *
     * @param SubscriptionLineItemCollection $items
     *
     * @param PaymentIntentData|null $paymentIntentData
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
     * @param ExistingCustomer|null $existingStripeCustomer - if you have an existing stripe customer ID, create one
     * of these objects with it, and optionally a configuration for whether that customer is updatable.
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
     * @param CountryCodeCollection|null $allowedShippingLocations - optionally specify a list of countries to allow
     * Stripe to collect a shipping address for, for shipping a product as part of this transaction. E.g. if you are
     * selling a product to be shipped, and you only ship to the UK, you need to fill in a collection with one element
     * identifying the UK. If you don't provide this, then Stripe won't collect a shipping address!
     *
     * @param TaxIdCollection|null $taxIdCollection - Details on the state of tax ID collection for the session.
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
     * @param PaymentIntentData|null $paymentIntent
     *
     * @param string|null $paymentMethodConfigurationId - optionally provide the ID of the payment method configuration
     * to use with this Checkout session. https://docs.stripe.com/api/payment_methods
     *
     * @param PaymentMethodTypeCollection|null $allowedPaymentMethodTypes - optionally provide a list of the payment
     * method types you would allow the user to pay through on checkout. E.g. ['card'
     *
     * @param SavedPaymentMethodOptions|null $savedPaymentMethodOptions - Controls saved payment method settings for
     * the session.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-saved_payment_method_options
     *
     * @param SubmitType $submitType - Describes the type of transaction being performed by Checkout in order to
     * customize relevant text on the page, such as the submit button. submit_type can only be specified on Checkout
     * Sessions in payment mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-submit_type
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
        ?string                          $customerEmail = null,
        ?string                          $clientReferenceId = null,
        ?Discount                        $discount = null,
        ?bool                            $allowPromoCodes = null,
        ?Locale                          $locale = null,
        ?Currency                        $currency = null,
        ?ExistingCustomer                $existingStripeCustomer = null,
        ?CustomerCreation                $customerCreation = null,
        ?InvoiceCreation                 $invoiceCreation = null,
        ?CustomTextOptions               $customTextOptions = null,
        ?StripeConnectPaymentConfig      $stripeConnectConfig = null,
        ?AutomaticTax                    $automaticTax = null,
        ?int                             $expiresAt = null,
        ?AfterExpiration                 $afterExpiration = null,
        ?bool                            $adaptivePricing = null,
        BillingAddressCollection         $billingAddressCollection = BillingAddressCollection::AUTO,
        ?CountryCodeCollection           $allowedShippingLocations = null,
        ?TaxIdCollection                 $taxIdCollection = null,
        ?bool                            $enablePhoneNumberCollection = null,
        ?ConsentConfig                   $consentConfig = null,
        ?CustomFieldCollection           $customFields = null,
        PaymentIntentData                $paymentIntent = null,
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

        if ($stripeConnectConfig !== null)
        {
            if ($paymentIntentData !== null)
            {
                $paymentIntentDataArray = $paymentIntentData->toArray();
                $paymentIntentDataArray = array_merge($paymentIntentDataArray, $stripeConnectConfig->getParams());
            }
            else
            {
                $paymentIntentDataArray = $stripeConnectConfig->getParams();;
            }

            $params['subscription_data'] = $paymentIntentDataArray;
        }

        if ($invoiceCreation !== null)
        {
            $invoiceCreationArray = $invoiceCreation->toArray();

            if ($stripeConnectConfig !== null)
            {
                $invoiceCreationArray['invoice_data']['issuer'] = $stripeConnectConfig->getIssuerObj();
            }

            $params['invoice_creation'] = $invoiceCreationArray;
        }

        $params['submit_type'] = $submitType->value;

        if ($existingStripeCustomer !== null)
        {
            $params = array_merge($params, $existingStripeCustomer->getStripeParams() );
        }

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }
        if ($clientReferenceId !== null) { $params['client_reference_id'] = $clientReferenceId; }
        if ($customerEmail !== null) { $params['customer_email'] = $customerEmail; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($adaptivePricing !== null) { $params['adaptive_pricing'] = $adaptivePricing; }
        if ($afterExpiration !== null) { $params['after_expiration'] = $afterExpiration->toArray(); }
        if ($allowPromoCodes !== null) { $params['allow_promotion_codes'] = $allowPromoCodes; }
        if ($billingAddressCollection !== null) { $params['billing_address_collection'] = $billingAddressCollection->value; }
        if ($consentConfig !== null) { $params['consent_config'] = $consentConfig->toArray(); }
        if ($currency !== null) { $params['currency'] = $currency->value; }
        if ($customFields !== null) { $params['custom_fields'] = $customFields->toStripeArrayForm(); }
        if ($customTextOptions !== null) { $params['custom_text'] = $customTextOptions->toArray(); }
        if ($taxIdCollection !== null) { $params['tax_id_collection'] = $taxIdCollection->toArray(); }

        if ($allowedShippingLocations !== null)
        {
            $params['shipping_address_collection'] = [
                'allowed_countries' => $allowedShippingLocations->toStripeArrayForm()
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
        if ($paymentIntent !== null) { $params['payment_intent'] = $paymentIntent->toArray(); }
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
     * that has a subscription payment.
     * https://docs.stripe.com/api/checkout/sessions/create
     *
     * @param FlowConfig $flowConfig
     *
     * @param SubscriptionLineItemCollection $items
     *
     * @param SubscriptionData|null $subscriptionData - A subset of parameters to be passed to subscription creation
     * for Checkout Sessions in subscription mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data
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
     * @param ExistingCustomer|null $existingStripeCustomer - if you have an existing stripe customer ID, create one
     * of these objects with it, and optionally a configuration for whether that customer is updatable.
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
     * @param StripeConnectSubscriptionConfig|null $stripeConnectConfig - a configuration for configuring the various settings
     * to do with if another account is involved, such as how much of a percentage they should get, and whether
     * the payment is on their behalf or not etc.
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
     * @param CountryCodeCollection|null $allowedShippingLocations - optionally specify a list of countries to allow
     * Stripe to collect a shipping address for, for shipping a product as part of this transaction. E.g. if you are
     * selling a product to be shipped, and you only ship to the UK, you need to fill in a collection with one element
     * identifying the UK. If you don't provide this, then Stripe won't collect a shipping address!
     *
     * @param TaxIdCollection|null $taxIdCollection - Details on the state of tax ID collection for the session.
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
     * method types you would allow the user to pay through on checkout. E.g. ['card'
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
    public function createheckoutSessionForSubscription(
        FlowConfig                      $flowConfig,
        SubscriptionLineItemCollection   $items,
        ?SubscriptionData                $subscriptionData = null,
        ?string                          $customerEmail = null,
        ?string                          $clientReferenceId = null,
        ?Discount                        $discount = null,
        ?bool                            $allowPromoCodes = null,
        ?Locale                          $locale = null,
        ?Currency                        $currency = null,
        ?ExistingCustomer                $existingStripeCustomer = null,
        ?CustomerCreation                $customerCreation = null,
        ?InvoiceCreation                 $invoiceCreation = null,
        ?CustomTextOptions               $customTextOptions = null,
        ?StripeConnectSubscriptionConfig $stripeConnectConfig = null,
        ?AutomaticTax                    $automaticTax = null,
        ?int                             $expiresAt = null,
        ?AfterExpiration                 $afterExpiration = null,
        ?bool                            $adaptivePricing = null,
        BillingAddressCollection         $billingAddressCollection = BillingAddressCollection::AUTO,
        ?CountryCodeCollection           $allowedShippingLocations = null,
        ?TaxIdCollection                 $taxIdCollection = null,
        ?bool                            $enablePhoneNumberCollection = null,
        ?ConsentConfig                   $consentConfig = null,
        ?CustomFieldCollection           $customFields = null,
        ?string                          $paymentMethodConfigurationId = null,
        ?PaymentMethodTypeCollection     $allowedPaymentMethodTypes = null,
        ?SavedPaymentMethodOptions       $savedPaymentMethodOptions = null,
        ?Metadata                        $metadata = null,
    )
    {
        $params = $flowConfig->getStripeParams();

        $params['mode'] = SessionMode::SUBSCRIPTION->value;
        $params['line_items'] = $items->toStripeArrayForm();
        $params['customer_creation'] = $customerCreation->value;

        if ($stripeConnectConfig !== null)
        {
            if ($subscriptionData !== null)
            {
                $subscriptionDataArray = $subscriptionData->toArray();
                $subscriptionDataArray = array_merge($subscriptionDataArray, $stripeConnectConfig->getParams());
            }
            else
            {
                $subscriptionDataArray = $stripeConnectConfig->getParams();
            }

            $params['subscription_data'] = $subscriptionDataArray;
        }

        if ($invoiceCreation !== null)
        {
            $invoiceCreationArray = $invoiceCreation->toArray();

            if ($stripeConnectConfig !== null)
            {
                $invoiceCreationArray['invoice_data']['issuer'] = $stripeConnectConfig->getIssuerObj();
            }

            $params['invoice_creation'] = $invoiceCreationArray;
        }

        if ($existingStripeCustomer !== null)
        {
            $params = array_merge($params, $existingStripeCustomer->getStripeParams() );
        }

        if ($automaticTax !== null) { $params['automatic_tax'] = $automaticTax->toArray(); }
        if ($clientReferenceId !== null) { $params['client_reference_id'] = $clientReferenceId; }
        if ($customerEmail !== null) { $params['customer_email'] = $customerEmail; }
        if ($metadata !== null) { $params['metadata'] = $metadata->toStripeArrayForm(); }
        if ($adaptivePricing !== null) { $params['adaptive_pricing'] = $adaptivePricing; }
        if ($afterExpiration !== null) { $params['after_expiration'] = $afterExpiration->toArray(); }
        if ($allowPromoCodes !== null) { $params['allow_promotion_codes'] = $allowPromoCodes; }
        if ($billingAddressCollection !== null) { $params['billing_address_collection'] = $billingAddressCollection->value; }
        if ($consentConfig !== null) { $params['consent_config'] = $consentConfig->toArray(); }
        if ($currency !== null) { $params['currency'] = $currency->value; }
        if ($customFields !== null) { $params['custom_fields'] = $customFields->toStripeArrayForm(); }
        if ($customTextOptions !== null) { $params['custom_text'] = $customTextOptions->toArray(); }
        if ($taxIdCollection !== null) { $params['tax_id_collection'] = $taxIdCollection->toArray(); }

        if ($allowedShippingLocations !== null)
        {
            $params['shipping_address_collection'] = [
                'allowed_countries' => $allowedShippingLocations->toStripeArrayForm()
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

    public function createCheckoutSessionForSetup()
    {

    }
}
