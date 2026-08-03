<?php

namespace Websyspro\Server\Includes;

use Exception;
use TypeError;
use function date;
use function microtime;
use function round;
use function sprintf;
use function get_class;

class Logger
{
  private static float $lastTime = 0.0;

  public static function info(
    string $message
  ): void {
    Logger::log(
      "INFO", "\033[32m", $message
    );
  }

  public static function error(
    string $message
  ): void {
    Logger::log(
      "ERROR", "\033[31m", $message
    );
  }

  public static function warn(
    string $message
  ): void {
    Logger::log(
      "WARN", "\033[33m", $message
    );
  }

  public static function debug(
    string $message
  ): void {
    Logger::log(
      "DEBUG", "\033[36m", $message
    );
  }

  public static function errorInRuntime(
    Exception|TypeError $exception
  ): void {
    Logger::error( 
      sprintf( "[%s] %s in %s on line %d",
        get_class( $exception ),
          $exception->getMessage(),
          $exception->getFile(),
          $exception->getLine()
      )
    );
  }

  private static function log(
    string $level,
    string $color,
    string $message
  ): void {
    $now  = microtime(true);
    $diff = Logger::$lastTime > 0
      ? round(( $now - Logger::$lastTime ) * 1000)
      : 0;

    Logger::$lastTime = $now;

    $timestamp = date( "Y-m-d H:i:s" );
    $reset = "\033[0m";

    $line = sprintf(
      "[%s] %s %s +%sms",
      $timestamp,
      $level,
      $message,
      $diff
    );

    // No modo Apache/FPM não pode fazer echo antes dos headers
    // redireciona para error_log (aparece no error.log do Apache/PHP)
    if (defined("APP") && APP instanceof \Websyspro\Server\Includes\Interfaces\AppStructure
      && APP->serviceType === \Websyspro\Server\Includes\Enums\ServiceType::Apache
    ) {
      error_log($line);
      return;
    }

    echo sprintf(
      "%s[%s] %s%s %s %s+%sms%s\n",
      $color,
      $timestamp,
      $level,
      $reset,
      $message,
      $color,
      $diff,
      $reset
    );
  }
}
