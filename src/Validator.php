<?php

namespace DataTools;

use DataTools\Expression\LogicExpression;
use DataTools\Interfaces\ContainerInterface;

final class Validator
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var LogicExpression[]
     */
    private $rules;

    /**
     * Mapper constructor.
     * @param ContainerInterface $container
     * @param LogicExpression[] $rules
     */
    public function __construct(ContainerInterface $container, LogicExpression ...$rules)
    {
        $this->container = $container;
        $this->rules = array_map(
            function(LogicExpression $rule) { return $rule->bindContainer($this->container); },
            $rules
        );
    }

    public function validate($row)
    {
        if(! $this->container->validate($row)) throw new \Exception('Row isnt complete.');

        $this->container->setRow($row);

        return array_map(
            function(LogicExpression $expression) { return $expression()[0]; },
            $this->rules
        );
    }

    public function __invoke($row)
    {
        return $this->validate($row);
    }


}