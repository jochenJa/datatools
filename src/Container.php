<?php

namespace DataTools;

use DataTools\Exceptions\CalibratedColumnNotFoundException;
use DataTools\Expression\Column as Link;
use DataTools\Interfaces\ContainerInterface;

class Container implements ContainerInterface
{
    private $indices = [];
    private $row = null;
    private $columns;

    /**
     * Container constructor.
     * @param Column[] $calibratedColumns
     */
    public function __construct(Column ...$calibratedColumns)
    {
        $this->columns = array_reduce(
            $calibratedColumns,
            function($map, Column $col) {
                $map[$col->field()] = $col->position();

                return $map;
            },
            []
        );
    }

    /**
     * @param string $columnName
     * @throws CalibratedColumnNotFoundException
     */
    public function link(string $columnName) : Link
    {
        if(! isset($this->columns[$columnName])) throw new CalibratedColumnNotFoundException($columnName, $this->columns);

        $this->indices[] = $index = $this->columns[$columnName];

        return new Link(
            $index,
            function() use ($index) { return trim($this->row[$index]); }
        );
    }

    public function validate($row) : bool
    {
        return (count(array_diff($this->indices, array_keys($row))) === 0);
    }

    public function setRow($row)
    {
        $this->row = $row;
    }
}