<?php

namespace Websyspro\Server\Includes\Engines;

use JsonSerializable;
use stdClass;
use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Connection;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;
use Websyspro\Server\Includes\Interfaces\FieldStructure;
use function strlen;

abstract class AbstractEngine
{
  public string $key;
  public string $table;
  public Collection $fields;

  private string $hash;

  public function __construct(
    public string $sql,
    protected string $viewName = "cache",
    protected ConnectionDNS|null $connectionDNS = null
  ){
    $this->normalizedSql();
    $this->hash = md5( $this->sql );

    if( $this->loadFromCache() === false ){
      $this->extractTable();
      $this->extractKey();
      $this->extractFields();
      $this->saveToCache();
    }
  }

  private function cacheFile(
  ): string {
    return __DIR__ . "/../../Caches/{$this->viewName}.php";
  }

  private function loadFromCache(
  ): bool {
    $file = $this->cacheFile();

    if( file_exists( $file ) === false ){
      return false;
    }

    $cache = require $file;
    if (($cache[ "hash" ] ?? null ) !== $this->hash) {
      return false;
    }

    $this->table = $cache[ "table" ];
    $this->key = $cache[ "key" ];
    $this->fields = new Collection(
      array_map(
        fn( array $f ) => new FieldStructure(
          $f['name'], $f['type']
        ), $cache['fields']
      )
    );

    return true;
  }

  private function saveToCache(
    Collection $content = new Collection()
  ): void  {
    $file = $this->cacheFile();
    $dir = dirname( $file );

    if (!is_dir( $dir )) {
      mkdir( $dir, 0755, true );
    }

    $content->add( "<?php" );
    $content->add( "" );
    $content->add( "return [" );
    $content->add( "\t\"hash\" => \"{$this->hash}\"," );
    $content->add( "\t\"table\" => \"{$this->table}\"," );
    $content->add( "\t\"key\" => \"{$this->key}\"," );
    $content->add( "\t\"fields\" => [" );
    $content->add( $this->fields->mapper(fn( FieldStructure $field ) => (
      "\t\t[ \"name\" => \"{$field->name}\", \"type\" => \"{$field->type}\" ],"
    ))->joinWithBreak());
    $content->add( "\t]" );
    $content->add( "];" );


    file_put_contents(
      $file, $content->joinWithBreak()
    );
  }

  abstract public function extractKeyArgs(
  ): array;

  abstract public function extractFieldsArgs(
  ): array;  

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
      "#[,\s].*$#", "", trim(
        substr( $this->sql, $fromStart )
      )
    );
  }

  public function extractKey(
  ): void {
    [ $sql, $params 
    ] = $this->extractKeyArgs();

    $single = Connection::set( 
      $this->connectionDNS->schema
    )->single( $sql, $params );

    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }
  }  

  public function validType(
    string $type
  ): string {
		return match( $type ){
      "datetime2" => "datetime",
      "bigint", "int" => "integer",
      "uniqueidentifier", "longtext", "varchar" => "text",
        default => $type
    };
	}  

  public function extractFields(
  ): void {
    [ $sql, $params 
    ] = $this->extractFieldsArgs();

    $this->fields = new Collection(
      Connection::set( $this->connectionDNS->schema )
        ->query( $sql, $params )
    );

    $this->fields = $this->fields->mapper(
      fn( object $field ) => new FieldStructure(
        name: $field->column_name,
        type: $this->validType(
          $field->data_type
        )
      )
    );
  }
}
