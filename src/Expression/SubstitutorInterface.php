<?php

namespace DataTools\Expression;

interface SubstitutorInterface
{
    public function substitute($expression) : array;
    public function __invoke($expression) : array;
}