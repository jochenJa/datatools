<?php

namespace DataTools\Interfaces;

interface ConfigureMappingInterface
{
    public function columns() : array;
    public function mappedBy() : array;
}