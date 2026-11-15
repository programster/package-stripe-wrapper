<?php

/*
 * A charge, as Stripe sent it: one attempt to take a payment from a card or other payment method.
 * https://docs.stripe.com/api/charges/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\ChargeStatus;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class Charge extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private Money $m_amount;
    private Money $m_amountCaptured;
    private Money $m_amountRefunded;
    private string $m_status;
    private bool $m_paid;
    private bool $m_refunded;
    private bool $m_captured;
    private bool $m_disputed;
    private ?string $m_customerId;
    private ?string $m_paymentIntentId;
    private ?string $m_paymentMethodId;
    private ?string $m_receiptUrl;
    private ?string $m_receiptEmail;
    private ?string $m_description;
    private ?string $m_failureCode;
    private ?string $m_failureMessage;
    private Metadata $m_metadata;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the charge's fields.
     * @param StripeClient $client - the client that got the charge.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $currency = $fields->getString('currency');
        $this->m_created = $fields->getTime('created');
        $this->m_amount = new Money($fields->getInt('amount'), $currency);
        $this->m_amountCaptured = new Money($fields->getInt('amount_captured'), $currency);
        $this->m_amountRefunded = new Money($fields->getInt('amount_refunded'), $currency);
        $this->m_status = $fields->getString('status');
        $this->m_paid = $fields->getBool('paid');
        $this->m_refunded = $fields->getBool('refunded');
        $this->m_captured = $fields->getBool('captured');
        $this->m_disputed = $fields->getBool('disputed');
        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_paymentIntentId = $fields->getOptionalId('payment_intent');
        $this->m_paymentMethodId = $fields->getOptionalId('payment_method');
        $this->m_receiptUrl = $fields->getOptionalString('receipt_url');
        $this->m_receiptEmail = $fields->getOptionalString('receipt_email');
        $this->m_description = $fields->getOptionalString('description');
        $this->m_failureCode = $fields->getOptionalString('failure_code');
        $this->m_failureMessage = $fields->getOptionalString('failure_message');
        $this->m_metadata = $fields->getMetadata();
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Check the charge's status. This takes the enum, so the comparison can't be misspelt.
     * @param ChargeStatus $status - the status to check for.
     * @return bool
     */
    public function hasStatus(ChargeStatus $status): bool
    {
        return $this->m_status === $status->value;
    }


    /**
     * Check whether the charge succeeded.
     * @return bool
     */
    public function isSucceeded(): bool
    {
        return $this->hasStatus(ChargeStatus::SUCCEEDED);
    }


    /**
     * Ask Stripe for the charge's customer.
     * @return Customer
     * @throws ExceptionNoRelatedObject - if the charge has no customer. Check getCustomerId() first.
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        if ($this->m_customerId === null)
        {
            throw new ExceptionNoRelatedObject("Charge {$this->getId()} has no customer.");
        }

        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Ask Stripe for the charge's payment intent.
     * @return PaymentIntent
     * @throws ExceptionNoRelatedObject - if the charge has no payment intent. Check getPaymentIntentId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchPaymentIntent(): PaymentIntent
    {
        if ($this->m_paymentIntentId === null)
        {
            throw new ExceptionNoRelatedObject("Charge {$this->getId()} has no payment intent.");
        }

        return $this->m_client->retrievePaymentIntent($this->m_paymentIntentId);
    }


    /**
     * Ask Stripe for the charge's payment method.
     * @return PaymentMethod
     * @throws ExceptionNoRelatedObject - if the charge has no payment method. Check getPaymentMethodId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchPaymentMethod(): PaymentMethod
    {
        if ($this->m_paymentMethodId === null)
        {
            throw new ExceptionNoRelatedObject("Charge {$this->getId()} has no payment method.");
        }

        return $this->m_client->retrievePaymentMethod($this->m_paymentMethodId);
    }


    /**
     * Ask Stripe for the charge's refunds, all of them.
     * @return RefundCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchRefunds(): RefundCollection
    {
        return $this->m_client->listRefunds(chargeId: $this->getId(), limit: ResultLimit::createAll());
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Charge
     */
    public function getStripeObject(): \Stripe\Charge
    {
        return $this->rebuildStripeObject(\Stripe\Charge::class);
    }


    protected static function getObjectType(): string
    {
        return 'charge';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getAmount(): Money { return $this->m_amount; }
    public function getAmountCaptured(): Money { return $this->m_amountCaptured; }
    public function getAmountRefunded(): Money { return $this->m_amountRefunded; }
    public function getStatus(): string { return $this->m_status; }
    public function isPaid(): bool { return $this->m_paid; }
    public function isRefunded(): bool { return $this->m_refunded; }
    public function isCaptured(): bool { return $this->m_captured; }
    public function isDisputed(): bool { return $this->m_disputed; }
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getPaymentIntentId(): ?string { return $this->m_paymentIntentId; }
    public function getPaymentMethodId(): ?string { return $this->m_paymentMethodId; }
    public function getReceiptUrl(): ?string { return $this->m_receiptUrl; }
    public function getReceiptEmail(): ?string { return $this->m_receiptEmail; }
    public function getDescription(): ?string { return $this->m_description; }
    public function getFailureCode(): ?string { return $this->m_failureCode; }
    public function getFailureMessage(): ?string { return $this->m_failureMessage; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
