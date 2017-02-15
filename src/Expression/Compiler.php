<?php

namespace DataTools\Expression;

class Compiler
{
    /**
     * @var SubstitutorInterface[]
     */
    private $subs;
    /**
     * @var OperatorInterface[]
     */
    private $operands;
    private $rightOperands;

    /**
     * Compiler constructor.
     * @param SubstitutorInterface[] $subs
     * @param OperatorInterface[] $rightOperands
     * @param OperatorInterface[] $operands
     */
    public function __construct($subs, $rightOperands, $operands)
    {
        $this->subs = (array)$subs;
        $this->operands = (array)$operands;
        $this->rightOperands = (array)$rightOperands;
    }

    public static function build(SubstitutorInterface ...$subs)
    {
        return new self(
            array_merge(
                [
                    new Substitutor('/\((.+)\)/', function($subexpr) { return [$subexpr]; }),
                    new Substitutor('/\[([a-z_0-9]+)\]/i', function($column) { return new ColumnName($column); })
                ],
                $subs,
                [
                    new Substitutor('/([0-9.]+)/', function($number) { return new Constant($number+0); }),
                ]
            ),
            [   new DiffOperator()  ],
            [
                new MultiOperator(),
                new SumOperator(),
                new LogicOperator()
            ]
        );
    }

    /**
     * @return Expression
     */
    public function compile($expression)
    {
        return $this->pipe(
            $this->pipe(
                $this->pipe(
                    (array)$expression,
                    function($expression, SubstitutorInterface $substitutor) {
                        return $this->substitute($substitutor, $expression);
                    },
                    $this->subs
                ),
                function($expression, OperatorInterface $operator) {
                    return $this->resolveRight($operator, $expression);
                },
                $this->rightOperands
            ),
            function($expression, OperatorInterface $operator) {
                return $this->resolve($operator, $expression);
            },
            $this->operands
        );
    }

    /**
     * @param SubstitutorInterface $substitutor
     * @param $exprs
     * @return mixed
     */
    private function substitute(SubstitutorInterface $substitutor, $exprs)
    {
        if (is_object($exprs))
        {
            return $exprs;
        }
        if (is_string($exprs))
        {
            return $substitutor($exprs);
        }

        return array_reduce(
            $exprs,
            function ($exprs, $expr) use ($substitutor)
            {
                if (is_string($expr))
                {
                    return array_merge($exprs, $substitutor($expr));
                }

                $exprs[] = is_object($expr)
                    ? $expr
                    : $this->substitute($substitutor, $expr);

                return $exprs;
            },
            []
        );
    }

    /**
     * @param OperatorInterface $operator
     * @param $expression
     * @param array $resolved
     * @return array
     * @throws \Exception
     */
    private function resolve(OperatorInterface $operator, $expression, $resolved = [])
    {
        if(empty($expression)) return $resolved;

        $handle = function($expr) use($operator)
        {
            if(is_array($expr)) {
                $set = $this->resolve($operator, $expr);

                return count($set) === 1 ? reset($set) : $set;
            }

            return $expr;
        };

        $flatten = function(Expr $expr, ...$args) use ($operator) : array
            {
                return array_reduce(
                    $args,
                    function($parent, $sub) use ($operator)
                    {
                        if(is_array($sub) && $operator->is_operand(reset($sub)))  return $operator->merge($parent, $sub);

                        array_push($parent, $sub);

                        return $parent;
                    },
                    [$expr]
                );
            };

        $expr = array_pop($expression);
        if(is_string($expr) && $operator->detect($expr)) {
            if(empty($resolved) || empty($expression)) throw new \Exception('invalid expression');

            //echo $handle(end($expression)), $expr, implode_recursive('',$resolved),PHP_EOL;
            array_unshift($resolved, $flatten(...$operator->handle(
                $expr,
                $handle(array_pop($expression)),
                array_shift($resolved)
            )));
        } else {
            $resolved = array_merge([$handle($expr)], $resolved);
        }

        return $this->resolve($operator, $expression, $resolved);
    }

    /**
     * @param $start
     * @param callable $call
     * @param $stream
     * @return mixed
     */
    private function pipe($start, callable $call, $stream)
    {
        return array_reduce(
            $stream,
            function($return, $argument) use ($call) {
                return $call($return, $argument);
            },
            $start
        );
    }

    private function errors($expr, $errors = [])
    {
        return array_reduce(
            $expr,
            function($errors, $expr) {
                if(is_string($expr)) return array_merge($errors, [$expr]);
                if(is_array($expr)) return $this->errors($expr, $errors);

                return $errors;
            },
            $errors
        );
    }

    public function order($exprs)
    {
        return array_reduce(
            $exprs,
            function($ordered, $expr) {
                return array_merge(
                    is_array($expr) ? [new Expression(...$this->order($expr))] : [$expr], $ordered);
            },
            []
        );
    }

    public function expression($expressionString) : Expression
    {
        $exprs = $this->compile($expressionString);
        if(! empty($err = $this->errors($exprs))) {
            throw new \Exception(sprintf('%s => %s', $this->implode_recursive(' | ', $exprs), implode(' | ', $err)));
        }

        return new Expression(...$this->order($exprs));
    }

    private function resolveRight(OperatorInterface $operator, $expression, $resolved = [])
    {
        if(empty($expression)) return $resolved;

        $handle = function($expr) use( $operator)
        {
            if(is_array($expr)) return $this->resolveRight($operator, $expr);

            return $expr;
        };

        $expr = array_shift($expression);
        if(is_string($expr) && $operator->detect($expr)) {
            if(empty($expression)) throw new \Exception('invalid expression');

            $resolved = array_merge($resolved, $operator->handle($expr, '', $handle(array_shift($expression))));
        } else {
            $resolved = array_merge($resolved, [$handle($expr)]);
        }

        return $this->resolveRight($operator, $expression, $resolved);
    }

    private function implode_recursive($glue, $multi)
    {
        return implode($glue, array_map(
            function($multi) use ($glue) {
                if(! is_array($multi)) return (string)$multi;

                return sprintf('(%s)', $this->implode_recursive($glue, $multi));
            },
            (array)$multi
        ));
    }
}
