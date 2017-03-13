<?php

namespace DataTools\Expression;

use DataTools\Interfaces\ContainerInterface;

final class FieldExpression extends Expr implements BindRowColumnInterface
{
    /**
     * @var string
     */
    private $fieldName;
    /**
     * @var Expression
     */
    private $expr;

    public function __construct(string $fieldName, Expression $expr)
    {

        $this->fieldName = $fieldName;
        $this->expr = $expr;
    }

    public function field() { return $this->fieldName; }

    protected function expand() : array
    {
        return $this->expr->expand();
    }

    public function __toString()
    {
        return $this->fieldName . $this->expr;
    }

    public function bindContainer(ContainerInterface $container): Expr
    {
        return new self($this->fieldName, $this->expr->bindContainer($container));
    }
}