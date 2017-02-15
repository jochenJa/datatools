<?php

namespace DataTools;

use DataTools\Exceptions\ColumnNotFoundException;
use DataTools\Exceptions\PositionAdjustedException;

class Column
{
    private $name;
    private $alias;
    private $position;
    /**
     * @var string
     */
    private $field;

    /**
     * Field constructor.
     * @param string $name
     * @param int $position
     * @param array|string $alias
     */
    public function __construct(string $field, string $name, int $position, $alias = [])
    {
        $this->name = $name;
        $this->position = $position;
        $this->alias = (array)$alias;
        $this->field = $field;
    }

    public function position() : int { return $this->position; }
    public function field() : string { return $this->field; }

    private function sameName($name)
    {
        return $this->name === $name
        || (array_search($name, $this->alias, true) !== false);
    }

    public function locateIn($header)
    {
        if(
            isset($header[$this->position])
            && $this->sameName($header[$this->position])
        ) return;

        foreach($this->nameAliaslookUp($header) as $position)
            if($position !== false) {
                throw new PositionAdjustedException(
                    $this->adjustPosition($position)
                );
            }

        throw new ColumnNotFoundException();
    }

    private function nameAliaslookUp($header)
    {
        yield array_search($this->name, $header, true);

        foreach($this->alias as $alias)
            yield array_search($alias, $header, true);
    }

    function __toString()
    {
        return sprintf('%s[%s, %d]', $this->field, $this->name, $this->position);
    }

    private function adjustPosition($position)
    {
        return new self($this->field, $this->name, $position, $this->alias);
    }
}