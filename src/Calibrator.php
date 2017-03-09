<?php

namespace DataTools;

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Exceptions\PositionAdjustedException;
use DataTools\Interfaces\ValidateHeaderInterface;

final class Calibrator implements ValidateHeaderInterface
{
    /**
     * @var Column[]
     */
    private $columns;

    /**
     * Calibrator constructor.
     * @param Column[] $columns
     */
    public function __construct(Column ...$columns)
    {
        $this->columns = $columns;
    }

    public function calibrate($header) : array
    {
        $errors = $warnings = [];

        return [
            array_filter(array_map(
                function (Column $col) use ($header, &$errors, &$warnings)
                {
                    try
                    {
                        $col->locateIn($header);

                        return $col;
                    } catch (ColumnNotFoundException $e)
                    {
                        $errors[] = $e;

                        return null;

                    } catch (PositionAdjustedException $e)
                    {
                        $warnings[] = $e;

                        return $e->column;
                    }
                },
                $this->columns)),
            $errors,
            $warnings
        ];
    }

    public function __invoke($header)
    {
        return $this->calibrate($header);
    }
}