<?php

/*
 * A webhook event, whose signature has been checked by StripeClient::verifyWebhookEvent(), so it's certain to have come
 * from Stripe. Check its type with hasType(), and get the object it's about as one of this package's models, e.g. with
 * getCheckoutSession() for a checkout.session.completed event.
 * https://docs.stripe.com/api/events/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Enums\EventType;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;


final readonly class Event extends AbstractResponse
{
    private string $m_type;
    private \DateTimeImmutable $m_created;
    private ?string $m_apiVersion;
    private bool $m_livemode;
    private ResponseFields $m_dataObject;
    private string $m_dataObjectType;


    /**
     * @param ResponseFields $fields - the event's fields.
     * @param StripeClient $client - the client that verified the event, for the models of the object it's about.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $this->m_type = $fields->getString('type');
        $this->m_created = $fields->getTime('created');
        $this->m_apiVersion = $fields->getOptionalString('api_version');
        $this->m_livemode = $fields->getBool('livemode');
        $this->m_dataObject = $fields->getObject('data')->getObject('object');
        $this->m_dataObjectType = $this->m_dataObject->getString('object');
    }


    /**
     * Check the event's type. This takes the enum, so the comparison can't be misspelt.
     * @param EventType $type - the type to check for, e.g. EventType::CHECKOUT_SESSION_COMPLETED.
     * @return bool
     */
    public function hasType(EventType $type): bool
    {
        return $this->m_type === $type->value;
    }


    /**
     * Get a copy of the object the event is about, as the SDK's object, e.g. for one this package has no model for.
     * It's a fresh copy each time, so changing it can't change the event.
     * @return \Stripe\StripeObject
     * @throws \LogicException - if the SDK rebuilds it as a list, which would be a bug, as it's an object.
     */
    public function getDataObject(): \Stripe\StripeObject
    {
        $object = \Stripe\Util\Util::convertToStripeObject($this->m_dataObject->toArray(), []);

        if (!($object instanceof \Stripe\StripeObject))
        {
            throw new \LogicException("The SDK rebuilt event {$this->getId()}'s object as a list.");
        }

        return $object;
    }


    /**
     * Get the checkout session the event is about, e.g. for a checkout.session.completed event.
     * @return CheckoutSession
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getCheckoutSession(): CheckoutSession
    {
        return CheckoutSession::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the customer the event is about, e.g. for a customer.updated event.
     * @return Customer
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     * @throws ExceptionCustomerDeleted - if the customer has been deleted, as for a customer.deleted event.
     */
    public function getCustomer(): Customer
    {
        return Customer::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the subscription the event is about, e.g. for a customer.subscription.updated event.
     * @return Subscription
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getSubscription(): Subscription
    {
        return Subscription::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the invoice the event is about, e.g. for an invoice.paid event.
     * @return Invoice
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getInvoice(): Invoice
    {
        return Invoice::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the payment intent the event is about, e.g. for a payment_intent.succeeded event.
     * @return PaymentIntent
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getPaymentIntent(): PaymentIntent
    {
        return PaymentIntent::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the charge the event is about, e.g. for a charge.refunded event.
     * @return Charge
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getCharge(): Charge
    {
        return Charge::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the refund the event is about, e.g. for a refund.failed event.
     * @return Refund
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getRefund(): Refund
    {
        return Refund::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get the payment method the event is about, e.g. for a payment_method.attached event.
     * @return PaymentMethod
     * @throws ExceptionUnexpectedResponse - if the event is about something else. Check its type first.
     */
    public function getPaymentMethod(): PaymentMethod
    {
        return PaymentMethod::createFromStripeObject($this->getDataObject(), $this->m_client);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Event
     */
    public function getStripeObject(): \Stripe\Event
    {
        return $this->rebuildStripeObject(\Stripe\Event::class);
    }


    protected static function getObjectType(): string
    {
        return 'event';
    }


    # Accessors
    public function getType(): string { return $this->m_type; }
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getApiVersion(): ?string { return $this->m_apiVersion; }
    public function isLivemode(): bool { return $this->m_livemode; }
    public function getDataObjectType(): string { return $this->m_dataObjectType; }
}
