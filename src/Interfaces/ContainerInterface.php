<?php

namespace DataTools\Interfaces;

use DataTools\Expression\Column;

interface ContainerInterface
{
    public function link(string $reference) : Column;
    public function workOn(array $data) : bool;
}