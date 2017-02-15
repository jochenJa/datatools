<?php

namespace DataTools\Expression;

final class Method extends Expr
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var callable
     */
    private $functionObject;

    /**
     * Method constructor.
     * @param callable $functionObject
     * @param string $name
     */
    public function __construct(callable $functionObject, $name = '')
    {
        $this->name = $name;
        $this->functionObject = $functionObject;
    }

    /**
     * @param $resolved
     * @return array
     */
    protected function consume($resolved) : array
    {
        return (array)call_user_func_array($this->functionObject, $resolved);
    }

    public function __toString() { return sprintf('Func(%s)', $this->name); }
}