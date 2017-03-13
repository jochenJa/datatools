<?php

namespace DataTools;

use DataTools\Interfaces\ContainerInterface;
use DataTools\Expression\Column;

class SimpleContainer implements ContainerInterface
{
    private $indexMap;
    private $row;
    private $count;

    public function __construct(array $names)
    {
       $this->indexMap = array_unique($names);
       $this->count = count($this->indexMap);
    }

    public function link(string $name) : Column
    {
        if(($index = array_search($name, $this->indexMap)) === false)
            throw new \Exception(sprintf('Key [%s] not found in [%s]', $name, implode(', ', $this->indexMap)));

        return new Column($index, function() use ($index) { return $this->at($index); });
    }

    public function at(int $index) { return trim($this->row[$index]); }
    public function validate($row) : bool { return count($row) === $this->count; }
    public function setRow($row) { $this->row = $row; }
}