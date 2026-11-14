<?php

/*
 * An invoice, as Stripe sent it. It doesn't include its payments: use fetchPayments(), or ask for the invoices with
 * them (StripeClient::listInvoicesWithPayments()), which gives an InvoiceWithPayments.
 * https://docs.stripe.com/api/invoices/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\InvoiceStatus;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class Invoice extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private ?string $m_customerId;
    private ?string $m_customerEmail;
    private ?string $m_subscriptionId;
    private ?string $m_status;
    private string $m_collectionMethod;
    private ?string $m_number;
    private Money $m_amountDue;
    private Money $m_amountPaid;
    private Money $m_amountRemaining;
    private Money $m_subtotal;
    private Money $m_total;
    private ?\DateTimeImmutable $m_dueDate;
    private \DateTimeImmutable $m_periodStart;
    private \DateTimeImmutable $m_periodEnd;
    private ?string $m_hostedInvoiceUrl;
    private ?string $m_invoicePdf;
    private ?string $m_billingReason;
    private ?string $m_description;
    private Metadata $m_metadata;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the invoice's fields.
     * @param StripeClient $client - the client that got the invoice.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $currency = $fields->getString('currency');
        # The subscription an invoice is for is in its parent's details.
        $subscriptionDetails = $fields->getOptionalObject('parent')?->getOptionalObject('subscription_details');
        $this->m_created = $fields->getTime('created');
        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_customerEmail = $fields->getOptionalString('customer_email');
        $this->m_subscriptionId = $subscriptionDetails?->getOptionalId('subscription');
        $this->m_status = $fields->getOptionalString('status');
        $this->m_collectionMethod = $fields->getString('collection_method');
        $this->m_number = $fields->getOptionalString('number');
        $this->m_amountDue = new Money($fields->getInt('amount_due'), $currency);
        $this->m_amountPaid = new Money($fields->getInt('amount_paid'), $currency);
        $this->m_amountRemaining = new Money($fields->getInt('amount_remaining'), $currency);
        $this->m_subtotal = new Money($fields->getInt('subtotal'), $currency);
        $this->m_total = new Money($fields->getInt('total'), $currency);
        $this->m_dueDate = $fields->getOptionalTime('due_date');
        $this->m_periodStart = $fields->getTime('period_start');
        $this->m_periodEnd = $fields->getTime('period_end');
        $this->m_hostedInvoiceUrl = $fields->getOptionalString('hosted_invoice_url');
        $this->m_invoicePdf = $fields->getOptionalString('invoice_pdf');
        $this->m_billingReason = $fields->getOptionalString('billing_reason');
        $this->m_description = $fields->getOptionalString('description');
        $this->m_metadata = $fields->getMetadata();
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Check the invoice's status. This takes the enum, so the comparison can't be misspelt.
     * @param InvoiceStatus $status - the status to check for.
     * @return bool
     */
    public function hasStatus(InvoiceStatus $status): bool
    {
        return $this->m_status === $status->value;
    }


    /**
     * Check whether the invoice has been paid.
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->hasStatus(InvoiceStatus::PAID);
    }


    /**
     * Check how the invoice is paid. This takes the enum, so the comparison can't be misspelt.
     * @param SubscriptionCollectionMethod $collectionMethod - the collection method to check for.
     * @return bool
     */
    public function hasCollectionMethod(SubscriptionCollectionMethod $collectionMethod): bool
    {
        return $this->m_collectionMethod === $collectionMethod->value;
    }


    /**
     * Ask Stripe for every payment made against the invoice.
     * @return InvoicePaymentCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchPayments(): InvoicePaymentCollection
    {
        return $this->m_client->listInvoicePayments($this->getId(), ResultLimit::all());
    }


    /**
     * Ask Stripe for the invoice's customer.
     * @return Customer
     * @throws ExceptionNoRelatedObject - if the invoice has no customer. Check getCustomerId() first.
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        if ($this->m_customerId === null)
        {
            throw new ExceptionNoRelatedObject("Invoice {$this->getId()} has no customer.");
        }

        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Ask Stripe for the subscription the invoice is for.
     * @return Subscription
     * @throws ExceptionNoRelatedObject - if the invoice isn't for a subscription. Check getSubscriptionId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchSubscription(): Subscription
    {
        if ($this->m_subscriptionId === null)
        {
            throw new ExceptionNoRelatedObject("Invoice {$this->getId()} isn't for a subscription.");
        }

        return $this->m_client->retrieveSubscription($this->m_subscriptionId);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Invoice
     */
    public function getStripeObject(): \Stripe\Invoice
    {
        return $this->rebuildStripeObject(\Stripe\Invoice::class);
    }


    protected static function getObjectType(): string
    {
        return 'invoice';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getCustomerEmail(): ?string { return $this->m_customerEmail; }
    public function getSubscriptionId(): ?string { return $this->m_subscriptionId; }
    public function getStatus(): ?string { return $this->m_status; }
    public function getCollectionMethod(): string { return $this->m_collectionMethod; }
    public function getNumber(): ?string { return $this->m_number; }
    public function getAmountDue(): Money { return $this->m_amountDue; }
    public function getAmountPaid(): Money { return $this->m_amountPaid; }
    public function getAmountRemaining(): Money { return $this->m_amountRemaining; }
    public function getSubtotal(): Money { return $this->m_subtotal; }
    public function getTotal(): Money { return $this->m_total; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->m_dueDate; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->m_periodStart; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->m_periodEnd; }
    public function getHostedInvoiceUrl(): ?string { return $this->m_hostedInvoiceUrl; }
    public function getInvoicePdf(): ?string { return $this->m_invoicePdf; }
    public function getBillingReason(): ?string { return $this->m_billingReason; }
    public function getDescription(): ?string { return $this->m_description; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
