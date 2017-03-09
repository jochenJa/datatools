<?php

namespace DataTools\Interfaces;

use DataTools\Column;

interface ColumnCreatorInterface
{
    public function create($field, $name, $index) : Column;
    public function normalize($field) : string;
}