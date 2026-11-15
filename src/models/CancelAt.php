<?php

/*
 * When a subscription created with createSubscription() is set to cancel. Create one with one of the static creators.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-cancel_at
 */

namespace Programster\Stripe\Models;


/**
 * @phpstan-type CancelAtParams array{cancel_at_period_end: true}|array{cancel_at: int|string}
 */
final readonly class CancelAt
{
    /** @var CancelAtParams */
    private array $m_stripeParams;


    /**
     * This is private, so use one of the static creators.
     * @param CancelAtParams $stripeParams - the subscription's parameters for when it cancels.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * Cancel at the end of the current billing period.
     * @return CancelAt
     */
    public static function createAtPeriodEnd() : CancelAt
    {
        return new CancelAt(['cancel_at_period_end' => true]);
    }


    /**
     * Cancel at a given time. It isn't checked against the current time, as a subscription on a test clock goes by
     * the clock's time instead.
     * @param \DateTimeImmutable $time - when to cancel.
     * @return CancelAt
     */
    public static function createAt(\DateTimeImmutable $time) : CancelAt
    {
        return new CancelAt(['cancel_at' => $time->getTimestamp()]);
    }


    /**
     * Cancel at the latest end of the subscription's items' current billing periods.
     * @return CancelAt
     */
    public static function createAtLongestItemPeriodEnd() : CancelAt
    {
        return new CancelAt(['cancel_at' => 'max_period_end']);
    }


    /**
     * Cancel at the earliest end of the subscription's items' current billing periods.
     * @return CancelAt
     */
    public static function createAtShortestItemPeriodEnd() : CancelAt
    {
        return new CancelAt(['cancel_at' => 'min_period_end']);
    }


    /**
     * Get the parameters to add to a subscription for when it cancels.
     * @return CancelAtParams
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
