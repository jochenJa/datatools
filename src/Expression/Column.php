<?php

namespace DataTools\Expression;

final class Column extends Expr
{
    private $index;
    private $fromRow;

    public function __construct($index, callable $fromRow)
    {
        $this->index = $index;
        $this->fromRow = $fromRow;
    }

    protected function expand() : array { return [call_user_func($this->fromRow, $this->index)]; }

    function __toString()
    {
        return 'Column('.$this->index.')';
    }
}