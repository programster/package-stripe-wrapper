<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\AccountReferenceType;
use Programster\Stripe\Interfaces\Arrayable;

/**
 * @phpstan-type AccountReferenceArray array{type: string, account?: string}
 */
final readonly class AccountReference implements Arrayable
{
    private readonly AccountReferenceType $m_type;
    private readonly ?string $m_account;


    /**
     * Create an account reference. This is private, so use one of the static create methods.
     * @param AccountReferenceType $type - whether this refers to this account, or another one.
     * @param string|null $account - the ID of the other account, or null for this account.
     */
    private function __construct(AccountReferenceType $type, ?string $account)
    {
        $this->m_type = $type;
        $this->m_account = $account;
    }


    /**
     * Reference this account (the one making the request to Stripe), e.g. as the one liable for the taxes.
     * @return AccountReference
     */
    public static function createSelf() : AccountReference
    {
        return new AccountReference(AccountReferenceType::SELF, null);
    }


    /**
     * Reference another, related account, e.g. as the one liable for the taxes.
     * @param string $account - the ID of the other Stripe account.
     * @return AccountReference
     */
    public static function createRelatedAccount(string $account) : AccountReference
    {
        return new AccountReference(AccountReferenceType::ACCOUNT, $account);
    }


    /**
     * @return AccountReferenceArray
     */
    public function toArray() : array
    {
        $arrayForm = [
            'type' => $this->m_type->value,
        ];

        if ($this->m_account !== null)
        {
            $arrayForm['account'] = $this->m_account;
        }

        return $arrayForm;
    }
}
