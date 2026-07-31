<?php

namespace Websyspro\Server\Includes;

use ReflectionClass;
use ReflectionNamedType;
use function is_object;
use function array_key_exists;
use function enum_exists;
use function is_string;

abstract class Model
{
  public static function from(
    mixed $data
  ): Model {
    $instance = new static;
    $reflection = new ReflectionClass($instance);
    $data = is_object($data) 
      ? (array) $data 
      : (array) $data;

    foreach ($reflection->getProperties() as $property) {
      $name = $property->getName();
      if (!array_key_exists($name, $data)) {
          continue;
      }

      $type = $property->getType();
      $value = $data[ $name ];

      if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
        $typeName = $type->getName();
        
        if (enum_exists($typeName)) {
          $value = is_string($value) ? $typeName::from($value) : $value;
        } else {
          $value = $typeName::from($value);
        }
      }

      $property->setValue(
        $instance, $value
      );
    }

    return $instance;
  }
}
