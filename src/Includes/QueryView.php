<?php

namespace Websyspro\Server\Includes;

use Websyspro\Server\Includes\Decorators\Database\OriginSchema;
use Websyspro\Server\Includes\Exceptions\InternalServerError;
use Websyspro\Server\Includes\Interfaces\QueryProps;
use Websyspro\Server\Includes\Engines\PostgreSQLEngine;
use Websyspro\Server\Includes\Engines\AbstractEngine;
use Websyspro\Server\Includes\Engines\SqliteEngine;
use Websyspro\Server\Includes\Engines\MsSqlEngine;
use Websyspro\Server\Includes\Engines\MySqlEngine;
use Websyspro\Server\Includes\Enums\Driver;
use Websyspro\Server\Includes\Enums\Schema;
use ReflectionAttribute;
use ReflectionClass;

abstract class QueryView
{
  public AbstractEngine $engine;
  public function __construct(
    public readonly QueryProps $queryProps
  ){
    $this->defineEngine();
    $this->defineRecordSet();
  }

  abstract public function sql(
  ): string;

  private function defineSchema(
  ): Schema|null {
    $attributesArr = (
      new ReflectionClass($this)
    )->getAttributes( OriginSchema::class );

    if( empty( $attributesArr )){
      throw new InternalServerError(
        "Schema not defined"
      );
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
    $connectionDns = Connection::connectionDNS( 
      $this->defineSchema()
    );

    $cacheName = "cache" . strtolower(
      preg_replace( "#([A-Z])#", "-$1", (
        new ReflectionClass($this)
      )->getShortName() )
    );

    if( $connectionDns->driver === Driver::PostgreSQL ){
      $this->engine = new PostgreSQLEngine(
        $this->sql(), $cacheName, $this->queryProps, $connectionDns
      );
    } else if( $connectionDns->driver === Driver::Sqlite ){
      $this->engine = new SqliteEngine(
        $this->sql(), $cacheName, $this->queryProps, $connectionDns
      );
    } else if( $connectionDns->driver === Driver::MsSql ){
      $this->engine = new MsSqlEngine(
        $this->sql(), $cacheName, $this->queryProps, $connectionDns
      );
    } else if( $connectionDns->driver === Driver::MySql ){
      $this->engine = new MySqlEngine(
        $this->sql(), $cacheName, $this->queryProps, $connectionDns
      );
    }
  }

  private function defineRecordSet(
  ): void {
    $this->engine->applyWhere();
  }
}