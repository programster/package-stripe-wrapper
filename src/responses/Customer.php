<?php

/*
 * A customer, as Stripe sent it. Most of its fields are optional, so they're null when they haven't been set.
 * https://docs.stripe.com/api/customers/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class Customer extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private ?string $m_email;
    private ?string $m_name;
    private ?string $m_phone;
    private ?string $m_description;
    private ?Address $m_address;
    private ?Shipping $m_shipping;
    private Metadata $m_metadata;
    private int $m_balance;
    private ?string $m_currency;
    private ?bool $m_delinquent;
    private StringCollection $m_preferredLocales;
    private ?string $m_taxExempt;
    private ?string $m_invoicePrefix;
    private bool $m_livemode;
    private ?string $m_testClockId;


    /**
     * @param ResponseFields $fields - the customer's fields.
     * @param StripeClient $client - the client that got the customer.
     * @throws ExceptionCustomerDeleted - if the customer has been deleted, as Stripe only sends its ID.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        if ($fields->getOptionalBool('deleted') === true)
        {
            throw new ExceptionCustomerDeleted(
                "Customer " . $fields->getString('id') . " has been deleted, so Stripe only sends its ID."
            );
        }

        parent::__construct($fields, $client);
        $address = $fields->getOptionalObject('address');
        $shipping = $fields->getOptionalObject('shipping');
        $this->m_created = $fields->getTime('created');
        $this->m_email = $fields->getOptionalString('email');
        $this->m_name = $fields->getOptionalString('name');
        $this->m_phone = $fields->getOptionalString('phone');
        $this->m_description = $fields->getOptionalString('description');
        $this->m_address = ($address !== null) ? new Address($address) : null;
        $this->m_shipping = ($shipping !== null) ? new Shipping($shipping) : null;
        $this->m_metadata = $fields->getMetadata();
        $this->m_balance = $fields->getInt('balance');
        $this->m_currency = $fields->getOptionalString('currency');
        $this->m_delinquent = $fields->getOptionalBool('delinquent');
        $this->m_preferredLocales = $fields->getStrings('preferred_locales');
        $this->m_taxExempt = $fields->getOptionalString('tax_exempt');
        $this->m_invoicePrefix = $fields->getOptionalString('invoice_prefix');
        $this->m_livemode = $fields->getBool('livemode');
        $this->m_testClockId = $fields->getOptionalId('test_clock');
    }


    /**
     * Ask Stripe for the customer's saved payment methods, all of them.
     * @return PaymentMethodCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchPaymentMethods(): PaymentMethodCollection
    {
        return $this->m_client->listCustomerPaymentMethods($this->getId(), limit: ResultLimit::createAll());
    }


    /**
     * Ask Stripe for the customer's subscriptions, whatever their status (including cancelled ones), all of them.
     * @return SubscriptionCollection
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchSubscriptions(): SubscriptionCollection
    {
        return $this->m_client->listSubscriptions(
            customerId: $this->getId(),
            subscriptionStatus: SubscriptionStatus::ALL,
            limit: ResultLimit::createAll()
        );
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Customer
     */
    public function getStripeObject(): \Stripe\Customer
    {
        return $this->rebuildStripeObject(\Stripe\Customer::class);
    }


    protected static function getObjectType(): string
    {
        return 'customer';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getEmail(): ?string { return $this->m_email; }
    public function getName(): ?string { return $this->m_name; }
    public function getPhone(): ?string { return $this->m_phone; }
    public function getDescription(): ?string { return $this->m_description; }
    public function getAddress(): ?Address { return $this->m_address; }
    public function getShipping(): ?Shipping { return $this->m_shipping; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function getBalance(): int { return $this->m_balance; }
    public function getCurrency(): ?string { return $this->m_currency; }
    public function isDelinquent(): ?bool { return $this->m_delinquent; }
    public function getPreferredLocales(): StringCollection { return $this->m_preferredLocales; }
    public function getTaxExempt(): ?string { return $this->m_taxExempt; }
    public function getInvoicePrefix(): ?string { return $this->m_invoicePrefix; }
    public function isLivemode(): bool { return $this->m_livemode; }
    public function getTestClockId(): ?string { return $this->m_testClockId; }
}
