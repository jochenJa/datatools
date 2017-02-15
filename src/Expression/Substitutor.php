<?php

namespace DataTools\Expression;

class Substitutor implements SubstitutorInterface
{
    /**
     * @var string
     */
    private $pattern;
    /**
     * @var callable
     */
    private $creator;

    /**
     * Substitutor constructor.
     * @param string $pattern
     * @param callable $creator
     */
    public function __construct(string $pattern, callable $creator)
    {
        $this->pattern = $pattern;
        $this->creator = $creator;
    }

    /**
     * @param $expression
     * @return array
     */
    public function substitute($expression) : array
    {
        $ref = preg_split($this->pattern, $expression, -1, PREG_SPLIT_DELIM_CAPTURE);

        array_walk(
            $ref,
            function(&$match, $pos) {
                $match = ($pos % 2) ? call_user_func($this->creator, $match) : $match;
            }
        );

        return array_filter($ref);
    }

    /**
     * @param $expression
     * @return array
     */
    public function __invoke($expression) : array
    {
       return $this->substitute($expression);
    }
}