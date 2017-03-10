<?php

namespace DataTools;

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Exceptions\PositionAdjustedException;
use DataTools\Interfaces\ColumnCreatorInterface;
use DataTools\Interfaces\GuessHeaderInterface;
use DataTools\Interfaces\ValidateHeaderInterface;
use League\Csv\Reader;

final class Guessed implements GuessHeaderInterface
{
    public function weight($header, $index) : int
    {
        $rowCount = (string)count($header);

        return (int)($rowCount. str_pad(count(array_filter($header, [$this, 'validHeaderField'])), strlen($rowCount)));
    }

    public function normalize($field): string
    {
        return str_replace([' ', '.', "'", ','],['_', '','', '_'], mb_strtolower($field));
    }

    private function validHeaderField($field) : bool
    {
        return ! (empty($field) || is_numeric($field) || ! preg_match('/[a-z]/i', $field));
    }

    function columns($header): array
    {
        return array_filter(array_map(
            function($field, $index) {
                return $this->validHeaderField($field)
                    ? new Column($this->normalize($field), $field, $index)
                    : null
                ;
            },
            $header,
            array_keys($header)
        ));
    }
}