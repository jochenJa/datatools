<?php

namespace DataTools\Expression;

use DataTools\Interfaces\ContainerInterface;

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

    public function at(ContainerInterface $container) : Column
    {
        return $container->link($this->name);
    }

    public function expand() : array { throw new \Exception('Column name should be replaced by index.'); }

    function __toString()
    {
        return 'Column('.$this->name.')';
    }
}