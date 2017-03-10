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

        $header = new Header();
        $columns = $header->guess($reader, new Guessed());

        $this->assertSame($columnsString, implode('|', $columns));
        $this->assertEquals($expected, $header->at(), 'wrong line matched');
    }

    /**
     * @test
     * @dataProvider possibleCsvs
     */
    public function guessing($expected, $csv, $columnsString, $weights)
    {
        $reader = Reader::createFromString($csv);
        $reader->setDelimiter(';');

        $this->assertEquals($weights, implode('|', $reader->fetchAll([(new Guessed()),'weight'])));
    }

    public function possibleCsvs()
    {
        return [
            // valid header on first row
            [0, <<<CSV
test
CSV
            ,'test[test, 0]',"11"],
            // valid header at first row with highest row count
            [2, <<<CSV
test
test;test
test;test;test
test;test;test
CSV
            ,'test[test, 0]|test[test, 1]|test[test, 2]',
            '11|22|33|33'
            ],
            // valid header at second row cuz it doesnt have empty fields
            [1, <<<CSV
test;1test;
test;test1;test2
CSV
            ,'test[test, 0]|test1[test1, 1]|test2[test2, 2]',
            '32|33'
            ],
            // numeric fields arent allowed in header.
            [2, <<<CSV
test;test;57657676,8
test;11;B
A;B;C
CSV
            ,'a[A, 0]|b[B, 1]|c[C, 2]',
            '32|32|33'
            ],
            // numeric fields and empty arent allowed in header. no header found.
            [0, <<<CSV
test;test;1,5
test;1.1;B
;test;test
CSV
            ,'test[test, 0]|test[test, 1]',
            "32|32|32"
            ],
            [
                2,
                file_get_contents('test.csv'),
                'sql[Sql, 0]|merk[Merk, 1]|model[Model, 2]|wagenomschrijving[Wagenomschrijving, 3]|voertuigcode[Voertuigcode, 4]|brandstof[Brandstof, 5]|looptijd[Looptijd, 6]|jaar_km[Jaar km, 7]|carrosserievorm[Carrosserievorm, 8]|deuren[Deuren, 10]|fisk_pk[Fisk. PK, 11]|kw[KW, 12]|cyl_inh[Cyl. Inh., 13]|co2_gr/km[CO2 gr/km, 14]|co2_bijdrage[CO2 bijdrage, 15]|fisk_aftrek[Fisk. Aftrek., 16]|verbruik_l/100km[Verbruik l/100km, 17]|brandstofprijs[Brandstofprijs, 18]|leaseprijs[Leaseprijs, 19]|catalogusprijs[Catalogusprijs, 20]',
                '211|210|2120|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|215|215|215|215|215|215|215|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216|216',
            ]
        ];
    }
}
