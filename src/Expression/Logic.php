<?php

namespace DataTools\Expression;

class Logic extends Expr
{
    /**
     * @var string
     */
    private $types;
    /**
     * @var callable
     */
    private $logic;

    /**
     * Logic constructor.
     * @param string $type
     * @param callable $logic
     */
    public function __construct(string $type, callable $logic)
    {
        $this->types = (array)$type;
        $this->logic = (array)$logic;
    }

    public function consume($resolved) : array
    {
        return array_merge(array_reduce(
            $this->logic,
            function($result, $logic) use(&$resolved)
            {
                if(count($resolved) >= 2) {
                    $result[] = $logic(array_shift($resolved), array_shift($resolved));
                }

                return $result;
            },
            []
        ), $resolved);
    }

    function __toString()
    {
        return 'logic('.implode('|',$this->types).')';
    }

    function merge(Logic $logic)
    {
        array_push($this->logic, ...$logic->logic);
        array_push($this->types, ...$logic->types);
    }
}