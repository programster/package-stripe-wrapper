<?php

/*
 * How many results a list method should get: up to a number, or all of them. Without one, a list method gets up to
 * its safety cap (ResultLimit::DEFAULT_SAFETY_CAP), and throws an ExceptionTooManyResults if more match, rather than
 * quietly giving back only some of them.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;


final readonly class ResultLimit
{
    # The most results a list method gets when it isn't given a ResultLimit. More than this throws, as getting them all
    # could take many requests, and use a lot of memory.
    public const DEFAULT_SAFETY_CAP = 1000;

    private ?int $m_maximum;


    /**
     * Create a limit. Use one of the static creators.
     * @param int|null $maximum - the most results to get, or null for all of them.
     */
    private function __construct(?int $maximum)
    {
        $this->m_maximum = $maximum;
    }


    /**
     * Get up to a number of results. If more match, only the first that many are given back, without an exception.
     * @param int $maximum - the most results to get, which must be at least 1.
     * @return ResultLimit
     * @throws ExceptionInvalidValue - if the maximum is less than 1.
     */
    public static function upTo(int $maximum): ResultLimit
    {
        if ($maximum < 1)
        {
            throw new ExceptionInvalidValue("A result limit must be at least 1, but {$maximum} was given.");
        }

        return new ResultLimit($maximum);
    }


    /**
     * Get every result, however many there are. This could take many requests, and use a lot of memory, so only use
     * it when the filters are known to keep the number of results small enough.
     * @return ResultLimit
     */
    public static function all(): ResultLimit
    {
        return new ResultLimit(null);
    }


    /**
     * Check whether this limit gets every result.
     * @return bool
     */
    public function isUnlimited(): bool
    {
        return $this->m_maximum === null;
    }


    /**
     * Get the most results to get.
     * @return int
     * @throws \LogicException - if this limit gets every result, so has no maximum. Check isUnlimited() first.
     */
    public function getMaximum(): int
    {
        if ($this->m_maximum === null)
        {
            throw new \LogicException("This result limit gets every result, so has no maximum.");
        }

        return $this->m_maximum;
    }
}
