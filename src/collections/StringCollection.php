<?php

/*
 * A simple list of strings, e.g. of tax rate IDs or image URLs. Each element is stored as a string, in the order
 * given, with no keys.
 */

namespace Programster\Stripe\Collections;

use Programster\Stripe\Interfaces\Arrayable;


/**
 * @implements \IteratorAggregate<int, string>
 */
class StringCollection implements Arrayable, \Countable, \IteratorAggregate
{
    /** @var string[] */
    private array $m_elements = [];


    public function __construct(string|\Stringable ...$elements)
    {
        foreach ($elements as $element)
        {
            $this->m_elements[] = (string)$element;
        }
    }


    /**
     * Get the strings as a list.
     * @return string[]
     */
    public function toArray() : array
    {
        return $this->m_elements;
    }


    /**
     * Get the number of strings in the collection.
     * @return int
     */
    public function count() : int
    {
        return count($this->m_elements);
    }


    /**
     * Iterate over the strings, in the order they were given.
     * @return \ArrayIterator<int, string>
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->m_elements);
    }
}
