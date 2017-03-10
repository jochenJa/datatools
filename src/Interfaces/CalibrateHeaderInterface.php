<?php

namespace DataTools\Interfaces;

interface CalibrateHeaderInterface
{
    function calibrate($header, $index);
    function errors($index) : array;
    function warnings($index) : array;
    function columns() : array;
}