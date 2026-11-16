<?php

/*
 * A preview of an invoice that hasn't been created: what a subscription's next invoice will be, or what a change to
 * a subscription (or a new one) would be invoiced. Get one from StripeClient::previewUpcomingInvoice(),
 * previewSubscriptionChange() or previewNewSubscription(). It's its own type, rather than an Invoice, as nothing in
 * Stripe has its ID: it can't be retrieved, paid or sent.
 * https://docs.stripe.com/api/invoices/create_preview
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionIncompleteList;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;


final readonly class InvoicePreview extends AbstractResponse
{
    private ?string $m_customerId;
    private ?string $m_billingReason;
    private Money $m_amountDue;
    private Money $m_subtotal;
    private Money $m_total;
    private \DateTimeImmutable $m_periodStart;
    private \DateTimeImmutable $m_periodEnd;
    private ?\DateTimeImmutable $m_dueDate;
    private ?\DateTimeImmutable $m_prorationDate;
    private InvoiceLineItemCollection $m_lines;
    private bool $m_hasAllLines;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the preview's fields.
     * @param StripeClient $client - the client that got the preview.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $currency = $fields->getString('currency');
        $subscriptionDetails = $fields->getOptionalObject('parent')?->getOptionalObject('subscription_details');
        [$lineFields, $hasMoreLines] = $fields->getList('lines');
        $lines = [];

        foreach ($lineFields as $lineField)
        {
            $lines[] = InvoiceLineItem::createFromFields($lineField, $client);
        }

        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_billingReason = $fields->getOptionalString('billing_reason');
        $this->m_amountDue = new Money($fields->getInt('amount_due'), $currency);
        $this->m_subtotal = new Money($fields->getInt('subtotal'), $currency);
        $this->m_total = new Money($fields->getInt('total'), $currency);
        $this->m_periodStart = $fields->getTime('period_start');
        $this->m_periodEnd = $fields->getTime('period_end');
        $this->m_dueDate = $fields->getOptionalTime('due_date');
        $this->m_prorationDate = $subscriptionDetails?->getOptionalTime('subscription_proration_date');
        $this->m_lines = new InvoiceLineItemCollection(...$lines);
        $this->m_hasAllLines = !$hasMoreLines;
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Get the preview's lines, as Stripe included them in it.
     * @return InvoiceLineItemCollection
     * @throws ExceptionIncompleteList - if Stripe left some out, as it only includes the first few. Check with
     * hasAllLines() first. The totals always include every line.
     */
    public function getLines(): InvoiceLineItemCollection
    {
        if (!$this->m_hasAllLines)
        {
            throw new ExceptionIncompleteList(
                "The invoice preview has more lines than Stripe included in it, so they can't all be shown. Its " .
                "totals include every line."
            );
        }

        return $this->m_lines;
    }


    /**
     * Check whether getLines() can be used: whether Stripe included all of the preview's lines in it.
     * @return bool
     */
    public function hasAllLines(): bool
    {
        return $this->m_hasAllLines;
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
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getBillingReason(): ?string { return $this->m_billingReason; }
    public function getAmountDue(): Money { return $this->m_amountDue; }
    public function getSubtotal(): Money { return $this->m_subtotal; }
    public function getTotal(): Money { return $this->m_total; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->m_periodStart; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->m_periodEnd; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->m_dueDate; }
    public function getProrationDate(): ?\DateTimeImmutable { return $this->m_prorationDate; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
