<?php

namespace DataTools\Expression;

class DiffOperator implements OperatorInterface
{
    public function detect(string $expr) : bool
    {
        return strpos('-', $expr) !== false;
    }

    public function handle(string $operator, $left, $right) : array
    {
        return ['+', $this->negative($right)];
    }

    private function negative($expr)
    {
        if(is_array($expr) || $expr instanceof ColumnName)
            return [new Constant(-1), '*', $expr];
        if($expr instanceof Negate)
            return $expr->straight();
        if($expr instanceof Column || $expr instanceof Constant)
            return new Negate($expr);

        return $expr;
    }

    public function is_operand($expr) : bool
    {
        return false;
    }

    public function merge($parent, $sub) : array
    {
        return ($parent[] = $sub);
    }
}