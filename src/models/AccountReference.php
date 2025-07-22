<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

class AccountReference implements Arrayable
{
    /**
     * Creates a tax liability object. To create one of these, use one of the createXYZ static methods.
     * @param string|null $type
     * @param string $account
     */
    private function __construct(private readonly ?string $type, private readonly string $account,)
    {

    }


    /**
     * Specify that this account (the one creating the Stripe session), is liable for the taxes.
     * @return AccountReference
     */
    public static function createSelf() : AccountReference
    {
        return new AccountReference("self", null);
    }


    /**
     * Reference another related account that is liable for the taxes.
     * @param string $account
     * @return AccountReference
     */
    public static function createRelatedAccount(string $account) : AccountReference
    {
        return new AccountReference("account", $account);
    }


    public function toArray(): array
    {
        $arrayForm = [
            'type' => $this->type,
        ];

        if ($this->account !== null)
        {
            $arrayForm['account'] = $this->account;
        }

        return $arrayForm;
    }
}