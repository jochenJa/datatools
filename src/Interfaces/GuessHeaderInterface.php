<?php

namespace DataTools\Interfaces;

interface GuessHeaderInterface
{
    function weight($header, $index) : int;
    function columns($header) : array;
}