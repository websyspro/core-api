<?php

namespace Websyspro\Server\Includes;

use Exception;
use ReflectionAttribute;
use ReflectionClass;
use Websyspro\Server\Includes\Decorators\Database\OriginSchema;
use Websyspro\Server\Includes\Engines\AbstractEngine;
use Websyspro\Server\Includes\Engines\MsSqlEngine;
use Websyspro\Server\Includes\Engines\MySqlEngine;
use Websyspro\Server\Includes\Engines\PostgreSQLEngine;
use Websyspro\Server\Includes\Engines\SqliteEngine;
use Websyspro\Server\Includes\Enums\Driver;
use Websyspro\Server\Includes\Enums\Schema;

abstract class QueryView
{
  public AbstractEngine $engine;
  public function __construct(
  ){
    $this->defineEngine();
  }

  abstract public function sql(): string;

  private function defineSchema(
  ): Schema|null {
    $attributesArr = (
      new ReflectionClass($this)
    )->getAttributes( OriginSchema::class );

    if( empty( $attributesArr )){
      new Exception( "Schema not defined" );
    }

    [ $attribute ] = $attributesArr;
    if( $attribute instanceof ReflectionAttribute ){
      $originSchema = $attribute->newInstance();
      if( $originSchema instanceof OriginSchema ){
        return $originSchema->schema;
      }
    }

    return null;
  }

  private function defineEngine(
  ): void {
    $dns = Connection::connectionDNS(
      $this->defineSchema()
    );

    if( $dns->driver === Driver::PostgreSQL ){
      $this->engine = new PostgreSQLEngine( $this->sql(), $dns );
    } else if( $dns->driver === Driver::Sqlite ){
      $this->engine = new SqliteEngine( $this->sql(), $dns );
    } else if( $dns->driver === Driver::MsSql ){
      $this->engine = new MsSqlEngine( $this->sql(), $dns );
    } else if( $dns->driver === Driver::MySql ){
      $this->engine = new MySqlEngine( $this->sql(), $dns );
    }
  }
}