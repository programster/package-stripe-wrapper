<?php

/*
 * A readonly collection of Charge response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<Charge>
 */
final readonly class ChargeCollection extends AbstractReadonlyCollection
{
    public function __construct(Charge ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return Charge::class; }
}
