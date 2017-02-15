<?php

namespace DataTools\Exceptions;

class CalibratedColumnNotFoundException extends \Exception
{
    protected $message = 'noooo %s isnt found in the calibated columns [%s].';

    /**
     * ColumnNotFoundException constructor.
     * @param string $name
     * @param array $columns
     */
    public function __construct(string $name, array $columns)
    {
        $this->message = sprintf($this->message, $name, implode(' ', array_keys($columns)));
    }
}