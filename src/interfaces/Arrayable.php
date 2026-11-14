<?php

/*
 * An object that can turn itself into the array form that Stripe's API takes.
 */

namespace Programster\Stripe\Interfaces;


interface Arrayable
{
    /**
     * Get the object in the array form that Stripe's API takes.
     * @return array<string, mixed>
     */
    public function toArray() : array;
}
