<?php

namespace Websyspro\Server\Includes\Engines;

use stdClass;
use Websyspro\Server\Includes\Connection;

class MySqlEngine
extends AbstractEngine
{
  public function extractKey(
  ): void {
    $single = Connection::set( $this->schema )->single(
      "SELECT information_schema.columns.column_name as column_name
         FROM information_schema.columns 
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
          AND information_schema.columns.extra = 'auto_increment'
          AND information_schema.columns.column_key = 'PRI'", [ $this->table ]
    );

    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }    
  }

  public function extractFieldsArr(
  ): array {
    return Connection::set( $this->schema )->query(
      "SELECT information_schema.columns.column_name as column_name
	           ,information_schema.columns.data_type as data_type
         FROM information_schema.columns
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
     ORDER BY information_schema.columns.ordinal_position asc", [ $this->table ]
    );
  }
}