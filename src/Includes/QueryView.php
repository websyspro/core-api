<?php

namespace Websyspro\Server\Includes;

use Exception;
use JsonSerializable;
use Websyspro\Server\Includes\Engines\AbstractEngine;
use Websyspro\Server\Includes\Engines\MsSqlEngine;
use Websyspro\Server\Includes\Engines\MySqlEngine;
use Websyspro\Server\Includes\Engines\PostgreSQLEngine;
use Websyspro\Server\Includes\Engines\SqliteEngine;
use Websyspro\Server\Includes\Enums\DriverSchema;
use Websyspro\Server\Includes\Enums\DriverType;

abstract class QueryView
{
  public AbstractEngine $engine;
  protected DriverSchema $schema;

  public function __construct(
  ){
    $this->defineEngine();
  }

  abstract public function sql(): string;

  private function defineEngine(
  ): void {
    $driverType = Connection::driver(
      $this->schema
    );

    $this->engine = match( $driverType ){
      DriverType::PostgreSQL => new PostgreSQLEngine( $this->sql(), $this->schema ),
      DriverType::Sqlite => new SqliteEngine( $this->sql(), $this->schema ),
      DriverType::MySql => new MySqlEngine( $this->sql(), $this->schema ),
      DriverType::MsSql => new MsSqlEngine( $this->sql(), $this->schema ),
        default => throw new Exception( "" )
    };
  }

  /**
   * Permite serialização JSON automática
   * Retorna os metadados extraídos pelo Engine
   */
  public function jsonSerialize(): array
  {
    return [
      'table'  => $this->engine->table,
      'key'    => $this->engine->key,
      'fields' => $this->engine->fields->toArray(),
      'sql'    => $this->engine->sql,
      'schema' => $this->schema->name,
    ];
  }
}