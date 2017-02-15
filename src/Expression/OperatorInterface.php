<?php

namespace DataTools\Expression;

interface OperatorInterface
{
    public function detect(string $expr) : bool;
    public function is_operand($expr) : bool;
    public function handle(string $operator, $left, $right) : array;
    public function merge($parent, $sub) : array;
}