<?php

namespace DataTools;

use DataTools\Expression\ColumnName;
use DataTools\Expression\Expr;
use DataTools\Expression\Expression;
use DataTools\Expression\FieldExpression;
use DataTools\Interfaces\RowColumnInterface;

final class Mapper
{
    /**
     * @var RowColumnInterface
     */
    private $container;
    private $mappedBy;

    /**
     * Mapper constructor.
     * @param RowColumnInterface $container
     * @param FieldExpression[] $mappedBy
     */
    public function __construct(RowColumnInterface $container, FieldExpression ...$mappedBy)
    {
        $this->container = $container;
        $this->mappedBy = array_map(
            function(FieldExpression $expr) {
                return new FieldExpression(
                    $expr->field(),
                    new Expression(...$this->bindContainer($expr->exprs()))
                );
            },
            $mappedBy
        );
    }

    public function map($row)
    {
        if(! $this->container->validate($row)) throw new \Exception('Row isnt complete.');

        $this->container->setRow($row);

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

    public function bindContainer($exprs)
    {
        return array_map(
            function(Expr $expr) {
                if($expr instanceof ColumnName) return $expr->at($this->container);
                if($expr instanceof Expression) return new Expression(...$this->bindContainer($expr->exprs()));

                return $expr;
            },
            $exprs
        );
    }
}