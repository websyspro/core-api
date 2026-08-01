<?php

namespace Websyspro\Server\Includes\Engines;

use stdClass;
use Websyspro\Server\Includes\Connection;

class PostgreSQLEngine
extends AbstractEngine
{
  public function extractKeyArgs(
  ): array {
    return [
      "SELECT information_schema.key_column_usage.column_name
         FROM information_schema.table_constraints
      	     ,information_schema.key_column_usage
        where information_schema.table_constraints.table_name = ?
          AND information_schema.table_constraints.constraint_type = 'PRIMARY KEY'
          AND information_schema.table_constraints.table_schema = CURRENT_SCHEMA()
          AND information_schema.key_column_usage.constraint_name = information_schema.table_constraints.constraint_name 
          AND information_schema.key_column_usage.table_schema  = information_schema.table_constraints.table_schema", [ $this->table ]
    ];    
  }

  public function extractFieldsArgs(
  ): array {
    return [];
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