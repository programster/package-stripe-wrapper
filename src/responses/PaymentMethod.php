<?php

/*
 * A payment method, as Stripe sent it, such as a customer's saved card.
 * https://docs.stripe.com/api/payment_methods/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class PaymentMethod extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private string $m_type;
    private ?string $m_customerId;
    private ?Card $m_card;
    private ?string $m_billingEmail;
    private ?string $m_billingName;
    private Metadata $m_metadata;
    private bool $m_livemode;


    /**
     * @param ResponseFields $fields - the payment method's fields.
     * @param StripeClient $client - the client that got the payment method.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $card = $fields->getOptionalObject('card');
        $billingDetails = $fields->getOptionalObject('billing_details');
        $this->m_created = $fields->getTime('created');
        $this->m_type = $fields->getString('type');
        $this->m_customerId = $fields->getOptionalId('customer');
        $this->m_card = ($card !== null) ? new Card($card) : null;
        $this->m_billingEmail = $billingDetails?->getOptionalString('email');
        $this->m_billingName = $billingDetails?->getOptionalString('name');
        $this->m_metadata = $fields->getMetadata();
        $this->m_livemode = $fields->getBool('livemode');
    }


    /**
     * Check the payment method's type. This takes the enum, so the comparison can't be misspelt.
     * @param PaymentMethodType $type - the type to check for.
     * @return bool
     */
    public function hasType(PaymentMethodType $type): bool
    {
        return $this->m_type === $type->value;
    }


    /**
     * Ask Stripe for the customer the payment method is saved to.
     * @return Customer
     * @throws ExceptionNoRelatedObject - if it isn't saved to a customer. Check getCustomerId() first.
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        if ($this->m_customerId === null)
        {
            throw new ExceptionNoRelatedObject("Payment method {$this->getId()} isn't saved to a customer.");
        }

        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\PaymentMethod
     */
    public function getStripeObject(): \Stripe\PaymentMethod
    {
        return $this->rebuildStripeObject(\Stripe\PaymentMethod::class);
    }


    protected static function getObjectType(): string
    {
        return 'payment_method';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getType(): string { return $this->m_type; }
    public function getCustomerId(): ?string { return $this->m_customerId; }
    public function getCard(): ?Card { return $this->m_card; }
    public function getBillingEmail(): ?string { return $this->m_billingEmail; }
    public function getBillingName(): ?string { return $this->m_billingName; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function isLivemode(): bool { return $this->m_livemode; }
}
