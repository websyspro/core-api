<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Includes\Enums\ServiceType;

class AppStructure
{
  public function __construct(
    public readonly string $host,
    public readonly int $port,
    public readonly int $version,
    public readonly int $keepAliveTimeOut,
    public readonly int $maxRequests,
    public readonly int $workers,
    public readonly ServiceType $serviceType = ServiceType::Tcp,
  ){}
}