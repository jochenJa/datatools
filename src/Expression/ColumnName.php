<?php

namespace DataTools\Expression;

use DataTools\Interfaces\RowColumnInterface;

final class ColumnName extends Expr
{
    /**
     * @var
     */
    private $name;

    /**
     * ColumnName constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function at(RowColumnInterface $container) : Column
    {
        $index = $container->column($this->name);

        return new Column(
            $index,
            function(int $index) use ($container) { return $container->at($index); }
        );
    }

    public function expand() : array { throw new \Exception('Column name should be replaced by index.'); }

    function __toString()
    {
        return 'Column('.$this->name.')';
    }
}