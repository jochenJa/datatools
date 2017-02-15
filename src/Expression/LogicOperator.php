<?php

namespace DataTools\Expression;

final class LogicOperator implements OperatorInterface
{
    function detect(string $logic) : bool
    {
        return preg_match('/[<>=]{2,2}/i', $logic) > 0;
    }

    function handle(string $expr, $left, $right) : array
    {
        $logic = function(string $type, callable $call) use ($left, $right) {
            return [new Logic($type, $call), $left, $right];
        };

        switch($expr) {
            case '==': return $logic('==', function($a, $b) { return $a == $b; });
            case '<>': return $logic('<>', function($a, $b) { return $a != $b; });
            case '<<': return $logic('<<', function($a, $b) { return $a < $b; });
            case '=<':
            case '<=': return $logic('<=', function($a, $b) { return $a <= $b; });
            case '>>': return $logic('>>', function($a, $b) { return $a > $b; });
            case '>=':
            case '=>': return $logic('>=', function($a, $b) { return $a >= $b; });
            default: return [$left, $expr, $right];
        }
    }

    public function is_operand($expr) : bool
    {
        return $expr instanceof Logic;
    }

    public function merge($parent, $sub) : array
    {
        $logic = array_shift($parent);
        $logic->merge(array_shift($sub));

        return array_merge([$logic], $parent, $sub);
    }
}