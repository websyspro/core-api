<?php

namespace Websyspro\Server\Includes;

use Exception;
use Websyspro\Server\Includes\Drivers\AbstractSchema;
use Websyspro\Server\Includes\Drivers\MsSqlSchema;
use Websyspro\Server\Includes\Drivers\MySqlSchema;
use Websyspro\Server\Includes\Drivers\PostgreSQLSchema;
use Websyspro\Server\Includes\Drivers\SqliteSchema;
use Websyspro\Server\Includes\Enums\DriverType;

abstract class QueryView
{
  public readonly AbstractSchema $schema;
  public function __construct(
  ){
    $this->defineSchema();
  }

  abstract public function sql(): string;

  private function defineSchema(
  ): void {
    $this->schema = match( Connection::driver()){
      DriverType::PostgreSQL => new PostgreSQLSchema( $this->sql()),
      DriverType::Sqlite => new SqliteSchema( $this->sql()),
      DriverType::MySql => new MySqlSchema( $this->sql()),
      DriverType::MsSql => new MsSqlSchema( $this->sql()),
        default => throw new Exception( "" )
    };
  }
}