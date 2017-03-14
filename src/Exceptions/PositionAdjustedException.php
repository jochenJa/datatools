<?php

namespace DataTools\Exceptions;

use DataTools\Column;

class PositionAdjustedException extends \Exception
{
    /**
     * @var Column
     */
    public $column;

    /**
     * PositionAdjustedException constructor.
     * @param Column $column
     */
    public function __construct(Column $column)
    {
        $this->column = $column;
    }
}