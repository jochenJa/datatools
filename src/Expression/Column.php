<?php

namespace DataTools\Expression;

final class Column extends Expr
{
    private $index;
    private $fetch;

    public function __construct($index, callable $fetch)
    {
        $this->index = $index;
        $this->fetch = $fetch;
    }

    protected function expand() : array { return [call_user_func($this->fetch)]; }

    function __toString()
    {
        return 'Column('.$this->index.')';
    }
}