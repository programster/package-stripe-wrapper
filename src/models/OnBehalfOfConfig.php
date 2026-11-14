<?php

/*
 * Never worked: it was an unfinished copy of AccountReference, and nothing in this package uses it.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

/**
 * @deprecated This class never worked, as it can't be created, and nothing in this package uses it. It will be
 * removed in the next major version. Use AccountReference, or the on-behalf-of parameters of the methods that take
 * them (e.g. SubscriptionData's $onBehalfOfAccountId), instead.
 */
readonly class OnBehalfOfConfig implements Arrayable
{
    private float $m_applicationFeePercent;
    private string $m_onBehalfOfAccountId;


    /**
     * Never called, as both static create methods throw.
     * @param float $applicationFeePercent - the percentage of the payment to take as an application fee.
     * @param string $onBehalfOfAccountId - the ID of the account the payment is on behalf of.
     */
    private function __construct(float $applicationFeePercent, string $onBehalfOfAccountId)
    {
        $this->m_applicationFeePercent = $applicationFeePercent;
        $this->m_onBehalfOfAccountId = $onBehalfOfAccountId;
    }


    /**
     * @deprecated This never worked, and always throws. Use AccountReference::createSelf() instead.
     * @return never
     * @throws \LogicException - always.
     */
    public static function createSelf() : never
    {
        throw new \LogicException(
            "OnBehalfOfConfig never worked, and will be removed. Use AccountReference::createSelf() instead."
        );
    }


    /**
     * @deprecated This never worked, and always throws. Use AccountReference::createRelatedAccount() instead.
     * @param string $account - the ID of the other account.
     * @return never
     * @throws \LogicException - always.
     */
    public static function createRelatedAccount(string $account) : never
    {
        throw new \LogicException(
            "OnBehalfOfConfig never worked, and will be removed. Use " .
            "AccountReference::createRelatedAccount('{$account}') instead."
        );
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'application_fee_percent' => $this->m_applicationFeePercent,
            'on_behalf_of' => $this->m_onBehalfOfAccountId,
        ];
    }
}
