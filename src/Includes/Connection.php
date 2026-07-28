<?php

namespace Websyspro\Server\Includes;

use PDO;
use PDOException;
use PDOStatement;
use Websyspro\Server\Includes\Enums\DriverType;
use function sprintf;
use function defined;

class Connection
{
	private static PDO $handle;
	private static array $statements = [];

	public static function connect(
	): PDO {
		if( isset( static::$handle )){
			return static::$handle;
		}

		static::$handle = new PDO(
			match( CONNECT_LIST["Crm"]->driver ){
				DriverType::PostgreSQL => self::getPostgresSQL(),
				DriverType::SqlServer => self::getSqlServer(),
				DriverType::Sqlite => self::getSqlLite(),
				DriverType::MySql => self::getMySQL(),
					default => self::getPdoException(),
			},
			CONNECT_LIST["Crm"]->user,
			CONNECT_LIST["Crm"]->pass, self::getPdoOptions()
		);

		return static::$handle;
	}

	private static function getMySQL(
	): string {
		return sprintf( "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
			CONNECT_LIST["Crm"]->host, CONNECT_LIST["Crm"]->port, CONNECT_LIST["Crm"]->name
		);
	}

	private static function getPostgresSQL(
	): string {
		return sprintf( "pgsql:host=%s;port=%s;dbname=%s",
			CONNECT_LIST["Crm"]->host, CONNECT_LIST["Crm"]->port, CONNECT_LIST["Crm"]->name
		);
	}
	
	private static function getSqlLite(
	): string {
		return sprintf( "sqlite:%s",
			CONNECT_LIST["Crm"]->name
		);
	}
	
	private static function getSqlServer(
	): string {
		return sprintf( "sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=1",
			CONNECT_LIST["Crm"]->host, CONNECT_LIST["Crm"]->port, CONNECT_LIST["Crm"]->name
		);
	}

	private static function getPdoException(
	): PDOException {
		return throw new PDOException(
			sprintf( "Driver '%s' nao suportado", CONNECT_LIST["Crm"]->driver->name )
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
		return CONNECT_LIST["Crm"]->driver;
	}


  public static function query(
		string $sql,
		array $params = [],
		array $fetchAll = [],
	): array {
    static::connect();

		if( isset( static::$statements[ $sql ]) === false ) {
			static::$statements[ $sql ] = static::$handle->prepare( $sql );
		}

		$statements = static::$statements[ $sql ];
		if( $statements instanceof PDOStatement ){
			if( $statements->execute( $params )){
				$fetchAll = $statements->fetchAll();
			}
		}
		
		$statements->closeCursor();
		return $fetchAll;
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
