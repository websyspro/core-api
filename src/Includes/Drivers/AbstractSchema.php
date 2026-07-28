<?php

namespace Websyspro\Server\Includes\Drivers;

use Websyspro\Server\Commons\Collection;
use function strlen; 

abstract class AbstractSchema
{
  public string $key;
  public string $table;
  public Collection $fields;

  public function __construct(
    public string $sql
  ){
    $this->normalizedSql();
    $this->extractTable();
    $this->extractKey();
  }

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
      "#[,\s].*$#", "", trim( substr( $this->sql, $fromStart ))
    );
  }

  abstract public function extractKey(
  ): void;
}