<?php

namespace DataTools\Expression;

final class SumOperator implements OperatorInterface
{
    public function is_operand($operator) : bool
    {
        return is_object($operator) && $operator instanceof Sum;
    }

    public function handle(string $operator, $left, $right) : array
    {
        switch($operator) {
            case '+':
                return [new Sum(), $left, $right];

            default:
                return [$left, $operator, $right];
        }
    }

    public function detect(string $expr) : bool
    {
        return strpos('+', $expr) !== false;
    }

    public function merge($parent, $sub) : array
    {
        return array_merge($parent, array_slice($sub, 1));
    }
}