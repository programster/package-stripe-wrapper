<?php

/*
 * What a subscription created with createSubscription() anchors its billing cycle to: a time, or a day of the month.
 * Create one with at() or createOnDayOfMonth().
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;


/**
 * @phpstan-type AnchorConfigArray array{day_of_month: int, month?: int, hour?: int, minute?: int, second?: int}
 * @phpstan-type BillingCycleAnchorParams array{billing_cycle_anchor: int}
 *     |array{billing_cycle_anchor_config: AnchorConfigArray}
 */
final readonly class BillingCycleAnchor
{
    /** @var BillingCycleAnchorParams */
    private array $m_stripeParams;


    /**
     * This is private, so use at() or createOnDayOfMonth().
     * @param BillingCycleAnchorParams $stripeParams - the subscription's parameters for the anchor.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * Anchor the billing cycle to a time, so each period starts at the same point after it. It isn't checked against
     * the current time, as a subscription on a test clock goes by the clock's time instead.
     * @param \DateTimeImmutable $time - the time to anchor to.
     * @return BillingCycleAnchor
     */
    public static function createAt(\DateTimeImmutable $time) : BillingCycleAnchor
    {
        return new BillingCycleAnchor(['billing_cycle_anchor' => $time->getTimestamp()]);
    }


    /**
     * Anchor the billing cycle to the next occurrence of a day of the month (and optionally month and time, in UTC).
     * This is only valid with monthly and yearly prices.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-billing_cycle_anchor_config
     *
     * @param int $dayOfMonth - the day of the month, from 1 to 31. In a shorter month, the last day is used.
     *
     * @param int|null $month - optionally, the month to start full cycle billing periods, from 1 to 12.
     *
     * @param int|null $hour - optionally, the hour of the day, from 0 to 23.
     *
     * @param int|null $minute - optionally, the minute of the hour, from 0 to 59.
     *
     * @param int|null $second - optionally, the second of the minute, from 0 to 59.
     *
     * @return BillingCycleAnchor
     *
     * @throws ExceptionInvalidValue - if any value is out of its range.
     */
    public static function createOnDayOfMonth(
        int  $dayOfMonth,
        ?int $month = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
    ) : BillingCycleAnchor
    {
        self::checkRange('Day of month', $dayOfMonth, 1, 31);
        $config = ['day_of_month' => $dayOfMonth];

        if ($month !== null)
        {
            self::checkRange('Month', $month, 1, 12);
            $config['month'] = $month;
        }

        if ($hour !== null)
        {
            self::checkRange('Hour', $hour, 0, 23);
            $config['hour'] = $hour;
        }

        if ($minute !== null)
        {
            self::checkRange('Minute', $minute, 0, 59);
            $config['minute'] = $minute;
        }

        if ($second !== null)
        {
            self::checkRange('Second', $second, 0, 59);
            $config['second'] = $second;
        }

        return new BillingCycleAnchor(['billing_cycle_anchor_config' => $config]);
    }


    /**
     * Get the parameters to add to a subscription for the anchor.
     * @return BillingCycleAnchorParams
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }


    /**
     * Check a value is within its range.
     * @param string $name - what the value is, for the error message, e.g. "Month".
     * @param int $value - the value.
     * @param int $min - the lowest it can be.
     * @param int $max - the highest it can be.
     * @return void
     * @throws ExceptionInvalidValue - if it's outside the range.
     */
    private static function checkRange(string $name, int $value, int $min, int $max) : void
    {
        if ($value < $min || $value > $max)
        {
            throw new ExceptionInvalidValue("{$name} must be between {$min} and {$max}, but {$value} was given.");
        }
    }
}
