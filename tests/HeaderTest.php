<?php

namespace DataTools;

use League\Csv\Reader;

class HeaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider possibleCsvs
     */
    public function headerCanBeGuessedInCSV($expected, $csv, $columnsString)
    {
        $reader = Reader::createFromString($csv);
        $reader->setDelimiter(';');

        $columns = [];
        $header = new Header(Guessed::byHighestColumnCount($reader, new ColumnCreator()));
        try {
            $columns = $header->lookup($reader);
        } catch(\Exception $e) {
            $this->assertEquals($columnsString, $e->getMessage());
        }

        if(! empty($cols)) $this->assertSame($columnsString, implode('|', $columns));
        $this->assertEquals($expected, $header->at(), 'wrong line matched');
    }

    /**
     * @test
     * @dataProvider possibleCsvs
     */
    public function guessing($expected, $csv, $columnsString)
    {
        $reader = Reader::createFromString($csv);
        $reader->setDelimiter(';');

        $guess = Guessed::byHighestColumnCount($reader, new ColumnCreator());

        $cols = [];
        foreach($reader->fetch() as $index => $row) {
            list($cols, $err) = $guess->calibrate($row);

            if(empty($err)) {
                $this->assertEquals($expected, $index);
                $this->assertSame($columnsString, implode('|', $cols));
                return true;
            }
        };

        $this->assertEquals($expected, ! empty($cols));
    }

    public function possibleCsvs()
    {
        return [
            // valid header on first row
            [0, <<<CSV
test
CSV
            ,'test[test, 0]'],
            // valid header at first row with highest row count
            [2, <<<CSV
test
test;test
test;test;test
test;test;test
CSV
            ,'test[test, 0]|test[test, 1]|test[test, 2]'],
            // valid header at second row cuz it doesnt have empty fields
            [1, <<<CSV
test;1test;
test;test1;test2
CSV
            ,'test[test, 0]|test1[test1, 1]|test2[test2, 2]'],
            // numeric fields arent allowed in header.
            [2, <<<CSV
test;test;57657676,8
test;11;B
A;B;C
CSV
            ,'a[A, 0]|b[B, 1]|c[C, 2]'],
            // numeric fields and empty arent allowed in header. no header found.
            [false, <<<CSV
test;test;1,5
test;1.1;B
;test;test
CSV
            ,'No header found :'],
        ];
    }
}
