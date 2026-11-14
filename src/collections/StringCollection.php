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
     * @param string|\Stringable ...$elements - the strings. Anything Stringable is turned into a string.
     */
    public function __construct(string|\Stringable ...$elements)
    {
        $toString = function (string|\Stringable $element): string {
            return (string)$element;
        };

        parent::__construct(...array_map($toString, $elements));
    }


    public function getType(): string { return 'string'; }
}
