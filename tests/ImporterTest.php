<?php

use DataTools\Expression\Compiler;
use DataTools\Guessed;
use DataTools\Importer;
use Domain\EurotaxConverter;
use Domain\Substitutor\Jato;
use League\Csv\Reader;

class ImporterTest extends PHPUnit_Framework_TestCase
{
    /** @var  Reader */
    private $reader;

    public function setUp() 
    {
        $this->reader = Reader::createFromPath(is_file('tests/test.csv') ? 'tests/test.csv' : 'test.csv');
        $this->reader->setDelimiter(';');
    }
    
    /**
     * @test
     */
    public function readerParsedData()
    {
        $csv = $this->reader->fetchAll();
        $this->assertCount(61, $csv);
        $this->assertCount(1, array_unique(array_map(function($r) { return count($r); }, $csv)));
    }
    
    /**
     * @test
     */
    public function importer()
    {
        $importer = new Importer($this->mapping());
        $carOffers = iterator_to_array($importer->build($this->reader));

        $this->assertCount(58, $carOffers);
        $this->assertSame('1 BE118246 439.5 BMW 60 32000', implode(' ', reset($carOffers)));
        $this->assertSame('1 BE113312 915.5 Volvo 60 32000', implode(' ', end($carOffers)));
    }


    public function mapping()
    {
        $json = <<<JSON
{
    "mapping": {
        "columns": [
            {"brand": ["Merk", 1]},
            {"eurotax": ["Voertuigcode", 4]},
            {"duration": ["Looptijd", 6]},
            {"distance": ["Jaar km", 7]},
            {"contrib_co2": ["CO2 bijdrage", 15]},
            {"tax": ["Fisk. Aftrek.", 16]},
            {"leaseprice": ["Leaseprijs", 19]}
        ],
        "mapped_by": [
            {"jato": "jato[eurotax]"},
            {"eurotax": "[eurotax]"},
            {"leasingPrice": "[leaseprice]+[contrib_co2]+65%[tax]"},
            {"brand": "[brand]"},
            {"duration": "[duration]"},
            {"distance": "[distance]"}
        ]
    }
}
JSON;
        return new \DataTools\Json\Configuration($json, Compiler::build(
            new \DataTools\Expression\Substitutor(
                '/jato/i',
                function() { return new \DataTools\Expression\Method(function($eurotax) { return 1; }, 'eurotax'); }
            )
        ));
    }

}
