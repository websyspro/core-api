<?php

namespace Websyspro\Server\Includes;

use ReflectionClass;
use ReflectionNamedType;

class Container
{
  public static function make(
    string $class,
    array $args = []
  ): object {
    $reflection = new ReflectionClass($class);
    $constructor = $reflection->getConstructor();

    if ($constructor === null) {
      return $reflection->newInstance();
    }

    foreach ($constructor->getParameters() as $param) {
      $type = $param->getType();

      if (!($type instanceof ReflectionNamedType) || $type->isBuiltin()) {
        $args[] = $param->isDefaultValueAvailable()
          ? $param->getDefaultValue()
          : null;
        continue;
      }

      if ($param->isDefaultValueAvailable()) {
        $args[] = $param->getDefaultValue();
        continue;
      }

      $args[] = Container::make($type->getName());
    }

    return $reflection->newInstanceArgs($args);
  }
}
