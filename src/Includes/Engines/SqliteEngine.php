<?php

namespace Websyspro\Server\Includes\Engines;

class SqliteEngine
extends AbstractEngine
{
  public function extractKeyArgs(
  ): array {
    return [];
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