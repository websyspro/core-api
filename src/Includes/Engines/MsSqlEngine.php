<?php

namespace Websyspro\Server\Includes\Engines;

class MsSqlEngine
extends AbstractEngine
{
  public function extractKeyArgs(
  ): array {
    return [
			"SELECT kcu.column_name as column_name
	 			 FROM information_schema.table_constraints tc
				 JOIN information_schema.key_column_usage kcu
	  			 ON kcu.constraint_schema = tc.constraint_schema
	 				AND kcu.constraint_name = tc.constraint_name
	 				and kcu.table_schema = tc.table_schema
	 				and kcu.table_name = tc.table_name
   			where tc.constraint_type = 'PRIMARY KEY'
	 				and kcu.table_name = ?
	 				and kcu.column_name not in ( 
  		 select b.name
				 from sys.foreign_key_columns a 
				 join sys.columns b on a.parent_column_id =b.column_id 
	 			  and a.parent_object_id=b.object_id 
				 join sys.columns c on a.constraint_column_id=c.column_id 
	 				and a.referenced_object_id=c.object_id 
   			where lower( object_name( parent_object_id )) = lower( ? ))
   	 order by kcu.table_schema, kcu.table_name, kcu.constraint_name", [ $this->table, $this->table ]
		];
  }

  public function extractFieldsArgs(
  ): array {
		return [
			"SELECT information_schema.columns.column_name
		         ,information_schema.columns.data_type
	       FROM information_schema.columns
	      WHERE information_schema.columns.table_name = ?
     ORDER BY information_schema.columns.ordinal_position asc ", [ $this->table ]
		];
	}
}