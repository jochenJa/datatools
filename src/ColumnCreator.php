<?php

namespace DataTools;

use DataTools\Interfaces\ColumnCreatorInterface;

class ColumnCreator implements ColumnCreatorInterface
{
    public function create($field, $name, $index): Column
    {
        return new Column($field, $name, $index);
    }

    public function normalize($field): string
    {
        return str_replace([' ', '.', "'", ','],['_', '','', '_'], mb_strtolower($field));
    }
}