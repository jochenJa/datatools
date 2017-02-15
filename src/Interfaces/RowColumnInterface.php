<?php

namespace DataTools\Interfaces;

interface RowColumnInterface
{
    public function at(int $index);
    public function column(string $columnName) : int;
    public function setRow($row);
    public function validate($row) : bool;
}