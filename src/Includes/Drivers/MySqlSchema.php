<?php

namespace Websyspro\Server\Includes\Drivers;

use stdClass;
use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Connection;
use Websyspro\Server\Includes\Interfaces\FieldStructure;

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
          AND information_schema.columns.extra = 'auto_increment'
          AND information_schema.columns.column_key = 'PRI'", [ $this->table ]
    );

    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }    
  }

  public function validKey(
    object $field
  ): bool {
    return $field->extra === "auto_increment"
        && $field->column_key === "PRI";
  }
  
  public function extractFields(
  ): void {
    $fields = Connection::query(
      "SELECT information_schema.columns.column_name as column_name
	           ,information_schema.columns.data_type as data_type
	           ,information_schema.columns.column_key as column_key
	           ,information_schema.columns.extra as extra
         FROM information_schema.columns
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
     ORDER BY information_schema.columns.ordinal_position asc", [ $this->table ]
    );

    if( empty( $fields ) === false ){
      $this->fields = Collection::create( $fields )->mapper(
        fn( object $field ) => new FieldStructure(
          name: $field->column_name,
          type: $field->data_type,
          key: $this->validKey( $field )
        )
      );
    }
  }  
}