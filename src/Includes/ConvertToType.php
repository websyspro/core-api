<?php

namespace Websyspro\Server\Includes;

use ReflectionClass;
use ReflectionNamedType;

class ConvertToType
{
  /**
   * Converte um valor source para o tipo target
   * 
   * @param mixed $source Valor de origem (pode ser primitivo, stdClass, objeto)
   * @param string $target Nome da classe/tipo de destino
   * @return mixed Valor convertido
   */
  public static function convert(
    mixed $source,
    string $target
  ): mixed {
    // 1. Validação: se ambos são tipos primitivos e iguais, retorna direto
    if (self::isPrimitive($source) && self::isBuiltinType($target)) {
      return self::convertPrimitive($source, $target);
    }

    // 2. Se source é primitivo mas target não é, retorna o source
    if (self::isPrimitive($source) && !self::isBuiltinType($target)) {
      return $source;
    }

    // 3. Se source não é primitivo, converte para o tipo target
    return self::convertToObject($source, $target);
  }

  /**
   * Verifica se o valor é um tipo primitivo
   */
  private static function isPrimitive(mixed $value): bool
  {
    return is_scalar($value) || is_array($value) || is_null($value);
  }

  /**
   * Verifica se o tipo target é builtin (primitivo)
   */
  private static function isBuiltinType(string $type): bool
  {
    return in_array($type, [
      'string', 'int', 'float', 'bool', 'array', 
      'object', 'mixed', 'null', 'void'
    ]);
  }

  /**
   * Converte entre tipos primitivos
   */
  private static function convertPrimitive(mixed $source, string $target): mixed
  {
    $sourceType = gettype($source);
    
    // Normaliza os nomes dos tipos
    $typeMap = [
      'integer' => 'int',
      'boolean' => 'bool',
      'double' => 'float',
      'NULL' => 'null',
    ];
    
    $sourceType = $typeMap[$sourceType] ?? $sourceType;
    
    // Se os tipos são iguais, retorna direto
    if ($sourceType === $target) {
      return $source;
    }
    
    // Converte se necessário
    return match($target) {
      'string' => (string) $source,
      'int' => (int) $source,
      'float' => (float) $source,
      'bool' => (bool) $source,
      'array' => (array) $source,
      default => $source
    };
  }

  /**
   * Converte source (stdClass/objeto) para classe target
   */
  private static function convertToObject(mixed $source, string $target): object
  {
    // Se target não é uma classe válida, retorna o source
    if (!class_exists($target)) {
      return $source;
    }

    $data = is_object($source) ? (array) $source : (array) $source;
    $reflection = new ReflectionClass($target);
    
    // Verifica se tem construtor
    $constructor = $reflection->getConstructor();
    
    if ($constructor) {
      // Tem construtor: instancia via new Target(...)
      return self::instantiateViaConstructor($reflection, $constructor, $data);
    } else {
      // Sem construtor: instancia e seta propriedades via setValue
      return self::instantiateViaProperties($reflection, $data);
    }
  }

  /**
   * Instancia via construtor (new Target(...))
   */
  private static function instantiateViaConstructor(
    ReflectionClass $reflection,
    \ReflectionMethod $constructor,
    array $data
  ): object {
    $params = [];
    
    foreach ($constructor->getParameters() as $param) {
      $name = $param->getName();
      $value = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
      
      // Converte o tipo se necessário
      $type = $param->getType();
      if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $value !== null) {
        $typeName = $type->getName();
        
        // Trata enums
        if (enum_exists($typeName)) {
          $value = self::convertToEnum($typeName, $value);
        } else {
          // Converte recursivamente para objetos aninhados
          $value = self::convert($value, $typeName);
        }
      }
      
      $params[] = $value;
    }
    
    return $reflection->newInstanceArgs($params);
  }

  /**
   * Instancia sem construtor e seta propriedades via setValue
   */
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
        
        // Converte o tipo se necessário
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $value !== null) {
          $typeName = $type->getName();
          
          // Trata enums
          if (enum_exists($typeName)) {
            $value = self::convertToEnum($typeName, $value);
          } else {
            // Converte recursivamente para objetos aninhados
            $value = self::convert($value, $typeName);
          }
        }
        
        $property->setValue($instance, $value);
      }
    }
    
    return $instance;
  }

  /**
   * Converte valor para enum
   */
  private static function convertToEnum(string $enumClass, mixed $value): mixed
  {
    if (!is_string($value)) {
      return $value;
    }
    
    // Tenta backed enum primeiro (from)
    if (method_exists($enumClass, 'from')) {
      return $enumClass::from($value);
    }
    
    // Unit enum - busca case-insensitive
    $enumReflection = new ReflectionClass($enumClass);
    $enumCases = $enumReflection->getMethod('cases')->invoke(null);
    
    foreach ($enumCases as $case) {
      if (strcasecmp($case->name, $value) === 0) {
        return $case;
      }
    }
    
    return $value;
  }
}
