<?php

namespace DataTools;

use DataTools\Exceptions\CalibratedColumnNotFoundException;
use DataTools\Interfaces\RowColumnInterface;

class Container implements RowColumnInterface
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
     * @return int
     * @throws CalibratedColumnNotFoundException
     */
    public function column(string $columnName) : int
    {
        if(! isset($this->columns[$columnName])) throw new CalibratedColumnNotFoundException($columnName, $this->columns);

        $this->indices[] = $this->columns[$columnName];

        return $this->columns[$columnName];
    }

    public function at(int $index)
    {
        return trim($this->row[$index]);
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