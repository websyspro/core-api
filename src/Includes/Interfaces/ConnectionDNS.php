<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Includes\Enums\DriverType;

class ConnectionDNS
{
  public function __construct(
    public readonly DriverType $driver,
    public readonly string $host,
    public readonly string $name,
    public readonly string $port,
    public readonly string $user,
    public readonly string $pass
  ){}  
}