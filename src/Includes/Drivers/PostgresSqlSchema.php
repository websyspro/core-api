<?php

namespace Websyspro\Server\Includes\Drivers;

use stdClass;
use Websyspro\Server\Includes\Connection;

class PostgreSQLSchema
extends AbstractSchema
{
  public function extractKey(
  ): void {
    $single = Connection::single(
      "SELECT information_schema.key_column_usage.column_name
         FROM information_schema.table_constraints
      	     ,information_schema.key_column_usage
        where information_schema.table_constraints.table_name = ?
          AND information_schema.table_constraints.constraint_type = 'PRIMARY KEY'
          AND information_schema.table_constraints.table_schema = CURRENT_SCHEMA()
          AND information_schema.key_column_usage.constraint_name = information_schema.table_constraints.constraint_name 
          AND information_schema.key_column_usage.table_schema  = information_schema.table_constraints.table_schema", [ $this->table ]
    );
    
    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }    
  }
}