<?php

/*
 * A refund, as Stripe sent it: money given back for a charge.
 * https://docs.stripe.com/api/refunds/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\RefundStatus;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;

final readonly class Refund extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private Money $m_amount;
    private ?string $m_status;
    private ?string $m_chargeId;
    private ?string $m_paymentIntentId;
    private ?string $m_reason;
    private Metadata $m_metadata;


    /**
     * @param ResponseFields $fields - the refund's fields.
     * @param StripeClient $client - the client that got the refund.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $this->m_created = $fields->getTime('created');
        $this->m_amount = new Money($fields->getInt('amount'), $fields->getString('currency'));
        $this->m_status = $fields->getOptionalString('status');
        $this->m_chargeId = $fields->getOptionalId('charge');
        $this->m_paymentIntentId = $fields->getOptionalId('payment_intent');
        $this->m_reason = $fields->getOptionalString('reason');
        $this->m_metadata = $fields->getMetadata();
    }


    /**
     * Check the refund's status. This takes the enum, so the comparison can't be misspelt.
     * @param RefundStatus $status - the status to check for.
     * @return bool
     */
    public function hasStatus(RefundStatus $status): bool
    {
        return $this->m_status === $status->value;
    }


    /**
     * Check whether the refund succeeded.
     * @return bool
     */
    public function isSucceeded(): bool
    {
        return $this->hasStatus(RefundStatus::SUCCEEDED);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Refund
     */
    public function getStripeObject(): \Stripe\Refund
    {
        return $this->rebuildStripeObject(\Stripe\Refund::class);
    }


    protected static function getObjectType(): string
    {
        return 'refund';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getAmount(): Money { return $this->m_amount; }
    public function getStatus(): ?string { return $this->m_status; }
    public function getChargeId(): ?string { return $this->m_chargeId; }
    public function getPaymentIntentId(): ?string { return $this->m_paymentIntentId; }
    public function getReason(): ?string { return $this->m_reason; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
}
