<?php

namespace Websyspro\Server\Includes;

use PDO;
use PDOException;
use PDOStatement;
use Websyspro\Server\Includes\Enums\DriverType;
use function sprintf;
use function defined;
use function array_slice;

class Connection
{
	private static PDO $handle;
	private static array $statements = [];
	private static string $module;

	public static function connect(
	): PDO {
		if( isset( static::$handle )){
			return static::$handle;
		}

		static::$handle = new PDO(
			match( CONNECT_LIST[ static::getModule() ]->driver ){
				DriverType::PostgreSQL => self::getPostgresSQL(),
				DriverType::Sqlite => self::getSqlLite(),
				DriverType::MsSql => self::getMsSql(),
				DriverType::MySql => self::getMySQL(),
					default => self::getPdoException(),
			},
			CONNECT_LIST[ static::getModule() ]->user,
			CONNECT_LIST[ static::getModule() ]->pass, self::getPdoOptions()
		);

		return static::$handle;
	}

	private static function getModule(
	): string {
		[ static::$module ] = array_slice(
			explode( "/", $_SERVER[ "REQUEST_URI" ]), 2, 1
		);

		return "crm";
	}

	private static function getMySQL(
	): string {
		return sprintf( "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
			CONNECT_LIST[  static::getModule()  ]->host, CONNECT_LIST[  static::getModule()  ]->port, CONNECT_LIST[ static::getModule() ]->name
		);
	}

	private static function getPostgresSQL(
	): string {
		return sprintf( "pgsql:host=%s;port=%s;dbname=%s",
			CONNECT_LIST[ static::getModule() ]->host, CONNECT_LIST[ static::getModule() ]->port, CONNECT_LIST[ static::getModule() ]->name
		);
	}
	
	private static function getSqlLite(
	): string {
		return sprintf( "sqlite:%s",
			CONNECT_LIST[ static::getModule() ]->name
		);
	}
	
	private static function getMsSql(
	): string {
		return sprintf( "sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=1",
			CONNECT_LIST[ static::getModule() ]->host, CONNECT_LIST[ static::getModule() ]->port, CONNECT_LIST[ static::getModule() ]->name
		);
	}

	private static function getPdoException(
	): PDOException {
		return throw new PDOException(
			sprintf( "Driver '%s' nao suportado", CONNECT_LIST[ static::getModule() ]->driver->name )
		);
	}

	private static function getPdoOptions(
	): array {
		return [
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
		];
	}

	public static function driver(
	): DriverType {
		if( defined( "CONNECT_LIST" ) === false ){}
		return CONNECT_LIST[ static::getModule() ]->driver;
	}


  public static function query(
		string $sql,
		array $params = [],
		 bool $single = false,
		array $fetchAll = [],
	): array|object|null {
		static::connect();

		if( isset( static::$statements[ $sql ]) === false ) {
			static::$statements[ $sql ] = static::$handle->prepare( $sql );
		}

		$statements = static::$statements[ $sql ];
		if( $statements instanceof PDOStatement ){
			if( $statements->execute( $params )){
				$fetchAll = $single === false
					? $statements->fetchAll()
					: $statements->fetch();
			}
		}
		
		$statements->closeCursor();
		return $fetchAll;
  }

  public static function single(
		string $sql,
		array $params = []
	): object {
		return static::query( $sql, $params, true );
  }	

	public static function execute(
		string $sql, 
		array $params = [],
		int $rowCount = 0
	): int {
		static::connect();

		if( isset(static::$statements[$sql]) === false ){
			static::$statements[ $sql ] = static::$handle->prepare( $sql );
		}

		$statements = static::$statements[$sql];
		if( $statements->execute( $params )){
			$rowCount = $statements->rowCount();
		}
		
		$statements->closeCursor();
		return $rowCount;
	}

	public static function lastInsertId(
	): string {
		return static::$handle->lastInsertId();
	}
}
