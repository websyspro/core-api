<?php

namespace Websyspro\Server\Includes;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use UnitEnum;
use function is_string;
use function gettype;
use function in_array;
use function is_scalar;
use function is_array;
use function is_object;
use function array_key_exists;
use function get_class;

class ConvertToType
{
  /**
   * Ponto de entrada.
   *
   * $source pode ser qualquer coisa:
   *   - primitivo (string, int, float, bool, null)
   *   - array (de primitivos, objetos, arrays aninhados)
   *   - object / stdClass (com filhos de qualquer profundidade)
   *   - UnitEnum
   *
   * $target pode ser:
   *   - omitido  → resolve pelo tipo real de $source
   *   - built-in  → "string", "int", "float", "bool", "array", etc.
   *   - FQCN de classe → hidrata via reflection
   */
  public static function convert(
    mixed $source,
    string $target = ""
  ): mixed {
    if ($target === "") {
      $target = self::resolveTypeOf($source);
    }

    // Array de qualquer coisa → hidrata cada item
    if (is_array($source)) {
      return self::hydrateArray($source, $target);
    }

    // Enum → não tenta converter, devolve como está (ou extrai ->name se target=string)
    if ($source instanceof UnitEnum) {
      return $target === "string" ? $source->name : $source;
    }

    // Object / stdClass com target primitivo built-in → tenta coerção
    // "object" e "mixed" satisfazem qualquer objeto → passa direto
    // Ex: stdClass com uma única prop string e target="string" → extrai o valor
    if (is_object($source) && self::isBuiltinType($target)) {
      if ($target === "object" || $target === "mixed") {
        return $source;
      }
      return self::coerceToPrimitive($source, $target);
    }

    // Object / stdClass → hidratação propriedade a propriedade
    if (is_object($source)) {
      return self::convertFromObject($source, $target);
    }

    // Primitivo + built-in → cast direto
    if (self::isPrimitive($source) && self::isBuiltinType($target)) {
      return self::convertPrimitive($source, $target);
    }

    // Primitivo + classe concreta → devolve sem conversão
    return $source;
  }

  // ---------------------------------------------------------------------------
  // Resolução de tipo quando $target é omitido
  // ---------------------------------------------------------------------------

  private static function resolveTypeOf(mixed $value): string
  {
    if (is_object($value)) {
      return get_class($value);
    }

    $typeMap = [
      "integer" => "int",
      "boolean" => "bool",
      "double"  => "float",
      "NULL"    => "null",
    ];

    $raw = gettype($value);
    return $typeMap[$raw] ?? $raw;
  }

  // ---------------------------------------------------------------------------
  // Coerção de object para primitivo
  // ---------------------------------------------------------------------------

  /**
   * Quando $source é um objeto mas $target é um tipo primitivo built-in.
   *
   * Regras (em ordem):
   *  1. Se $source já pode ser convertido diretamente (ex: objeto com __toString) → usa cast
   *  2. Se $source tem uma única propriedade → extrai o valor e converte
   *  3. Se $target = "array" → converte para array via cast
   *  4. Se $target = "string" e o objeto tem __toString → usa (string)
   *  5. Caso contrário → devolve $source sem alteração
   */
  private static function coerceToPrimitive(object $source, string $target): mixed
  {
    // array → cast direto
    if ($target === "array") {
      return (array) $source;
    }

    // string e objeto tem __toString → usa cast
    if ($target === "string" && method_exists($source, "__toString")) {
      return (string) $source;
    }

    // objeto com uma única propriedade → extrai e converte o valor
    $vars = get_object_vars($source);
    if (count($vars) === 1) {
      $value = reset($vars);
      if (is_scalar($value) || $value === null) {
        return self::convertPrimitive($value, $target);
      }
    }

    // sem como converter → devolve como está
    return $source;
  }

  // ---------------------------------------------------------------------------
  // Hidratação de Object / stdClass
  // ---------------------------------------------------------------------------

  /**
   * Quando $target é uma classe concreta diferente da fonte → instancia via reflection.
   * Quando $target é stdClass / object / mesmo tipo → espelha as props recursivamente.
   */
  private static function convertFromObject(
    object $source,
    string $target
  ): mixed {
    $sourceClass = get_class($source);

    $isConcreteTarget = $target !== "object"
      && $target !== "stdClass"
      && $target !== $sourceClass
      && class_exists($target);

    if ($isConcreteTarget) {
      return self::convertToObject($source, $target);
    }

    // Espelha para stdClass, resolvendo filhos recursivamente
    $result = new \stdClass();
    foreach (get_object_vars($source) as $name => $value) {
      $result->$name = self::hydrateValue($value);
    }

    return $result;
  }

  /**
   * Hidrata um valor individual sem conhecer o tipo alvo.
   * Usado quando percorremos propriedades de um stdClass sem target explícito.
   */
  private static function hydrateValue(mixed $value): mixed
  {
    if ($value instanceof UnitEnum) {
      return $value->name;
    }

    if (is_array($value)) {
      return self::hydrateArray($value);
    }

    if (is_object($value)) {
      return self::convertFromObject($value, get_class($value));
    }

    return $value;
  }

  /**
   * Percorre array e hidrata cada item.
   * Se $itemTarget for fornecido e for uma classe concreta, tenta converter cada item para ela.
   */
  private static function hydrateArray(array $source, string $itemTarget = ""): array
  {
    $isConcreteTarget = $itemTarget !== ""
      && !self::isBuiltinType($itemTarget)
      && class_exists($itemTarget);

    $result = [];
    foreach ($source as $key => $item) {
      if ($isConcreteTarget) {
        $result[$key] = self::convert($item, $itemTarget);
      } else {
        $result[$key] = self::hydrateValue($item);
      }
    }

    return $result;
  }

  // ---------------------------------------------------------------------------
  // Helpers de tipo
  // ---------------------------------------------------------------------------

  private static function isPrimitive(mixed $value): bool
  {
    return is_scalar($value) || is_array($value) || ($value === null);
  }

  private static function isBuiltinType(string $type): bool
  {
    return in_array($type, [
      "string", "int", "float", "bool", "array",
      "object", "mixed", "null", "void"
    ]);
  }

  // ---------------------------------------------------------------------------
  // Conversão de primitivos (cast)
  // ---------------------------------------------------------------------------

  private static function convertPrimitive(
    mixed $source,
    string $target
  ): mixed {
    $typeMap = [
      "integer" => "int",
      "boolean" => "bool",
      "double"  => "float",
      "NULL"    => "null",
    ];

    $sourceType = $typeMap[gettype($source)] ?? gettype($source);

    if ($sourceType === $target) {
      return $source;
    }

    return match ($target) {
      "string" => (string) $source,
      "int"    => (int) $source,
      "float"  => (float) $source,
      "bool"   => (bool) $source,
      "array"  => (array) $source,
      default  => $source
    };
  }

  // ---------------------------------------------------------------------------
  // Instanciação via reflection
  // ---------------------------------------------------------------------------

  private static function convertToObject(
    mixed $source,
    string $target
  ): object {
    if (!class_exists($target)) {
      return $source;
    }

    $data = is_object($source) ? (array) $source : (array) $source;

    $reflection  = new ReflectionClass($target);
    $constructor = $reflection->getConstructor();

    if ($constructor) {
      return self::instantiateViaConstructor($reflection, $constructor, $data);
    }

    return self::instantiateViaProperties($reflection, $data);
  }

  private static function instantiateViaConstructor(
    ReflectionClass $reflection,
    ReflectionMethod $constructor,
    array $data,
    array $params = []
  ): object {
    foreach ($constructor->getParameters() as $param) {
      $name  = $param->getName();
      $value = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);

      $type = $param->getType();

      if ($type instanceof ReflectionNamedType) {
        $typeName = $type->getName();

        if (!$type->isBuiltin() && $value !== null) {
          // Tipo de classe: enum ou objeto
          $value = enum_exists($typeName)
            ? self::convertToEnum($typeName, $value)
            : self::convert($value, $typeName);
        } elseif ($typeName === "array" && is_array($value)) {
          // array → hidrata cada item recursivamente
          $value = self::hydrateArray($value);
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

      $property->setAccessible(true);

      // Se $source não tem a propriedade → seta null apenas se o tipo aceitar
      if (!array_key_exists($name, $data)) {
        if (!$property->isInitialized($instance)) {
          $type = $property->getType();
          // só seta null se tipo permite nullable ou não tem tipo declarado
          if ($type === null || $type->allowsNull()) {
            $property->setValue($instance, null);
          }
        }
        continue;
      }

      $value = $data[$name];
      $type  = $property->getType();

      if ($type instanceof ReflectionNamedType) {
        $typeName = $type->getName();

        if (!$type->isBuiltin() && $value !== null) {
          // Tipo de classe: enum ou objeto
          $value = enum_exists($typeName)
            ? self::convertToEnum($typeName, $value)
            : self::convert($value, $typeName);
        } elseif ($typeName === "array" && is_array($value)) {
          // array → hidrata cada item recursivamente
          $value = self::hydrateArray($value);
        }
      }

      $property->setValue($instance, $value);
    }

    return $instance;
  }

  // ---------------------------------------------------------------------------
  // Conversão de Enum
  // ---------------------------------------------------------------------------

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
    $enumCases      = $enumReflection->getMethod("cases")->invoke(null);

    foreach ($enumCases as $case) {
      if (strcasecmp($case->name, $value) === 0) {
        return $case;
      }
    }

    return $value;
  }
}
