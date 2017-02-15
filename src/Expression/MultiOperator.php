<?php

namespace DataTools\Expression;

final class MultiOperator implements OperatorInterface
{
    public function is_operand($operator) : bool
    {
        return $operator instanceof Product;
    }

    public function handle(string $operator, $left, $right) : array
    {
        switch($operator) {
            case '%':
                return $left instanceof Constant
                    ? [new Product(), $this->percent($left), $right]
                    : [new Product(), new Constant(0.01), $left, $right]
                ;

            case '*':
                return [new Product(), $left, $right];

            default:
                return [$left, $operator, $right];
        }
    }

    private function percent(Constant $c)
    {
        return new Constant($c->expand()[0]/100);
    }

    public function detect(string $expr) : bool
    {
        return strpos('%*', $expr) !== false;
    }

    public function merge($parent, $sub) : array
    {
        return array_merge($parent, array_slice($sub, 1));
    }
}