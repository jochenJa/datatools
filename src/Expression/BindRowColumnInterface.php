<?php

namespace DataTools\Expression;

use DataTools\Interfaces\RowColumnInterface;

interface BindRowColumnInterface
{
    public function bindContainer(RowColumnInterface $container) : Expr;
}