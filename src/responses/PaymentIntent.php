<?php

/*
 * A payment intent, as Stripe sent it: an attempt to take a payment, from creation through to it succeeding (or not).
 * https://docs.stripe.com/api/payment_intents/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\PaymentIntentStatus;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class PaymentIntent extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private Money $m_amount;
    private Money $m_amountReceived;
    private Money $m_amountCapturable;
    private string $m_status;
    private ?string $m_customerId;
    private ?string $m_latestChargeId;
    private ?string $m_paymentMethodId;
    private string $m_captureMethod;
    private ?string $m_description;
    private ?string $m_receiptEmail;
    private ?\DateTimeImmutable $m_canceledAt;
    private ?string $m_cancellationReason;
    private Metadata $m_metadata;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the payment intent's fields.
     * @param StripeClient $client - the client that got the payment intent.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $currency = $fields->getString('currency');
        $this->m_created = $fields->getTime('created');
        $this->m_amount = new Money($fields->getInt('amount'), $currency);
        $this->m_amountReceived = new Money($fields->getInt('amount_received'), $currency);
        $this->m_amountCapturable = new Money($fields->getInt('amount_capturable'), $currency);
        $this->m_status = $fields->getString('status');
        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_latestChargeId = $fields->getOptionalId('latest_charge');
        $this->m_paymentMethodId = $fields->getOptionalId('payment_method');
        $this->m_captureMethod = $fields->getString('capture_method');
        $this->m_description = $fields->getOptionalString('description');
        $this->m_receiptEmail = $fields->getOptionalString('receipt_email');
        $this->m_canceledAt = $fields->getOptionalTime('canceled_at');
        $this->m_cancellationReason = $fields->getOptionalString('cancellation_reason');
        $this->m_metadata = $fields->getMetadata();
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Check the payment intent's status. This takes the enum, so the comparison can't be misspelt.
     * @param PaymentIntentStatus $status - the status to check for.
     * @return bool
     */
    public function hasStatus(PaymentIntentStatus $status): bool
    {
        return $this->m_status === $status->value;
    }


    /**
     * Check whether the payment succeeded.
     * @return bool
     */
    public function isSucceeded(): bool
    {
        return $this->hasStatus(PaymentIntentStatus::SUCCEEDED);
    }


    /**
     * Ask Stripe for the payment intent's customer.
     * @return Customer
     * @throws ExceptionNoRelatedObject - if the payment intent has no customer. Check getCustomerId() first.
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        if ($this->m_customerId === null)
        {
            throw new ExceptionNoRelatedObject("Payment intent {$this->getId()} has no customer.");
        }

        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Ask Stripe for the payment intent's latest charge.
     * @return Charge
     * @throws ExceptionNoRelatedObject - if the payment intent has no charge yet. Check getLatestChargeId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchLatestCharge(): Charge
    {
        if ($this->m_latestChargeId === null)
        {
            throw new ExceptionNoRelatedObject("Payment intent {$this->getId()} has no charge yet.");
        }

        return $this->m_client->retrieveCharge($this->m_latestChargeId);
    }


    /**
     * Ask Stripe for the payment intent's payment method.
     * @return PaymentMethod
     * @throws ExceptionNoRelatedObject - if the payment intent has no payment method. Check getPaymentMethodId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchPaymentMethod(): PaymentMethod
    {
        if ($this->m_paymentMethodId === null)
        {
            throw new ExceptionNoRelatedObject("Payment intent {$this->getId()} has no payment method.");
        }

        return $this->m_client->retrievePaymentMethod($this->m_paymentMethodId);
    }


    /**
     * Ask Stripe for the payment intent's charges, all of them.
     * @return ChargeCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCharges(): ChargeCollection
    {
        return $this->m_client->listCharges(paymentIntentId: $this->getId(), limit: ResultLimit::createAll());
    }


    /**
     * Ask Stripe for the payment intent's refunds, all of them.
     * @return RefundCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchRefunds(): RefundCollection
    {
        return $this->m_client->listRefunds(paymentIntentId: $this->getId(), limit: ResultLimit::createAll());
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\PaymentIntent
     */
    public function getStripeObject(): \Stripe\PaymentIntent
    {
        return $this->rebuildStripeObject(\Stripe\PaymentIntent::class);
    }


    protected static function getObjectType(): string
    {
        return 'payment_intent';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getAmount(): Money { return $this->m_amount; }
    public function getAmountReceived(): Money { return $this->m_amountReceived; }
    public function getAmountCapturable(): Money { return $this->m_amountCapturable; }
    public function getStatus(): string { return $this->m_status; }
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getLatestChargeId(): ?string { return $this->m_latestChargeId; }
    public function getPaymentMethodId(): ?string { return $this->m_paymentMethodId; }
    public function getCaptureMethod(): string { return $this->m_captureMethod; }
    public function getDescription(): ?string { return $this->m_description; }
    public function getReceiptEmail(): ?string { return $this->m_receiptEmail; }
    public function getCanceledAt(): ?\DateTimeImmutable { return $this->m_canceledAt; }
    public function getCancellationReason(): ?string { return $this->m_cancellationReason; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
