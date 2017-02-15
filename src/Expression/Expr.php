<?php

namespace DataTools\Expression;

abstract class Expr
{
    /**
     * @param $resolved
     * @return array
     */
    protected function consume($resolved) : array { return $resolved; }

    /**
     * @return array
     */
    protected function expand() : array { return []; }

    /**
     * @param $resolved
     * @return array
     */
    public function __invoke($resolved = [])
    {
        return array_merge($this->expand(), $this->consume($resolved));
    }

    abstract function __toString();
}