<?php

/*
 * One item of a subscription, as Stripe sent it: a price, and how many of it. Its billing period is on the item, not
 * the subscription, as each item can be billed on its own schedule.
 * https://docs.stripe.com/api/subscription_items/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class SubscriptionItem extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private string $m_subscriptionId;
    private ?int $m_quantity;
    private string $m_priceId;
    private ?string $m_productId;
    private ?Money $m_unitAmount;
    private ?string $m_recurringInterval;
    private ?int $m_recurringIntervalCount;
    private \DateTimeImmutable $m_currentPeriodStart;
    private \DateTimeImmutable $m_currentPeriodEnd;
    private Metadata $m_metadata;
    private StringCollection $m_taxRateIds;


    /**
     * @param ResponseFields $fields - the item's fields.
     * @param StripeClient $client - the client that got the item.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        $price = $fields->getObject('price');
        $recurring = $price->getOptionalObject('recurring');
        $taxRateIds = [];

        foreach ($fields->getObjects('tax_rates') as $taxRate)
        {
            $taxRateIds[] = $taxRate->getString('id');
        }

        $this->m_created = $fields->getTime('created');
        $this->m_subscriptionId = $fields->getString('subscription');
        $this->m_quantity = $fields->getOptionalInt('quantity');
        $this->m_priceId = $price->getString('id');
        $this->m_productId = $price->getOptionalId('product');
        $this->m_unitAmount = $price->getOptionalMoney('unit_amount');
        $this->m_recurringInterval = ($recurring !== null) ? $recurring->getString('interval') : null;
        $this->m_recurringIntervalCount = ($recurring !== null) ? $recurring->getInt('interval_count') : null;
        $this->m_currentPeriodStart = $fields->getTime('current_period_start');
        $this->m_currentPeriodEnd = $fields->getTime('current_period_end');
        $this->m_metadata = $fields->getMetadata();
        $this->m_taxRateIds = new StringCollection(...$taxRateIds);
    }


    /**
     * Ask Stripe for the subscription the item is part of.
     * @return Subscription
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchSubscription(): Subscription
    {
        return $this->m_client->retrieveSubscription($this->m_subscriptionId);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\SubscriptionItem
     */
    public function getStripeObject(): \Stripe\SubscriptionItem
    {
        return $this->rebuildStripeObject(\Stripe\SubscriptionItem::class);
    }


    protected static function getObjectType(): string
    {
        return 'subscription_item';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getSubscriptionId(): string { return $this->m_subscriptionId; }
    public function getQuantity(): ?int { return $this->m_quantity; }
    public function getPriceId(): string { return $this->m_priceId; }
    public function getProductId(): ?string { return $this->m_productId; }
    public function getUnitAmount(): ?Money { return $this->m_unitAmount; }
    public function getRecurringInterval(): ?string { return $this->m_recurringInterval; }
    public function getRecurringIntervalCount(): ?int { return $this->m_recurringIntervalCount; }
    public function getCurrentPeriodStart(): \DateTimeImmutable { return $this->m_currentPeriodStart; }
    public function getCurrentPeriodEnd(): \DateTimeImmutable { return $this->m_currentPeriodEnd; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function getTaxRateIds(): StringCollection { return $this->m_taxRateIds; }
}
