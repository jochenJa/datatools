<?php

namespace DataTools;

use DataTools\Interfaces\CalibrateHeaderInterface;
use DataTools\Interfaces\ColumnCreatorInterface;
use DataTools\Interfaces\GuessHeaderInterface;
use DataTools\Interfaces\ValidateHeaderInterface;
use League\Csv\Reader;

final class Header
{
    public $log = [];
    /**
     * @var Calibrator
     */
    private $calibrator;
    /**
     * @var int
     */
    private $untilRow;
    private $headerAt;
    private $calibratedColumns = [];

    public static function lookupByColumns(Reader $reader, Column ...$columns)
    {
        return (new self())->calibrate($reader, new Calibrator(...$columns));
    }

    public static function guessed(Reader $reader)
    {
        return (new self())->guess($reader, new Guessed());
    }

    public function __construct($untilRow = 20)
    {
        $this->untilRow = $untilRow;
    }

    private function init(Reader $reader)
    {
        $this->headerAt = null;
        $finder = $reader->newReader();
        $finder->setLimit($this->untilRow);

        return $finder;
    }

    public function calibrate(Reader $reader, CalibrateHeaderInterface $calibrator)
    {
        $finder = $this->init($reader);
        $finder->each(function($row, $offset) use ($calibrator) {
            $calibrator->calibrate($row, $offset);

            if(count($calibrator->errors($offset))) {
                $this->log(
                    $offset,
                    implode('|', $row),
                    $calibrator->warnings($offset),
                    $calibrator->errors($offset)
                );

                return true;
            }

            $this->headerAt = $offset;

            return false;
        });

        if(! isset($this->headerAt)) throw new \Exception('No header found :');

        return $this->calibratedColumns = $calibrator->columns();
    }

    public function guess(Reader $reader, GuessHeaderInterface $guessor)
    {
        $weights = $this->init($reader)->fetchAll([$guessor, 'weight']);

        list($weight, $this->headerAt) = array_reduce(
            $this->array_concat(
                $weights,
                array_keys($weights)
            ),
            function($highest, $weight) { return $highest[0] >= $weight[0] && $highest[1] < $weight[1] ? $highest : $weight; },
            [0,0]
        );

        return $this->calibratedColumns = $guessor->columns($reader->fetchOne($this->headerAt));
    }

    private function array_concat()
    {
        return array_map(
            function() { return func_get_args(); },
            ...func_get_args()
        );
    }

    private function log($offset, ...$info) { $this->log[$offset] = $info; }

    public function skip() { return $this->headerAt + 1; }

    public function at() { return $this->headerAt; }
}