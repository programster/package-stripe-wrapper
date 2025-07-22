<?php

/*
 * A basic class to enforce a collection of just string values. No indexes or other types for values.
 */

namespace Programster\Stripe\Collections;

use Programster\Stripe\Interfaces\Arrayable;

class StringCollection implements Arrayable
{
    private array $elements;


    public function __construct(string|\Stringable ...$elements)
    {
        foreach ($elements as $element)
        {
            $this->elements[] = (string)$element;
        }
    }

    public function toArray(): array
    {
        return $this->elements;
    }
}