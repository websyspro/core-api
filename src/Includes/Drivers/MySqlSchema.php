<?php

namespace Websyspro\Server\Includes\Drivers;

use stdClass;
use Websyspro\Server\Includes\Connection;

class MySqlSchema
extends AbstractSchema
{
  public function extractKey(
  ): void {
    $single = Connection::single(
      "SELECT information_schema.columns.column_name as column_name
         FROM information_schema.columns 
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
          AND information_schema.columns.extra='auto_increment'
          AND information_schema.columns.column_key='PRI'", [ $this->table ]
    );
    
    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }    
  }  
}