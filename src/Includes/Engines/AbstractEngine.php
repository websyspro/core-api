<?php

namespace Websyspro\Server\Includes\Engines;

use stdClass;
use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Connection;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;
use Websyspro\Server\Includes\Interfaces\FieldStructure;
use function strlen;

abstract class AbstractEngine
{
  public string $key;
  public string $table;
  public Collection $fields;

  public function __construct(
    public string $sql,
    protected ConnectionDNS $connectionDNS
  ){
    $this->normalizedSql();
    $this->extractTable();
    $this->extractKey();
    $this->extractFields();
  }

  abstract public function extractKeyArgs(
  ): array;

  abstract public function extractFieldsArgs(
  ): array;  

  public function normalizedSql(   
  ): void {
    $this->sql = preg_replace(
      [ "#\r\n#", "#\s*,\s*#", "#\s{2,}#" ],
      [ " ", ", ", " " ], $this->sql
    );
  }

  public function extractTable(
    int $parenteesCounties = 0,
    int $fromStart = 0
  ): void {
    for ($i = 0; $i < strlen($this->sql); $i++){
      if( $this->sql[$i] === "(" ){
        $parenteesCounties++;
      } else if( $this->sql[$i] === ")" ){
        $parenteesCounties--;
      }

      if( strtolower( substr( $this->sql, $i, 4 )) === "from" ){
        if( $parenteesCounties === 0 ){
          $fromStart = $i + 5;
          break;    
        }
      }
    }

    $this->table = preg_replace(
      "#[,\s].*$#", "", trim(
        substr( $this->sql, $fromStart )
      )
    );
  }

  public function extractKey(
  ): void {
    [ $sql, $params 
    ] = $this->extractKeyArgs();


    $single = Connection::set( $this->connectionDNS->schema )
      ->single( $sql, $params );

    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }
  }  

  public function validType(
    string $type
  ): string {
		return match( $type ){
      "datetime2" => "datetime",
      "bigint", "int" => "integer",
      "uniqueidentifier", "longtext", "varchar" => "text",
        default => $type
    };
	}  

  public function extractFields(
  ): void {
    [ $sql, $params 
    ] = $this->extractFieldsArgs();

    $this->fields = new Collection(
      Connection::set( $this->connectionDNS->schema )
        ->query( $sql, $params )
    );

    $this->fields = $this->fields->mapper(
      fn( object $field ) => new FieldStructure(
        name: $field->column_name,
        type: $this->validType(
          $field->data_type
        )
      )
    );
  }
}