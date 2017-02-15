<?php

namespace DataTools\Expression;

final class Product extends Expr
{
    /**
     * @param $resolved
     * @return array
     */
    protected function consume($resolved) : array {
        return [array_product($resolved)];
    }

    function __toString()
    {
        return 'Product';
    }
}