<?php

namespace DataTools\Expression;

use DataTools\Interfaces\RowColumnInterface;

final class Expression extends Expr implements BindRowColumnInterface
{
    /**
     * @var Expr[]
     */
    private $expressions;

    /**
     * Expression constructor.
     * @param Expr[] $expressions
     */
    public function __construct(Expr ...$expressions)
    {
        $this->expressions = $expressions;
    }

    protected function expand() : array
    {
        return array_reduce(
            $this->expressions,
            function($resolved, $expr) {
                return $expr($resolved);
            },
            []
        );
    }

    public function __toString()
    {
        return '('.$this->implode_recursive(' ', $this->expressions).')';
    }

    private function implode_recursive($glue, $multi)
    {
        return implode($glue, array_map(
            function($multi) use ($glue) {
                if(! is_array($multi)) return (string)$multi;

                return sprintf('(%s)', implode_recursive($glue, $multi));
            },
            $multi
        ));
    }

//    public function exprs() : array
//    {
//        return $this->expressions;
//    }

    public function bindContainer(RowColumnInterface $container): Expr
    {
        return new self(...array_map(
            function(Expr $expr) use ($container) {
                if($expr instanceof ColumnName) return $expr->at($container);
                if($expr instanceof Expression) return $expr->bindContainer($container);

                return $expr;
            },
            $this->expressions
        ));
    }
}