<?php

namespace Websyspro\Server\Includes\Engines;

class MySqlEngine
extends AbstractEngine
{
  public function extractKeyArgs(
  ): array {
    return [
      "SELECT information_schema.columns.column_name as column_name
         FROM information_schema.columns 
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
          AND information_schema.columns.extra = 'auto_increment'
          AND information_schema.columns.column_key = 'PRI'", [ $this->table ]
    ];    
  }

  public function extractFieldsArgs(
  ): array {
    return [
      "SELECT information_schema.columns.column_name as column_name
	           ,information_schema.columns.data_type as data_type
         FROM information_schema.columns
        WHERE information_schema.columns.table_schema = database() 
          AND information_schema.columns.table_name = ?
     ORDER BY information_schema.columns.ordinal_position asc", [ $this->table ]
    ];
  }

  public function extractCountRows(
	): string {
		return sprintf(
			"SELECT count(*) as numRows FROM %s", $this->table
		);
	} 

  public function applyWhere(
  ): void {}

  public function applyOrderBy(
  ): void {}
  
  public function applyLimits(
  ): void  {}  
}