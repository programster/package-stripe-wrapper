<?php

/*
 * A simple list of strings, e.g. of tax rate IDs or image URLs. Each element is stored as a string, in the order
 * given, with no keys.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Interfaces\Arrayable;


/**
 * @extends AbstractReadonlyCollection<string>
 */
final readonly class StringCollection extends AbstractReadonlyCollection implements Arrayable
{
    /**
     * @param string ...$elements - the strings.
     */
    public function __construct(string ...$elements)
    {
        parent::__construct(...$elements);
    }
}
