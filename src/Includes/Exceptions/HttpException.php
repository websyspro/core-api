<?php

namespace Websyspro\Server\Includes\Exceptions;

use Exception;
use Websyspro\Server\Includes\Enums\Server\HttpStatus;

abstract class HttpException extends Exception
{
  public function __construct(
    string $message,
    public readonly HttpStatus $httpStatus
  ) {
    parent::__construct($message, $httpStatus->value);
  }
}
