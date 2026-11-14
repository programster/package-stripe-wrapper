<?php

/*
 * A checkout session, as Stripe sent it. Many of its fields depend on the session's mode and state, e.g. its url is
 * only set for a hosted session that's still open, so they're null when they don't apply.
 * https://docs.stripe.com/api/checkout/sessions/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\CheckoutSessionPaymentStatus;
use Programster\Stripe\Enums\CheckoutSessionStatus;
use Programster\Stripe\Enums\SessionMode;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class CheckoutSession extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private \DateTimeImmutable $m_expiresAt;
    private string $m_mode;
    private ?string $m_status;
    private string $m_paymentStatus;
    private ?string $m_uiMode;
    private ?string $m_url;
    private ?string $m_clientSecret;
    private ?string $m_successUrl;
    private ?string $m_cancelUrl;
    private ?string $m_returnUrl;
    private ?string $m_customerId;
    private ?string $m_customerEmail;
    private ?CustomerDetails $m_customerDetails;
    private ?Money $m_amountSubtotal;
    private ?Money $m_amountTotal;
    private ?string $m_currency;
    private ?string $m_clientReferenceId;
    private Metadata $m_metadata;
    private ?string $m_paymentIntentId;
    private ?string $m_subscriptionId;
    private ?string $m_invoiceId;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the session's fields.
     * @param StripeClient $client - the client that got the session.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $customerDetails = $fields->getOptionalObject('customer_details');
        $this->m_created = $fields->getTime('created');
        $this->m_expiresAt = $fields->getTime('expires_at');
        $this->m_mode = $fields->getString('mode');
        $this->m_status = $fields->getOptionalString('status');
        $this->m_paymentStatus = $fields->getString('payment_status');
        $this->m_uiMode = $fields->getOptionalString('ui_mode');
        $this->m_url = $fields->getOptionalString('url');
        $this->m_clientSecret = $fields->getOptionalString('client_secret');
        $this->m_successUrl = $fields->getOptionalString('success_url');
        $this->m_cancelUrl = $fields->getOptionalString('cancel_url');
        $this->m_returnUrl = $fields->getOptionalString('return_url');
        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_customerEmail = $fields->getOptionalString('customer_email');
        $this->m_customerDetails = ($customerDetails !== null) ? new CustomerDetails($customerDetails) : null;
        $this->m_amountSubtotal = $fields->getOptionalMoney('amount_subtotal');
        $this->m_amountTotal = $fields->getOptionalMoney('amount_total');
        $this->m_currency = $fields->getOptionalString('currency');
        $this->m_clientReferenceId = $fields->getOptionalString('client_reference_id');
        $this->m_metadata = $fields->getMetadata();
        $this->m_paymentIntentId = $fields->getOptionalId('payment_intent');
        $this->m_subscriptionId = $fields->getOptionalId('subscription');
        $this->m_invoiceId = $fields->getOptionalId('invoice');
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Check the session's status. This takes the enum, so the comparison can't be misspelt.
     * @param CheckoutSessionStatus $status - the status to check for.
     * @return bool
     */
    public function hasStatus(CheckoutSessionStatus $status): bool
    {
        return $this->m_status === $status->value;
    }


    /**
     * Check whether the session is still in progress: not yet completed, and not expired.
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->hasStatus(CheckoutSessionStatus::OPEN);
    }


    /**
     * Check whether the customer completed the session.
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->hasStatus(CheckoutSessionStatus::COMPLETE);
    }


    /**
     * Check whether the session expired before the customer completed it.
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->hasStatus(CheckoutSessionStatus::EXPIRED);
    }


    /**
     * Check the session's payment status. This takes the enum, so the comparison can't be misspelt.
     * @param CheckoutSessionPaymentStatus $paymentStatus - the payment status to check for.
     * @return bool
     */
    public function hasPaymentStatus(CheckoutSessionPaymentStatus $paymentStatus): bool
    {
        return $this->m_paymentStatus === $paymentStatus->value;
    }


    /**
     * Check whether the session's payment has been made, and the funds are available.
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->hasPaymentStatus(CheckoutSessionPaymentStatus::PAID);
    }


    /**
     * Check the session's mode. This takes the enum, so the comparison can't be misspelt.
     * @param SessionMode $mode - the mode to check for.
     * @return bool
     */
    public function hasMode(SessionMode $mode): bool
    {
        return $this->m_mode === $mode->value;
    }


    /**
     * Ask Stripe for the session's customer.
     * @return Customer
     * @throws ExceptionNoRelatedObject - if the session has no customer. Check getCustomerId() first.
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        if ($this->m_customerId === null)
        {
            throw new ExceptionNoRelatedObject("Checkout session {$this->getId()} has no customer.");
        }

        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Ask Stripe for the subscription the session created.
     * @return Subscription
     * @throws ExceptionNoRelatedObject - if the session has no subscription (e.g. it's for a one-off payment, or
     * hasn't been completed yet). Check getSubscriptionId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchSubscription(): Subscription
    {
        if ($this->m_subscriptionId === null)
        {
            throw new ExceptionNoRelatedObject("Checkout session {$this->getId()} has no subscription.");
        }

        return $this->m_client->retrieveSubscription($this->m_subscriptionId);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Checkout\Session
     */
    public function getStripeObject(): \Stripe\Checkout\Session
    {
        return $this->rebuildStripeObject(\Stripe\Checkout\Session::class);
    }


    protected static function getObjectType(): string
    {
        return 'checkout.session';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->m_expiresAt; }
    public function getMode(): string { return $this->m_mode; }
    public function getStatus(): ?string { return $this->m_status; }
    public function getPaymentStatus(): string { return $this->m_paymentStatus; }
    public function getUiMode(): ?string { return $this->m_uiMode; }
    public function getUrl(): ?string { return $this->m_url; }
    public function getClientSecret(): ?string { return $this->m_clientSecret; }
    public function getSuccessUrl(): ?string { return $this->m_successUrl; }
    public function getCancelUrl(): ?string { return $this->m_cancelUrl; }
    public function getReturnUrl(): ?string { return $this->m_returnUrl; }
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getCustomerEmail(): ?string { return $this->m_customerEmail; }
    public function getCustomerDetails(): ?CustomerDetails { return $this->m_customerDetails; }
    public function getAmountSubtotal(): ?Money { return $this->m_amountSubtotal; }
    public function getAmountTotal(): ?Money { return $this->m_amountTotal; }
    public function getCurrency(): ?string { return $this->m_currency; }
    public function getClientReferenceId(): ?string { return $this->m_clientReferenceId; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function getPaymentIntentId(): ?string { return $this->m_paymentIntentId; }
    public function getSubscriptionId(): ?string { return $this->m_subscriptionId; }
    public function getInvoiceId(): ?string { return $this->m_invoiceId; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
