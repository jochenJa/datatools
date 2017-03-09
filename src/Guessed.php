<?php

namespace DataTools;

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Exceptions\PositionAdjustedException;
use DataTools\Interfaces\ColumnCreatorInterface;
use DataTools\Interfaces\ValidateHeaderInterface;
use League\Csv\Reader;

final class Guessed implements ValidateHeaderInterface
{
    private $columnCreator;
    /**
     * @var
     */
    private $rowCount;

    public static function byHighestColumnCount(Reader $reader, ColumnCreatorInterface $creator)
    {
        $finder = $reader->newReader();
        $finder->setLimit(20);

        $rowCount = array_reduce(
            $finder->fetchAll(function($row) { return count($row); }),
            function($highest, $count) { return $count > $highest ? $count : $highest; },
            0
        );

        return new self( $rowCount, $creator);
    }

    /**
     * Guessed constructor.
     * @param ColumnCreatorInterface $creator
     */
    public function __construct($rowCount, ColumnCreatorInterface $creator)
    {
        $this->columnCreator = $creator;
        $this->rowCount = $rowCount;
    }

    public function calibrate($header) : array
    {
        if(count($header) != $this->rowCount)
            return [[], [sprintf('Invalid column count %d', count($header))], []];

        foreach($header as $index => $field) if(empty($field) || is_numeric($field) || ! preg_match('/[a-z]/i', $field))
            return [[], [sprintf('Invalid header field %s at %d', $field, $index)], []];

        return [
            array_map(
                function($field, $index) {
                    return $this->columnCreator->create(
                        $this->columnCreator->normalize($field),
                        $field,
                        $index
                    );
                },
                $header,
                array_keys($header)
            ),
            [],[]
        ];
    }
}