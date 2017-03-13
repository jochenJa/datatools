<?php

namespace DataTools\Expression;

use DataTools\Interfaces\ContainerInterface;

interface BindRowColumnInterface
{
    public function bindContainer(ContainerInterface $container) : Expr;
}