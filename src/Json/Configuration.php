<?php

namespace DataTools\Json;

use DataTools\Column;
use DataTools\Expression\Compiler;
use DataTools\Expression\FieldExpression;
use DataTools\Expression\LogicExpression;
use DataTools\Interfaces\ConfigureMappingInterface;
use DataTools\Interfaces\ConfigureValidationInterface;
use DataTools\Interfaces\ContainerInterface;
use DataTools\SimpleContainer;

class Configuration implements ConfigureMappingInterface, ConfigureValidationInterface
{
    const FROM = 'columns';
    const TO = 'mapped_by';
    const VALID = 'validation';
    const ON_IMPORT = 'on_import';
    const WITH_HISTORY = 'with_history';
    const BETWEEN_SUPPLIERS = 'between_suppliers';
    const MAPPING = 'mapping';

    private $cfg;
    private $compiler;

    public static function fromJSON($configuration_json, Compiler $compiler)
    {
        if(! ($cfg = json_decode($configuration_json, true))) {
            throw new \Exception('invalid configuration : json_decode failed.');
        };

        return new self($cfg, $compiler);
    }

    public function __construct(array $cfg, Compiler $compiler)
    {
        $this->cfg = $cfg;
        $this->compiler = $compiler;
    }

    private function array_key_map($array, callable $call) {  return array_map($call, $array, array_keys($array));  }

    public function columns() : array
    {
        return $this->array_key_map(
            $this->path(self::MAPPING, self::FROM),
            function($columnInfo, $field) { return new Column($field, ...$columnInfo); }
        );
    }

    public function mappedBy() : array
    {
        return $this->array_key_map(
            $this->path(self::MAPPING, self::TO),
            function(string $expressionString, string $field) {
                return new FieldExpression(
                    $field,
                    $this->compiler->expression($expressionString)
                );
            }
        );
    }

    public function path(...$sections)
    {
        return $this->unpack(array_reduce(
            $sections,
            function($cfg, $section) {
                return $this->section($cfg, $section);
            },
            $this->cfg
        ));
    }

    private function section($cfg, $section)
    {
        if(isset($cfg[$section]) && is_array($cfg[$section])) {
            return $cfg[$section];
        }

        throw new \Exception(sprintf('Section "%s" not found in configuration[%s].', $section, json_encode($cfg)));
    }

    private function unpack($keyAndValue)
    {
        return array_reduce(
            $keyAndValue,
            function($container, $keyAndValue) { return array_merge($container, (array)$keyAndValue); },
            []
        );
    }

    public function onImport() : array
    {
        return array_map(
            function(string $expr) { return new LogicExpression($this->compiler->expression($expr)); },
            $this->path(self::VALID, self::ON_IMPORT)
        );
    }

    public function withHistory() : array
    {
        return array_map(
            function(string $expr) { return new LogicExpression($this->compiler->expression($expr)); },
            $this->path(self::VALID, self::WITH_HISTORY)
        );
    }

    public function betweenSuppliers() : array
    {
        return array_map(
            function(string $expr) { return new LogicExpression($this->compiler->expression($expr)); },
            $this->path(self::VALID, self::BETWEEN_SUPPLIERS)
        );
    }

    public function validationMap(): ContainerInterface
    {
        return new SimpleContainer(array_keys($this->path(self::MAPPING, self::TO)));
    }
}
