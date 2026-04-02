<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Interfaces\Arrayable;

readonly class TaxRates
{
    private readonly array $taxRates;
    /**Arrayable
     *
     */
    public function __construct(
        private bool $isDynamic,
        string ...$taxRates
    )
    {
        if (count($taxRates) === 0)
        {
            throw new ExceptionMissingRequiredParameter("You need to provide at least one tax rate when creating a TaxRates object.");
        }

        $this->taxRates = $taxRates;
    }

    public function getIsDynamic(): bool { return $this->isDynamic; }
    public function getTaxRates(): array { return $this->taxRates; }
}