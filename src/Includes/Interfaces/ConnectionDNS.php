<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Includes\Enums\Driver;
use Websyspro\Server\Includes\Enums\Schema;

class ConnectionDNS
{
  public function __construct(
    public readonly Driver $driver,
    public readonly Schema $schema,
    public readonly string $host,
    public readonly string $name,
    public readonly string $port,
    public readonly string $user,
    public readonly string $pass
  ){}  
}