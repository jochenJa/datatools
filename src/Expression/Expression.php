<?php

namespace DataTools\Expression;

final class Expression extends Expr implements ExpressionInterface
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

    public function exprs() : array
    {
        return $this->expressions;
    }
}