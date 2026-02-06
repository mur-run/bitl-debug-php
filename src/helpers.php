<?php

use BitL\Debug\BitL;

if (! function_exists('bitl')) {
    /**
     * Get the BitL client instance.
     *
     * @return \BitL\Debug\Client
     */
    function bitl(): \BitL\Debug\Client
    {
        return BitL::client();
    }
}

if (! function_exists('bitl_dump')) {
    /**
     * Send a dump to BitL and return the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function bitl_dump(mixed $value): mixed
    {
        return BitL::dump($value);
    }
}

if (! function_exists('bitl_dd')) {
    /**
     * Send a dump to BitL and die.
     *
     * @param  mixed  ...$values
     * @return never
     */
    function bitl_dd(mixed ...$values): never
    {
        BitL::dd(...$values);
    }
}

if (! function_exists('bd')) {
    /**
     * Alias for bitl_dump() - BitL Dump.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function bd(mixed $value): mixed
    {
        return BitL::dump($value);
    }
}

if (! function_exists('bdd')) {
    /**
     * Alias for bitl_dd() - BitL Dump and Die.
     *
     * @param  mixed  ...$values
     * @return never
     */
    function bdd(mixed ...$values): never
    {
        BitL::dd(...$values);
    }
}
