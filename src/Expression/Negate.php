<?php

namespace DataTools\Expression;

class Negate extends Expr
{
    /**
     * @var Expr
     */
    private $straight;

    /**
     * Negate constructor.
     * @param $straight
     */
    public function __construct(Expr $straight)
    {
        $this->straight = $straight;
    }

    public function straight() : Expr { return $this->straight; }

    public function expand() : array
    {
        return [-1 * $this->straight->expand()[0]];
    }

    function __toString()
    {
        return sprintf('Negate%s', (string)$this->straight);
    }
}