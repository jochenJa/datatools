<?php

namespace DataTools\Expression;

final class Sum extends Expr
{
    /**
     * @param $resolved
     * @return array
     */
    protected function consume($resolved) : array {
        return [array_sum($resolved)];
    }

    function __toString()
    {
        return 'Sum';
    }
}