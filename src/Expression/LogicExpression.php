<?php

namespace DataTools\Expression;

final class LogicExpression extends Expr
{
    private $expression;

    /**
     * Expression constructor.
     * @param Expr[] $expressions
     */
    public function __construct(Expr ...$expressions)
    {
        $this->expression = new Expression(...$expressions);
    }

    protected function expand() : array
    {
        return [array_reduce(
            $this->expression->expand(),
            function($bool, $sub) { return ($bool && $sub); },
            true
        )];
    }

    public function __toString()
    {
        return (string)$this->expression;
    }
}