<?php

namespace DataTools;

use DataTools\Interfaces\RowColumnInterface;

class SimpleContainer implements RowColumnInterface
{
    private $indexMap;
    private $row;
    private $count;

    public function __construct(array $names)
    {
       $this->indexMap = array_unique($names);
       $this->count = count($this->indexMap);
    }

    public function column(string $name) : int
    {
        if(($index = array_search($name, $this->indexMap)) === false)
            throw new \Exception(sprintf('Key [%s] not found in [%s]', $name, implode(', ', $this->indexMap)));

        return $index;
    }

    public function at(int $index) { return trim($this->row[$index]); }
    public function validate($row) : bool { return count($row) === $this->count; }
    public function setRow($row) { $this->row = $row; }
}