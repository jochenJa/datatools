<?php

namespace DataTools;

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
        $header = new self(new Calibrator(...$columns));

        return $header->lookup($reader);
    }

    public function __construct(Calibrator $calibrator, $untilRow = 20)
    {
        $this->calibrator = $calibrator;
        $this->untilRow = $untilRow;
    }

    public function lookup(Reader $reader)
    {
        $finder = $reader->newReader();
        $finder->setLimit($this->untilRow);
        $finder->each(function($row, $offset) {
            list($calibrated, $err, $warn) = $this->calibrator->calibrate($row);

            if(count($err)) {
                $this->log($offset, implode('|', $row), $warn, $err);

                return true;
            }

            $this->calibratedColumns = $calibrated;
            $this->headerAt = $offset;

            return false;
        });

        if(empty($this->calibratedColumns)) throw new \Exception('No header found :');

        return $this->calibratedColumns;
    }

    private function log($offset, ...$info) { $this->log[$offset] = $info; }

    public function skip() { return $this->headerAt + 1; }
}