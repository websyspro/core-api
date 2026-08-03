<?php

namespace Websyspro\Server\Includes\Engines;

use stdClass;
use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Connection;
use Websyspro\Server\Includes\Enums\Query\EqualType;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;
use Websyspro\Server\Includes\Interfaces\FieldStructure;
use Websyspro\Server\Includes\Interfaces\WhereField;
use Websyspro\Server\Includes\Interfaces\QueryProps;
use function strlen;
use function sprintf;

abstract class AbstractEngine
{
  public string $key;
  public string $table;
  public Collection $fields;
  public Collection $whereField;
  private string $hash;

  // public int $numRows;
  // public Collection $rows;

  public function __construct(
    public string $sql,
    protected string $viewName = "cache",
    protected readonly QueryProps|null $queryProps = null,
    protected readonly ConnectionDNS|null $connectionDNS = null
  ){
    $this->extractBase();
    $this->extractRecordSet();
  }

  private function db(
  ): Connection|null {
    if ($this->connectionDNS == null) {
      return null;
    }

    return Connection::set(
      $this->connectionDNS->schema
    );
  }

  private function cacheFile(
  ): string {
    return sprintf(
       "%ssrc/Caches/%s.php", ROOT, $this->viewName
    );
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

    $single = $this->db()->single(
      $sql, $params
    );

    if( $single instanceof stdClass ) {
      $this->key = $single->column_name;
    }
  }  

  public function validType(
    string $type
  ): string {
		return match( $type ){
      "datetime2", "datetime", "date", "time" 
        => "date",
      "bigint", "int" 
        => "integer",
      "uniqueidentifier", "longtext", "varchar" 
        => "text",
        default => $type
    };
	}  

  public function extractFields(
  ): void {
    [ $sql, $params 
    ] = $this->extractFieldsArgs();

    $this->fields = new Collection(
      $this->db()->query( $sql, $params )
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

  private function extractBase(
  ): void {
    $this->normalizedSql();
    $this->hash = md5( $this->sql );

    if( $this->loadFromCache() === false ){
      $this->extractTable();
      $this->extractKey();
      $this->extractFields();
      $this->saveToCache();
    }
  }

  abstract public function extractCountRows(
  ): string;  

  public function applyWhere(
    string|null $equalType = null
  ): void {
    if ($this->queryProps->where === null) {
			$this->sql = sprintf(
				"SELECT * FROM (%s) AS %s WHERE 1=1", $this->sql, $this->table
			);
		} else {
			foreach( $this->queryProps->where as $name => $text ){
        $fieldStructure = $this->fields->where(
          fn( FieldStructure $fieldStrucuture )=> (
            $fieldStrucuture->name === $name
          ) 
        );

        if( $fieldStructure->exist()){
          if( isset( $this->whereField ) === false ){
            $this->whereField = new Collection();
          }

          if ((bool)preg_match( "#\[(?:,|:)\]#", $text )) {
            if ((bool)preg_match( "#\[:]#", $text )) {
              $equalType = EqualType::Interval;
            } else 
            if ((bool)preg_match( "#\[,]#", $text )) {
              $equalType = EqualType::List;
            }
          } else {
            $equalType = EqualType::Equal;
          }

          $this->whereField->add(
            new WhereField(
              table: $this->table, field: $name,
              equalType: $equalType,
              callbacks: [],
              values: [] 
            )
          );
        }
			}
		}
  }

  abstract public function applyOrderBy(
  ): void;
  
  abstract public function applyLimits(
  ): void;

  public function extractRecordSet(
  ): void {
  }
}
