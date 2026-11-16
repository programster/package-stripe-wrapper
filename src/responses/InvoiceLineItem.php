<?php

/*
 * One line of an invoice, such as a subscription item's charge for a period, or the credit for the unused time of a
 * price it was changed from.
 * https://docs.stripe.com/api/invoice-line-item/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;


final readonly class InvoiceLineItem extends AbstractResponse
{
    private ?string $m_description;
    private Money $m_amount;
    private ?int $m_quantity;
    private \DateTimeImmutable $m_periodStart;
    private \DateTimeImmutable $m_periodEnd;
    private bool $m_isProration;
    private ?string $m_priceId;
    private ?string $m_subscriptionItemId;
    private Metadata $m_metadata;


    /**
     * @param ResponseFields $fields - the line's fields.
     * @param StripeClient $client - the client that got the line.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $period = $fields->getObject('period');
        $parent = $fields->getOptionalObject('parent');
        $subscriptionItemDetails = $parent?->getOptionalObject('subscription_item_details');
        $invoiceItemDetails = $parent?->getOptionalObject('invoice_item_details');
        $priceDetails = $fields->getOptionalObject('pricing')?->getOptionalObject('price_details');

        $isProration = (
               $subscriptionItemDetails?->getOptionalBool('proration') === true
            || $invoiceItemDetails?->getOptionalBool('proration') === true
        );

        $this->m_description = $fields->getOptionalString('description');
        $this->m_amount = new Money($fields->getInt('amount'), $fields->getString('currency'));
        $this->m_quantity = $fields->getOptionalInt('quantity');
        $this->m_periodStart = $period->getTime('start');
        $this->m_periodEnd = $period->getTime('end');
        $this->m_isProration = $isProration;
        $this->m_priceId = $priceDetails?->getOptionalId('price');
        $this->m_subscriptionItemId = $subscriptionItemDetails?->getOptionalId('subscription_item');
        $this->m_metadata = $fields->getMetadata();
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\InvoiceLineItem
     */
    public function getStripeObject(): \Stripe\InvoiceLineItem
    {
        return $this->rebuildStripeObject(\Stripe\InvoiceLineItem::class);
    }


    protected static function getObjectType(): string
    {
        return 'line_item';
    }


    # Accessors
    public function getDescription(): ?string { return $this->m_description; }
    public function getAmount(): Money { return $this->m_amount; }
    public function getQuantity(): ?int { return $this->m_quantity; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->m_periodStart; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->m_periodEnd; }
    public function isProration(): bool { return $this->m_isProration; }
    public function getPriceId(): ?string { return $this->m_priceId; }
    public function getSubscriptionItemId(): ?string { return $this->m_subscriptionItemId; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
}
