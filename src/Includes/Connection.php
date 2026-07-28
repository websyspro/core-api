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
			match( CONNECT_LIST["Global"]->driver ){
				DriverType::PostgreSQL => self::getPostgresSQL(),
				DriverType::Sqlite => self::getSqlLite(),
				DriverType::MsSql => self::getMsSql(),
				DriverType::MySql => self::getMySQL(),
					default => self::getPdoException(),
			},
			CONNECT_LIST["Global"]->user,
			CONNECT_LIST["Global"]->pass, self::getPdoOptions()
		);

		return static::$handle;
	}

	private static function getMySQL(
	): string {
		return sprintf( "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
			CONNECT_LIST[ "Global" ]->host, CONNECT_LIST[ "Global" ]->port, CONNECT_LIST["Global"]->name
		);
	}

	private static function getPostgresSQL(
	): string {
		return sprintf( "pgsql:host=%s;port=%s;dbname=%s",
			CONNECT_LIST["Global"]->host, CONNECT_LIST["Global"]->port, CONNECT_LIST["Global"]->name
		);
	}
	
	private static function getSqlLite(
	): string {
		return sprintf( "sqlite:%s",
			CONNECT_LIST["Global"]->name
		);
	}
	
	private static function getMsSql(
	): string {
		return sprintf( "sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=1",
			CONNECT_LIST["Global"]->host, CONNECT_LIST["Global"]->port, CONNECT_LIST["Global"]->name
		);
	}

	private static function getPdoException(
	): PDOException {
		return throw new PDOException(
			sprintf( "Driver '%s' nao suportado", CONNECT_LIST["Global"]->driver->name )
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
		return CONNECT_LIST["Global"]->driver;
	}


  public static function query(
		string $sql,
		array $params = [],
		 bool $single = false,
		array $fetchAll = [],
	): array|object|null {
		try {
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
		} catch( PDOException $e ) {
			Logger::error( "Query error: " . $e->getMessage());
			throw $e;
		}
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
		try {
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
		} catch( PDOException $e ) {
			Logger::error( "Execute error: " . $e->getMessage());
			throw $e;
		}
	}

	public static function lastInsertId(
	): string {
		return static::$handle->lastInsertId();
	}
}
