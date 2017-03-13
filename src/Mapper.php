<?php

namespace DataTools;

use DataTools\Expression\ColumnName;
use DataTools\Expression\Expr;
use DataTools\Expression\Expression;
use DataTools\Expression\FieldExpression;
use DataTools\Interfaces\ContainerInterface;

final class Mapper
{
    /**
     * @var ContainerInterface
     */
    private $container;
    private $mappedBy;

    /**
     * Mapper constructor.
     * @param ContainerInterface $container
     * @param FieldExpression[] $mappedBy
     */
    public function __construct(ContainerInterface $container, FieldExpression ...$mappedBy)
    {
        $this->container = $container;
        $this->mappedBy = array_map(
            function(FieldExpression $expr) { return $expr->bindContainer($this->container); },
            $mappedBy
        );
    }

    public function map($row)
    {
        if(! $this->container->workOn($row)) throw new \Exception('Row isnt complete.');

        return array_reduce(
            $this->mappedBy,
            function($calculated, FieldExpression $expression) {
                $calculated[$expression->field()] = $expression()[0];

                return $calculated;
            },
            []
        );
    }

    public function __invoke($row)
    {
        return $this->map($row);
    }
}