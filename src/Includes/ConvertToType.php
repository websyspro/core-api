<?php

namespace Websyspro\Server\Includes;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use function is_string;
use function gettype;
use function in_array;

class ConvertToType
{
  public static function convert(
    mixed $source,
    string $target
  ): mixed {
    if (ConvertToType::isPrimitive($source) && ConvertToType::isBuiltinType($target)) {
      return ConvertToType::convertPrimitive($source, $target);
    }

    if (ConvertToType::isPrimitive($source) && !ConvertToType::isBuiltinType($target)) {
      return $source;
    }

    return ConvertToType::convertToObject($source, $target);
  }

  private static function isPrimitive(
    mixed $value
  ): bool {
    return is_scalar($value) || is_array($value) || is_null($value);
  }

  private static function isBuiltinType(
    string $type
  ): bool {
    return in_array($type, [
      "string", "int", "float", "bool", "array", 
      "object", "mixed", "null", "void"
    ]);
  }

  private static function convertPrimitive(
    mixed $source,
    string $target
  ): mixed {
    $sourceType = gettype(
      $source
    );
    
    $typeMap = [
      "integer" => "int",
      "boolean" => "bool",
      "double" => "float",
      "NULL" => "null",
    ];
    
    $sourceType = $typeMap[
      $sourceType
    ] ?? $sourceType;
    
    if ($sourceType === $target) {
      return $source;
    }
    
    return match($target) {
      "string" => (string) $source,
      "int" => (int) $source,
      "float" => (float) $source,
      "bool" => (bool) $source,
      "array" => (array) $source,
      default => $source
    };
  }

  private static function convertToObject(
    mixed $source,
    string $target
  ): object {
    if (!class_exists( $target )) {
      return $source;
    }

    $data = is_object($source)
      ? (array) $source 
      : (array) $source;
    
    $reflection = new ReflectionClass($target);
    $constructor = $reflection->getConstructor();
    
    if ($constructor) {
      return self::instantiateViaConstructor(
        $reflection, $constructor, $data
      );
    } else {
      return self::instantiateViaProperties(
        $reflection, $data
      );
    }
  }

  private static function instantiateViaConstructor(
    ReflectionClass $reflection,
    ReflectionMethod $constructor,
    array $data, array $params = []
  ): object {
    foreach ($constructor->getParameters() as $param) {
      $name = $param->getName();
      $value = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
      
      $type = $param->getType();
      if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $value !== null) {
        $typeName = $type->getName();
        
        if (enum_exists($typeName)) {
          $value = self::convertToEnum($typeName, $value);
        } else {
          $value = self::convert($value, $typeName);
        }
      }
      
      $params[] = $value;
    }
    
    return $reflection->newInstanceArgs($params);
  }

  private static function instantiateViaProperties(
    ReflectionClass $reflection,
    array $data
  ): object {
    $instance = $reflection->newInstanceWithoutConstructor();
    
    foreach ($reflection->getProperties() as $property) {
      $name = $property->getName();
      
      if (array_key_exists($name, $data)) {
        $property->setAccessible(true);
        $value = $data[$name];
        
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $value !== null) {
          $typeName = $type->getName();
          
          if (enum_exists($typeName)) {
            $value = self::convertToEnum($typeName, $value);
          } else {
            $value = self::convert($value, $typeName);
          }
        }
        
        $property->setValue($instance, $value);
      }
    }
    
    return $instance;
  }

  private static function convertToEnum(
    string $enumClass, 
    mixed $value
  ): mixed {
    if (!is_string($value)) {
      return $value;
    }
    
    if (method_exists($enumClass, "from")) {
      return $enumClass::from($value);
    }
    
    $enumReflection = new ReflectionClass($enumClass);
    $enumCases = $enumReflection->getMethod("cases")->invoke(null);
    
    foreach ($enumCases as $case) {
      if (strcasecmp($case->name, $value) === 0) {
        return $case;
      }
    }
    
    return $value;
  }
}
