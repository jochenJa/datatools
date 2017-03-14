<?php

use DataTools\Calibrator;
use DataTools\Column;
use DataTools\Container;
use DataTools\Exceptions\PositionAdjustedException;
use DataTools\Expression\Compiler;
use DataTools\Json\Configuration;
use DataTools\Mapper;

class ConfigurationJsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    public $mapping;

    public function setUp()
    {
       $this->mapping = Configuration::fromJSON($this->configuration(), Compiler::build());
    }

    public function configuration()
    {
        return <<<JSON
{
    "mapping": {
        "columns": [
            { "A": ["A", 0] },
            { "B": ["B", 1, []] },
            { "C": ["c", 2, ["C"]]}
        ],
        "mapped_by": [
            { "X": "[C]"},
            { "Y": "[B]"},
            { "Z": "[A]"}
        ]
    },
    "validation": {
        "on_import": [
            "[X]==10",
            "[Y]<>[X]",
            "[Z]<<20"
        ],
        "with_history": [],
        "between_suppliers": []
    }
}
JSON;
    }

    /**
     * @test
     */
    public function positionIsUpdatedWhenNameIsFound()
    {
        $colA = new Column('X','A', 0, ['a']);
        $colB = new Column('Y','b', 1, ['B']);

        try {
            $colA->locateIn(['B', 'A']);
        } catch(PositionAdjustedException $e) {
            $this->assertSame('X[A, 1]', (string)$e->column);
        }

        try {
            $colB->locateIn(['B', 'A']);
        } catch(PositionAdjustedException $e) {
            $this->assertSame('Y[b, 0]', (string)$e->column);
        }
    }

    /**
     * @test
     */
    public function buildMappingSectionFromJsonString()
    {
        $mapping = $this->mapping->path(Configuration::MAPPING);

        $this->assertSame(
            '[{"A":["A",0]},{"B":["B",1,[]]},{"C":["c",2,["C"]]},{"X":"[C]"},{"Y":"[B]"},{"Z":"[A]"}]',
            json_encode($mapping)
        );
    }

    /**
     * @test
     */
    public function buildColumnsFromJsonString()
    {
        $columns = $this->mapping->columns();

        $this->assertSame("A[A, 0]B[B, 1]C[c, 2]", implode($columns));
    }

    /**
     * @test
     */
    public function buildMappedByFromJsonString()
    {
        $mapped = $this->mapping->mappedBy();

        $this->assertSame("X(Column(C))Y(Column(B))Z(Column(A))", implode($mapped));
    }

    /**
     * @test
     */
    public function buildMapperFromConfiguration()
    {
        $container = new Container(...$this->mapping->columns());
        $mapper = new Mapper(
            $container,
            ...$this->mapping->mappedBy()
        );

        $rs = $mapper(['K', 'L', 'M']);

        $this->assertSame("MLK", implode($rs));
        $this->assertSame("XYZ", implode(array_keys($rs)));

        $rs = $mapper(['O', 'P', 'Q']);

        $this->assertSame("QPO", implode($rs));
        $this->assertSame("XYZ", implode(array_keys($rs)));
    }

    /**
     * @test
     * @dataProvider validationSets
     */
    public function buildOnImportValidatorFromConfiguration($expected, $row)
    {
        $validator = new \DataTools\Validator(
            $this->mapping->validationMap(),
            ...$this->mapping->onImport()
        );

        $rs = $validator($row);
        $this->assertSame($expected, boolString($rs));
    }

    public function validationSets()
    {
        return [
            ['111', [10, 5, 2]],
            ['100', [10, 10, 21]],
            ['010', [1, 2, 28]],
            ['001', [1, 1, 19]],
            ['110', [10, 11, 100000]],
            ['101', [10, 10, 10]],
            ['011', [1, 100, 1]]
        ];
    }

    /**
     * @test
     */
    public function columnsMatchByPositionAndName()
    {
        $columns = $this->calibrate(
            1,
            ['A', 'B', 'C'],
            new Column('X','A', 0),
            new Column('Y','B', 1),
            new Column('Z','c', 2, ['C'])
        );

        $this->assertSame("X[A, 0]Y[B, 1]Z[c, 2]", implode(reset($columns)));
    }

    /**
     * @test
     */
    public function columnMatchByNameOrAliasNotByPosition()
    {
        $columns = $this->calibrate(
            1,
            ['A', 'B', 'C'],
            new Column('X','A', 2),
            new Column('Y','B', 4),
            new Column('Z','C', 1)
        )->columns();

        $this->assertSame("X[A, 0]Y[B, 1]Z[C, 2]", implode($columns));
    }

    /**
     * @test
     */
    public function calibrationReturnsErrorsAndWarnings()
    {
        $calibrated = $this->calibrate(
            1,
            ['A', 'B', 'C'],
            new Column('X','A', 4),
            new Column('Y','E', 1),
            new Column('Z','C', 2),
            new Column('X','D', 10)
        );

        $this->assertCount(2, $calibrated->columns(), implode($calibrated->columns()));
        $this->assertCount(2, $calibrated->errors(1));
        $this->assertCount(1, $calibrated->warnings(1));
    }

    private function calibrate($index, $header, Column ...$columns)
    {
        $calibrator = new Calibrator(...$columns);
        $calibrator->calibrate($header, $index);

        return $calibrator;
    }
}

function boolString($bools) { return array_reduce($bools, function($string, $bool) { return $string.($bool?:0); }); }


