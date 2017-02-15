<?php

namespace DataTools;

use DataTools\Interfaces\ConfigureMappingInterface;
use League\Csv\Reader;

class Importer
{
    private $mapping;

    /**
     * Importer constructor.
     * @param ConfigureMappingInterface $mapping
     */
    public function __construct(ConfigureMappingInterface $mapping)
    {
        $this->header = new Header(new Calibrator(...$mapping->columns()));
        $this->mapping = $mapping->mappedBy();
    }

    public function build(Reader $reader) : \Iterator
    {
        $mapper = new Mapper(
            new Container(...$this->header->lookup($reader)),
            ...$this->mapping
        );
        $reader->setOffset($this->header->skip());

        return $reader->fetch($mapper);
    }
}