<?php

namespace DataTools;

use DataTools\Interfaces\ConfigureMappingInterface;
use League\Csv\Reader;

class Importer
{
    private $mapping;
    private $columns;

    /**
     * Importer constructor.
     * @param ConfigureMappingInterface $mapping
     */
    public function __construct(ConfigureMappingInterface $mapping)
    {
        $this->mapping = $mapping;
    }

    public function build(Reader $reader) : \Iterator
    {
        $header = new Header();
        $mapper = new Mapper(
            new Container(...$header->calibrate(
                $reader,
                new Calibrator(...$this->mapping->columns()))
            ),
            ...$this->mapping->mappedBy()
        );
        $reader->setOffset($header->skip());

        return $reader->fetch($mapper);
    }
}