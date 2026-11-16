<?php

/*
 * A subscription, as Stripe sent it. Its billing periods are on its items, as each item can be billed on its own
 * schedule.
 * https://docs.stripe.com/api/subscriptions/object
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionIncompleteList;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionTooManyResults;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

final readonly class Subscription extends AbstractResponse
{
    private \DateTimeImmutable $m_created;
    private string $m_customerId;
    private string $m_status;
    private SubscriptionItemCollection $m_items;
    private bool $m_hasAllItems;
    private \DateTimeImmutable $m_startDate;
    private bool $m_cancelAtPeriodEnd;
    private ?\DateTimeImmutable $m_cancelAt;
    private ?\DateTimeImmutable $m_canceledAt;
    private ?\DateTimeImmutable $m_endedAt;
    private ?\DateTimeImmutable $m_trialStart;
    private ?\DateTimeImmutable $m_trialEnd;
    private string $m_currency;
    private string $m_collectionMethod;
    private ?int $m_daysUntilDue;
    private ?string $m_defaultPaymentMethodId;
    private ?string $m_latestInvoiceId;
    private ?string $m_description;
    private Metadata $m_metadata;
    private ?string $m_cancellationComment;
    private ?string $m_cancellationFeedback;
    private ?string $m_cancellationReason;
    private bool $m_livemode;
    private ?string $m_testClockId;


    /**
     * @param ResponseFields $fields - the subscription's fields.
     * @param StripeClient $client - the client that got the subscription.
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        parent::__construct($fields, $client);
        [$itemFields, $hasMoreItems] = $fields->getList('items');
        $items = [];

        foreach ($itemFields as $itemField)
        {
            $items[] = SubscriptionItem::createFromFields($itemField, $client);
        }

        $cancellation = $fields->getOptionalObject('cancellation_details');
        $this->m_created = $fields->getTime('created');
        $this->m_customerId = $fields->getId('customer');
        $this->m_status = $fields->getString('status');
        $this->m_items = new SubscriptionItemCollection(...$items);
        $this->m_hasAllItems = !$hasMoreItems;
        $this->m_startDate = $fields->getTime('start_date');
        $this->m_cancelAtPeriodEnd = $fields->getBool('cancel_at_period_end');
        $this->m_cancelAt = $fields->getOptionalTime('cancel_at');
        $this->m_canceledAt = $fields->getOptionalTime('canceled_at');
        $this->m_endedAt = $fields->getOptionalTime('ended_at');
        $this->m_trialStart = $fields->getOptionalTime('trial_start');
        $this->m_trialEnd = $fields->getOptionalTime('trial_end');
        $this->m_currency = $fields->getString('currency');
        $this->m_collectionMethod = $fields->getString('collection_method');
        $this->m_daysUntilDue = $fields->getOptionalInt('days_until_due');
        $this->m_defaultPaymentMethodId = $fields->getOptionalId('default_payment_method');
        $this->m_latestInvoiceId = $fields->getOptionalId('latest_invoice');
        $this->m_description = $fields->getOptionalString('description');
        $this->m_metadata = $fields->getMetadata();
        $this->m_cancellationComment = $cancellation?->getOptionalString('comment');
        $this->m_cancellationFeedback = $cancellation?->getOptionalString('feedback');
        $this->m_cancellationReason = $cancellation?->getOptionalString('reason');
        $this->m_livemode = $fields->getBool('livemode');
        $this->m_testClockId = $fields->getOptionalId('test_clock');
    }


    /**
     * Check the subscription's status. This takes the enum, so the comparison can't be misspelt.
     * @param SubscriptionStatus $status - the status to check for.
     * @return bool
     * @throws \LogicException - if given SubscriptionStatus::ALL, which is only for filtering a list, so no
     * subscription has it.
     */
    public function hasStatus(SubscriptionStatus $status): bool
    {
        if ($status === SubscriptionStatus::ALL)
        {
            throw new \LogicException(
                "SubscriptionStatus::ALL is only for filtering a list, so no subscription has it."
            );
        }

        return $this->m_status === $status->value;
    }


    /**
     * Check whether the subscription is in good standing.
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->hasStatus(SubscriptionStatus::ACTIVE);
    }


    /**
     * Check whether the subscription is in its trial period.
     * @return bool
     */
    public function isTrialing(): bool
    {
        return $this->hasStatus(SubscriptionStatus::TRIALING);
    }


    /**
     * Check whether the latest invoice's payment failed, or wasn't attempted.
     * @return bool
     */
    public function isPastDue(): bool
    {
        return $this->hasStatus(SubscriptionStatus::PAST_DUE);
    }


    /**
     * Check whether the subscription has been cancelled, which is final.
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->hasStatus(SubscriptionStatus::CANCELLED);
    }


    /**
     * Check how the subscription is paid. This takes the enum, so the comparison can't be misspelt.
     * @param SubscriptionCollectionMethod $collectionMethod - the collection method to check for.
     * @return bool
     */
    public function hasCollectionMethod(SubscriptionCollectionMethod $collectionMethod): bool
    {
        return $this->m_collectionMethod === $collectionMethod->value;
    }


    /**
     * Get the subscription's items, as Stripe included them in the subscription.
     * @return SubscriptionItemCollection
     * @throws ExceptionIncompleteList - if Stripe left some out, as it only includes the first few. Use fetchItems()
     * instead, which gets them all.
     */
    public function getItems(): SubscriptionItemCollection
    {
        if (!$this->m_hasAllItems)
        {
            throw new ExceptionIncompleteList(
                "Subscription {$this->getId()} has more items than Stripe included in it. Use fetchItems() to get " .
                "them all."
            );
        }

        return $this->m_items;
    }


    /**
     * Check whether getItems() can be used: whether Stripe included all of the subscription's items in it.
     * @return bool
     */
    public function hasAllItems(): bool
    {
        return $this->m_hasAllItems;
    }


    /**
     * Ask Stripe for every one of the subscription's items, however many there are.
     * @return SubscriptionItemCollection
     * @throws ExceptionTooManyResults - never, as every item is asked for.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchItems(): SubscriptionItemCollection
    {
        return $this->m_client->listSubscriptionItems($this->getId(), limit: ResultLimit::createAll());
    }


    /**
     * Ask Stripe for the subscription's customer.
     * @return Customer
     * @throws ExceptionCustomerDeleted - if the customer has since been deleted.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchCustomer(): Customer
    {
        return $this->m_client->retrieveCustomer($this->m_customerId);
    }


    /**
     * Ask Stripe for the subscription's latest invoice.
     * @return Invoice
     * @throws ExceptionNoRelatedObject - if the subscription has no invoice yet. Check getLatestInvoiceId() first.
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchLatestInvoice(): Invoice
    {
        if ($this->m_latestInvoiceId === null)
        {
            throw new ExceptionNoRelatedObject("Subscription {$this->getId()} has no invoice yet.");
        }

        return $this->m_client->retrieveInvoice($this->m_latestInvoiceId);
    }


    /**
     * Ask Stripe for the subscription's default payment method.
     *
     * @return PaymentMethod
     *
     * @throws ExceptionNoRelatedObject - if the subscription has no default payment method of its own. Check
     * getDefaultPaymentMethodId() first.
     *
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchDefaultPaymentMethod(): PaymentMethod
    {
        if ($this->m_defaultPaymentMethodId === null)
        {
            throw new ExceptionNoRelatedObject("Subscription {$this->getId()} has no default payment method.");
        }

        return $this->m_client->retrievePaymentMethod($this->m_defaultPaymentMethodId);
    }


    /**
     * Ask Stripe for a preview of the subscription's next invoice, as it stands.
     * @return InvoicePreview
     * @throws ApiErrorException - if Stripe can't be reached, or refuses the request.
     */
    public function fetchUpcomingInvoicePreview(): InvoicePreview
    {
        return $this->m_client->previewUpcomingInvoice($this->getId());
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have.
     * @return \Stripe\Subscription
     */
    public function getStripeObject(): \Stripe\Subscription
    {
        return $this->rebuildStripeObject(\Stripe\Subscription::class);
    }


    protected static function getObjectType(): string
    {
        return 'subscription';
    }


    # Accessors
    public function getCreated(): \DateTimeImmutable { return $this->m_created; }
    public function getCustomerId(): string { return $this->m_customerId; }
    public function getStatus(): string { return $this->m_status; }
    public function getStartDate(): \DateTimeImmutable { return $this->m_startDate; }
    public function isCancelAtPeriodEnd(): bool { return $this->m_cancelAtPeriodEnd; }
    public function getCancelAt(): ?\DateTimeImmutable { return $this->m_cancelAt; }
    public function getCanceledAt(): ?\DateTimeImmutable { return $this->m_canceledAt; }
    public function getEndedAt(): ?\DateTimeImmutable { return $this->m_endedAt; }
    public function getTrialStart(): ?\DateTimeImmutable { return $this->m_trialStart; }
    public function getTrialEnd(): ?\DateTimeImmutable { return $this->m_trialEnd; }
    public function getCurrency(): string { return $this->m_currency; }
    public function getCollectionMethod(): string { return $this->m_collectionMethod; }
    public function getDaysUntilDue(): ?int { return $this->m_daysUntilDue; }
    public function getDefaultPaymentMethodId(): ?string { return $this->m_defaultPaymentMethodId; }
    public function getLatestInvoiceId(): ?string { return $this->m_latestInvoiceId; }
    public function getDescription(): ?string { return $this->m_description; }
    public function getMetadata(): Metadata { return $this->m_metadata; }
    public function getCancellationComment(): ?string { return $this->m_cancellationComment; }
    public function getCancellationFeedback(): ?string { return $this->m_cancellationFeedback; }
    public function getCancellationReason(): ?string { return $this->m_cancellationReason; }
    public function isLivemode(): bool { return $this->m_livemode; }
    public function getTestClockId(): ?string { return $this->m_testClockId; }
}
