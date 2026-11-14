<?php

/*
 * One payment made against an invoice, as Stripe sent it, e.g. to find the payment intent that paid it.
 * https://docs.stripe.com/api/invoice-payment/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;

final readonly class InvoicePayment extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private string $m_invoiceId;
    private string $m_status;
    private ?Money $m_amountPaid;
    private Money $m_amountRequested;
    private ?string $m_paymentIntentId;
    private bool $m_isDefault;


    /**
     * @param ResponseFields $fields - the invoice payment's fields.
     * @param StripeClient $client - the client that got the invoice payment.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $payment = $fields->getObject('payment');
        $this->m_created = $fields->getTime('created');
        $this->m_invoiceId = $fields->getId('invoice');
        $this->m_status = $fields->getString('status');
        $this->m_amountPaid = $fields->getOptionalMoney('amount_paid');
        $this->m_amountRequested = new Money($fields->getInt('amount_requested'), $fields->getString('currency'));
        $this->m_paymentIntentId = $payment->getOptionalId('payment_intent');
        $this->m_isDefault = $fields->getBool('is_default');
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\InvoicePayment
     */
    public function getStripeObject(): \Stripe\InvoicePayment
    {
        return $this->rebuildStripeObject(\Stripe\InvoicePayment::class);
    }


    protected static function getObjectType(): string
    {
        return 'invoice_payment';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getInvoiceId(): string { return $this->m_invoiceId; }
    public function getStatus(): string { return $this->m_status; }
    public function getAmountPaid(): ?Money { return $this->m_amountPaid; }
    public function getAmountRequested(): Money { return $this->m_amountRequested; }
    public function getPaymentIntentId(): ?string { return $this->m_paymentIntentId; }
    public function isDefault(): bool { return $this->m_isDefault; }
}
