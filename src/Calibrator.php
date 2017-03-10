<?php

namespace DataTools;

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Exceptions\PositionAdjustedException;
use DataTools\Interfaces\CalibrateHeaderInterface;

final class Calibrator implements CalibrateHeaderInterface
{
    /**
     * @var Column[]
     */
    private $columns;
    private $errors;
    private $warnings;
    private $calibratedWithIndex = null;
    private $calibrated = [];

    /**
     * Calibrator constructor.
     * @param Column[] $columns
     */
    public function __construct(Column ...$columns)
    {
        $this->columns = $columns;
    }

    public function calibrate($header, $index)
    {
        $this->init($index);

        $this->calibrated = array_filter(
            array_map(
                function (Column $col) use ($header)
                {
                    try
                    {
                        $col->locateIn($header);

                        return $col;
                    } catch (ColumnNotFoundException $e)
                    {
                        $this->errors[] = $e;

                        return null;

                    } catch (PositionAdjustedException $e)
                    {
                        $this->warnings[] = $e;

                        return $e->column;
                    }
                },
                $this->columns)
        );
    }

    public function errors($index): array
    {
        if($this->calibratedWithIndex !== $index) throw new \Exception(sprintf("Didn't calibrate for index [%s] but for [%s]", $index, $this->calibratedWithIndex));

        return $this->errors;
    }

    public function warnings($index): array
    {
        if($this->calibratedWithIndex !== $index) throw new \Exception(sprintf("Didn't calibrate for index [%s] but for [%s]", $index, $this->calibratedWithIndex));

        return $this->warnings;
    }

    private function init($index)
    {
        $this->calibratedWithIndex = $index;
        $this->errors = $this->warnings = $this->calibrated = [];
    }

    public function columns(): array
    {
        return $this->calibrated;
    }
}