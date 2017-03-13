<?php

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Expression\
{
    ColumnName, Compiler, Constant, DiffOperator, Expression, LogicExpression, LogicOperator, MultiOperator, Product, Substitutor, Sum, Column, SumOperator
};
use DataTools\Interfaces\ContainerInterface;

class OperationStringTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function anExpressionIsResolved()
    {
        $container = new Row(array_flip(str_split('ABCDE')));

        $expression = new Expression(
            $container->columnAt('C'),
            $container->columnAt('A'),
            new Sum(),
            $container->columnAt('D'),
            $container->columnAt('B'),
            new Sum(),
            new Sum(),
            $container->columnAt('D')
        );


        $result = $this->process(
            $container,
            [1, 2, 3, 4],
            $expression
        );

        $this->assertEquals(4, $result[0]);
        $this->assertEquals(10, $result[1]);

        $result = $this->process(
            $container,
            [45, 35, 25, 15],
            $expression
        );

        $this->assertEquals(15, $result[0]);
        $this->assertEquals(120, $result[1]);
    }

    /**
     * @test
     */
    public function convertPartExpressionToExpr()
    {
        $expr = "[A]-[B]-20%([C]-[D])*[E]";
        $col = [10, 2, 3, 4, 5];
        $expected = $col[0] - $col[1] - (0.2 * ($col[2] - $col[3]) * $col[4]);

        $container = new Row(array_flip(str_split('ABCDE')));

        $brackets = new Compiler(
            [new Substitutor('/\((.+)\)/', function($subexpr) { return [$subexpr]; })],
            [],[]
        );

        $this->assertSame(
            "[A]-[B]-20% ([C]-[D]) *[E]",
            implode_recursive(' ', $expr = $brackets->compile($expr))
        );

        $columns = new Compiler(
            [new Substitutor(
                '/\[([a-z_]+)\]/i',
                function($column) { return new ColumnName($column); }
            )],
            [],[]
        );

        $this->assertSame(
            "Column(A) - Column(B) -20% (Column(C) - Column(D)) * Column(E)",
            implode_recursive(' ', $expr = $columns->compile($expr))
        );

        $constants = new Compiler(
            [new Substitutor(
                '/([0-9.]+)/',
                function($number) { return new Constant($number+0); }
            )],[],[]);

        $this->assertSame(
            "Column(A) - Column(B) - Constant(20) % (Column(C) - Column(D)) * Column(E)",
            implode_recursive(' ', $expr = $constants->compile($expr))
        );

        $negatives = new Compiler([],[new DiffOperator()],[]);

        $this->assertSame(
            "Column(A) + (Constant(-1) * Column(B)) + NegateConstant(20) % (Column(C) + (Constant(-1) * Column(D))) * Column(E)",
            implode_recursive(' ', $expr = $negatives->compile($expr))
        );

        $products = new Compiler([],[],[new MultiOperator()]);

        $this->assertSame(
            "Column(A) + (Product Constant(-1) Column(B)) + (Product Constant(0.010000) NegateConstant(20) (Column(C) + (Product Constant(-1) Column(D))) Column(E))",
            implode_recursive(' ', $expr = $products->compile($expr))
        );

        $sums = new Compiler([],[],[new SumOperator()]);

        $this->assertSame(
            "(Sum Column(A) (Product Constant(-1) Column(B)) (Product Constant(0.010000) NegateConstant(20) (Sum Column(C) (Product Constant(-1) Column(D))) Column(E)))",
            implode_recursive(' ', $expr = $sums->compile($expr))
        );

        $this->assertSame(
            "((Column(E) ((Column(D) Constant(-1) Product) Column(C) Sum) NegateConstant(20) Constant(0.010000) Product) (Column(B) Constant(-1) Product) Column(A) Sum)",
            implode_recursive(' ', $expr = $sums->order($expr))
        );

        $expr = (new Expression(...$expr))->bindContainer($container);

        $this->assertSame(
            "(((Column(4) ((Column(3) Constant(-1) Product) Column(2) Sum) NegateConstant(20) Constant(0.010000) Product) (Column(1) Constant(-1) Product) Column(0) Sum))",
            implode_recursive(' ', $expr)
        );

        $this->assertEquals($expected, $this->process($container, $col, $expr)[0]);
    }/**
     * @test
     */
    public function convertPartExpressionToLogicExpr()
    {
        $col = [10, 2, 3, 4, 5];
        $container = new Row(array_flip(str_split('ABCDE')));
        $compiler = new Compiler(
            [
                new Substitutor('/\((.+)\)/', function($subexpr) { return [$subexpr]; }),
                new Substitutor('/\[([a-z_0-9]+)\]/i', function($column) { return new ColumnName($column); }),
                new Substitutor('/([0-9.]+)/', function($number) { return new Constant($number+0); }),
            ],
            [   new DiffOperator()  ],
            [
                new MultiOperator(),
                new SumOperator()
            ]
    );

        $this->assertSame(
            "Constant(3) << (Sum Column(A) (Product Constant(-1) Column(B)) (Product Constant(0.010000) NegateConstant(20) (Sum Column(C) (Product Constant(-1) Column(D))) Column(E))) <= Constant(9)",
            implode_recursive(' ', $exprs = $compiler->compile("3<<[A]-[B]-20%([C]-[D])*[E]<=9"))
        );

        $this->assertSame(
            "(logic(<<|<=) Constant(3) (Sum Column(A) (Product Constant(-1) Column(B)) (Product Constant(0.010000) NegateConstant(20) (Sum Column(C) (Product Constant(-1) Column(D))) Column(E))) Constant(9))",
            implode_recursive(' ',  $exprs = (new Compiler([], [], [new LogicOperator()]))->compile($exprs))
        );

        $expr = (new LogicExpression(...$compiler->order($exprs)))->bindContainer($container);

        $this->assertTrue($this->process($container, $col, $expr)[0]);
    }
    
    /**
     * @test
     * @expectedException Exception
     */
    public function throwsExceptionWhenLeftOrRightAreEmpty()
    {
        (new Compiler([], [], [new MultiOperator()]))->compile("*");
    }

    /**
     * @test
     */
    public function bindContianerReplacesColumnNameWithColumn()
    {
        $container = new Row(array_flip(str_split('ABCDE')));

        $this->assertSame(
            '(Column(0))',
            (string)(new Expression(new ColumnName('A')))->bindContainer($container)
        );

        $this->assertSame(
            '(Column(4))',
            (string)(new Expression(new ColumnName('E')))->bindContainer($container)
        );

        $this->assertSame(
            '(Column(4) Column(2))',
            (string)(new Expression(new ColumnName('E'), new ColumnName('C')))->bindContainer($container)
        );

        $this->assertSame(
            '(Product)',
            (string)(new Expression(new Product()))->bindContainer($container)
        );

        $this->assertSame(
            '((Product Column(4) Column(2)) Column(0))',
            (string)(new Expression(new Expression(new Product(), new ColumnName('E'), new ColumnName('C')),new ColumnName('A')))->bindContainer($container)
        );
    }

    /**
     * @test
     */
    public function compilerMakesExpression()
    {
        $col = [10, 2, 3, 4, 5];
        $container = new Row(array_flip(str_split('ABCDE')));
        $compiler = Compiler::build();

        $expr = (new Expression(...$compiler->order($compiler->compile("[A]-[B]-20%([C]-[D])*[E]"))))
            ->bindContainer($container);

        $this->assertEquals(
            $col[0] - $col[1] - (0.2 * ($col[2] - $col[3]) * $col[4]),
            $this->process($container, $col, $expr)[0]
        );
    }

    /**
     * @test
     * @dataProvider multiSets
     */
    public function multi($multiSet, $expected)
    {
        echo PHP_EOL;

        $this->assertSame(
            $expected,
            implode_recursive(
                ' ',
                (new Compiler([], [], [new MultiOperator()]))->compile($multiSet)
            )
        );
    }

    /**
     * @return array
     */
    public function multiSets(): array
    {
        return [
            [
                [new Constant(3), '*', new Constant(2)],
                '(Product Constant(3) Constant(2))'
            ],
            [
                [new Constant(3), '*', new Constant(2), '*', new Constant(4)],
                '(Product Constant(3) Constant(2) Constant(4))'
            ],
            [
                [new Constant(3), '+', new Constant(2), '*', new Constant(4)],
                'Constant(3) + (Product Constant(2) Constant(4))'
            ],
            [
                [[new Constant(3), '+', new Constant(2)], '*', new Constant(4)],
                '(Product (Constant(3) + Constant(2)) Constant(4))'
            ],
            [
                [[new Constant(3), '*', new Constant(2)], new Constant(5), '*', new Constant(4)],
                '(Product Constant(3) Constant(2)) (Product Constant(5) Constant(4))'
            ],
            [
                [[new Constant(3), '*', new Constant(2)], '+', new Constant(5), '*', new Constant(4)],
                '(Product Constant(3) Constant(2)) + (Product Constant(5) Constant(4))'
            ],
            [
                [[new Constant(3), '*', new Constant(2)], '+', [new Constant(5), '*', new Constant(4)]],
                '(Product Constant(3) Constant(2)) + (Product Constant(5) Constant(4))'
            ],
            [
                [[new Constant(3), '*', new Constant(2)], '*', [new Constant(5), '*', new Constant(4)]],
                '(Product Constant(3) Constant(2) Constant(5) Constant(4))'
            ],
            [
                [[new Constant(3), '+', new Constant(2)], '*', [new Constant(5), '+', new Constant(4)]],
                '(Product (Constant(3) + Constant(2)) (Constant(5) + Constant(4)))'
            ],
            [
                [new Constant(2), '*', [new Constant(5), '+', new Constant(4)]],
                '(Product Constant(2) (Constant(5) + Constant(4)))'
            ],
            [
                [[[new Constant(3), '*', new Constant(2)], '*', new Constant(5)], '*', new Constant(4)],
                '(Product Constant(3) Constant(2) Constant(5) Constant(4))'
            ],
            [
                [new Constant(30), '%', new Constant(100)],
                '(Product Constant(0.300000) Constant(100))'
            ],
            [
                [new Constant(30), '%', new Constant(100) , '*', new Constant(4)],
                '(Product Constant(0.300000) Constant(100) Constant(4))'
            ],
            [
                [[new Constant(1), '+', new Constant(30)], '%', new Constant(100)],
                '(Product Constant(0.010000) (Constant(1) + Constant(30)) Constant(100))'
            ],
            [
                [new Constant(3), '*', [new Constant(2), '+', new Constant(4)]],
                '(Product Constant(3) (Constant(2) + Constant(4)))'
            ],
            [
                [new Constant(2), '+', new Constant(4)],
                'Constant(2) + Constant(4)'
            ],
            [
                [new Constant(3), '*', [new Constant(-1), '*', [new Constant(2), '*', new Constant(4)]]],
                '(Product Constant(3) Constant(-1) Constant(2) Constant(4))'
            ],
            [
                [new Constant(3), '*', [new Constant(-1), '*', [new Constant(2), '+', new Constant(4)]]],
                '(Product Constant(3) Constant(-1) (Constant(2) + Constant(4)))'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider diffSets
     */
    public function diff($set, $expected)
    {
        echo PHP_EOL;

        $this->assertSame(
            $expected,
            implode_recursive(
                ' ',
                (new Compiler([], [new DiffOperator()], []))->compile($set)
            )
        );
    }

    /**
     * @return array
     */
    public function diffSets(): array
    {
        return [
            [
                [new Constant(3), '-', new Constant(2)],
                'Constant(3) + NegateConstant(2)'
            ],
            [
                [new Constant(3), '-', new Constant(2), '-', new Constant(1)],
                'Constant(3) + NegateConstant(2) + NegateConstant(1)'
            ],
            [
                [new Constant(3), '-', [new Constant(2), '-', new Constant(1)]],
                'Constant(3) + (Constant(-1) * (Constant(2) + NegateConstant(1)))'
            ],
            [
                [[new Constant(3), '-', new Constant(2)], '-', new Constant(1)],
                '(Constant(3) + NegateConstant(2)) + NegateConstant(1)'
            ],
            [
                [[new Constant(3), '-', new Column(0, function() { return 1; })], '-', new Constant(1)],
                '(Constant(3) + NegateColumn(0)) + NegateConstant(1)'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sumSets
     */
    public function sum($set, $expected)
    {
        echo PHP_EOL;

        $this->assertSame(
            $expected,
            implode_recursive(
                ' ',
                (new Compiler([],[],[new SumOperator()]))->compile($set)
            )
        );
    }

    /**
     * @return array
     */
    public function sumSets(): array
    {
        return [
            [
                [new Constant(3), '+', new Constant(2)],
                '(Sum Constant(3) Constant(2))'
            ],
            [
                [new Constant(3), '+', new Constant(2), '+', new Constant(4) ],
                '(Sum Constant(3) Constant(2) Constant(4))'
            ],
            [
                [new Constant(3), '+', [new Constant(2), '+', new Constant(4)] ],
                '(Sum Constant(3) Constant(2) Constant(4))'
            ],
            [
                [[new Constant(3), '+', new Constant(2)], '+', new Constant(4)],
                '(Sum Constant(3) Constant(2) Constant(4))'
            ],
            [
                [[new Constant(3), '+', new Constant(2)], '*', new Constant(4)],
                '(Sum Constant(3) Constant(2)) * Constant(4)'
            ],
            [
                [new Constant(3), '+', [new Constant(2), '*', new Constant(4)]],
                '(Sum Constant(3) (Constant(2) * Constant(4)))'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider logicSets
     */
    public function logic($set, $expected)
    {
        echo PHP_EOL;

        $this->assertSame(
            $expected,
            implode_recursive(
                ' ',
                (new Compiler([],[],[new LogicOperator()]))->compile($set)
            )
        );
    }

    /**
     * @return array
     */
    public function logicSets(): array
    {
        return [
            [[new Constant(1), '==', new Constant(1)], '(logic(==) Constant(1) Constant(1))'],
            [[new Constant(1), '<=', new Constant(1)], '(logic(<=) Constant(1) Constant(1))'],
            [[new Constant(1), '=>', new Constant(1)], '(logic(>=) Constant(1) Constant(1))'],
            [[new Constant(1), '>=', new Constant(1)], '(logic(>=) Constant(1) Constant(1))'],
            [[new Constant(1), '=<', new Constant(1)], '(logic(<=) Constant(1) Constant(1))'],
            [[new Constant(1), '<>', new Constant(1)], '(logic(<>) Constant(1) Constant(1))'],
            [[new Constant(1), '>>', new Constant(1)], '(logic(>>) Constant(1) Constant(1))'],
            [[new Constant(1), '<<', new Constant(1)], '(logic(<<) Constant(1) Constant(1))'],
            [[new Constant(1), '<>', new Constant(2), '<>', new Constant(3)], '(logic(<>|<>) Constant(1) Constant(2) Constant(3))'],
            [[new Constant(1), '<>', new Constant(2), '==', new Constant(3)], '(logic(<>|==) Constant(1) Constant(2) Constant(3))'],
            [[new Constant(1), '>>', new Constant(2), '>=', new Constant(3)], '(logic(>>|>=) Constant(1) Constant(2) Constant(3))'],
        ];
    }

    private function process(Row $container, $row, $expression)
    {
        if(! $container->validate($row)) throw new \Exception('Row doesnt contain all required indices.');
        $container->setRow($row);

        return $expression();
    }
}

function implode_recursive($glue, $multi)
{
    return implode($glue, array_map(
        function($multi) use ($glue) {
            if(! is_array($multi)) return (string)$multi;

            return sprintf('(%s)', implode_recursive($glue, $multi));
        },
        (array)$multi
    ));
};

class Row implements ContainerInterface
{
    private $indices = [];
    private $row = null;
    private $columns;

    /**
     * Row constructor.
     * @param $columns
     */
    public function __construct($columns)
    {
       $this->columns = $columns;
    }

    public function columnAt(string $columnName) : Column
    {
        return $this->link($columnName);
    }

    public function at(int $index)
    {
        return $this->row[$index];
    }

    public function setRow($row)
    {
        $this->row = &$row;
    }

    public function validate($row) : bool
    {
        return count(array_diff($this->indices, array_keys($row))) === 0;
    }

    public function link(string $columnName) : Column
    {
        if(! isset($this->columns[$columnName])) throw new ColumnNotFoundException($columnName, $this->columns);

        $this->indices[] = $index = $this->columns[$columnName];

        return new Column($index, function() use ($index) { return $this->row[$index]; });
    }
}




