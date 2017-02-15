<?php

namespace DataTools\Interfaces;

interface ConfigureValidationInterface
{
    public function onImport() : array;
    public function withHistory() : array;
    public function betweenSuppliers() : array;
}