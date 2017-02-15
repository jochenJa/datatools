<?php

namespace DataTools\Expression;

class Constant extends Expr
{
    private $number;

    /**
     * Constant constructor.
     * @param int|float $number
     */
    public function __construct($number)
    {
        $this->number = $number;
    }

    /**
     * @return array
     */
    public function expand() : array
    {
       return [$this->number];
    }

    function __toString()
    {
        return sprintf("Constant(%".(is_int($this->number) ? 'd' : 'f').")", $this->number);
    }
}