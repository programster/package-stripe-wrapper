<?php

/*
 * The IDs of the tax rates that apply to a line item, and whether Stripe should pick between them based on the
 * customer's address (dynamic), or apply all of them.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-dynamic_tax_rates
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;


final readonly class TaxRates
{
    private bool $m_isDynamic;

    /** @var string[] */
    private array $m_taxRates;


    /**
     * @param bool $isDynamic - true for Stripe to pick the tax rate that matches the customer's address, or false to
     * apply all of them. Dynamic tax rates can only be used on checkout line items.
     *
     * @param string ...$taxRates - the IDs of the tax rates. At least one must be given.
     *
     * @throws ExceptionMissingRequiredParameter - if no tax rates are given.
     */
    public function __construct(bool $isDynamic, string ...$taxRates)
    {
        if (count($taxRates) === 0)
        {
            throw new ExceptionMissingRequiredParameter(
                "You need to provide at least one tax rate when creating a TaxRates object."
            );
        }

        $this->m_isDynamic = $isDynamic;
        $this->m_taxRates = array_values($taxRates);
    }


    # Accessors
    public function getIsDynamic() : bool { return $this->m_isDynamic; }
    public function getTaxRates() : array { return $this->m_taxRates; }
}
