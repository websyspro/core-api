<?php

namespace Websyspro\Server\Includes\Drivers;

class SqliteSchema
extends AbstractSchema
{
  public function extractKey(
  ): void {}

  public function validKey(
    object $field
  ): bool {
		return false;
	}  
  
  public function extractFields(
  ): void {}  
}