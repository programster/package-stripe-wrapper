<?php

/*
 * Lets the customer change the quantity of a line item during checkout, within limits.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-adjustable_quantity
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;

readonly class AdjustableQuantityConfig implements Arrayable
{
    private int $m_minimum;
    private int $m_maximum;


    /**
     * Let the customer adjust the quantity, between a minimum and a maximum.
     * @param int $minimum - the lowest quantity the customer can choose.
     * @param int $maximum - the highest quantity the customer can choose.
     * @throws ExceptionInvalidValue - if the minimum is negative, or more than the maximum.
     */
    public function __construct(int $minimum, int $maximum)
    {
        if ($minimum < 0 || $minimum > $maximum)
        {
            throw new ExceptionInvalidValue(
                "The minimum quantity ({$minimum}) must be at least 0, and no more than the maximum ({$maximum})."
            );
        }

        $this->m_minimum = $minimum;
        $this->m_maximum = $maximum;
    }


    public function toArray() : array
    {
        return [
            'enabled' => true,
            'minimum' => $this->m_minimum,
            'maximum' => $this->m_maximum,
        ];
    }
}
