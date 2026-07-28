<?php

namespace Websyspro\Server\Includes;

use PDO;
use PDOException;
use PDOStatement;
use Websyspro\Server\Includes\Enums\DriverSchema;
use Websyspro\Server\Includes\Enums\DriverType;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;
use function sprintf;
use function defined;
use function array_slice;
class Connection
{
	private static array $handles = []; // ✅ Um handle por schema
	private static array $statements = [];
	
	public function __construct(
		private ConnectionDNS $connectionDNS
	){}

	public static function getConnectionDNSBySchema(
		DriverSchema $schema
	): ConnectionDNS|null {
		if( defined( "CONNECT_LIST" )){
			$connectionDNSArr = CONNECT_LIST->where( 
				fn( ConnectionDNS $dns ) => $dns->schema === $schema
			);

			if( $connectionDNSArr->empty() === false ){
				if( $connectionDNSArr->first() instanceof ConnectionDNS ){
					return $connectionDNSArr->first();
				}
			}
		}

		return null;		
	}

	public static function set(
		DriverSchema $schema
	): Connection|null {
		return new static( self::getConnectionDNSBySchema($schema) );
	}	

	public static function driver(
		DriverSchema $schema
	): DriverType|null {
		$dns = self::getConnectionDNSBySchema($schema);
		return $dns?->type;
	}	

	public function connect(
	): void {
		if ( isset(self::$handles[ $this->connectionDNS->schema->name ]) === false ){
			self::$handles[ $this->connectionDNS->schema->name ] = new PDO(
				match ($this->connectionDNS->type) {
					DriverType::PostgreSQL => $this->getPostgresSQL(),
					DriverType::Sqlite => $this->getSqlLite(),
					DriverType::MsSql => $this->getMsSql(),
					DriverType::MySql => $this->getMySQL(),
					default => $this->getPdoException(),
				},
				$this->connectionDNS->user,
				$this->connectionDNS->pass,
				self::getPdoOptions()
			);
		}
	}

	private function getMySQL(
	): string {
		return sprintf( "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
			$this->connectionDNS->host, $this->connectionDNS->port, $this->connectionDNS->name
		);
	}

	private function getPostgresSQL(
	): string {
		return sprintf( "pgsql:host=%s;port=%s;dbname=%s",
			$this->connectionDNS->host, $this->connectionDNS->port, $this->connectionDNS->name
		);
	}

	private function getSqlLite(
	): string {
		return sprintf( "sqlite:%s", 
			$this->connectionDNS->name
		);
	}

	private function getMsSql(
	): string {
		return sprintf( "sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=1",
			$this->connectionDNS->host, $this->connectionDNS->port, $this->connectionDNS->name
		);
	}

	private function getPdoException(
	): PDOException {
		throw new PDOException(
			sprintf("Driver '%s' nao suportado", $this->connectionDNS->type->name)
		);
	}

	private function getPdoOptions(
	): array {
		return [
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
		];
	}

  public function query(
		string $sql,
		array $params = [],
		bool $single = false,
		array $fetchAll = [],
	): array|object|null {
		$this->connect();

		$stmtKey = $this->connectionDNS->schema->name . ':' . $sql;
		if ( isset( self::$statements[ $stmtKey ]) === false ) {
			self::$statements[$stmtKey] = self::$handles[
				$this->connectionDNS->schema->name
			]->prepare( $sql );
		}

		$statements = self::$statements[$stmtKey];
		if ($statements instanceof PDOStatement) {
			if ($statements->execute( $params )) {
				$fetchAll = $single === false
					? $statements->fetchAll()
					: $statements->fetch();
			}
		}
		
		$statements->closeCursor();
		return $fetchAll;
  }

  public function single(
		string $sql,
		array $params = []
	): object|null {
		return static::query( $sql, $params, true );
  }	

	public function execute(
		string $sql, 
		array $params = [],
		int $rowCount = 0
	): int {
		$this->connect();
		
		$stmtKey = $this->connectionDNS->schema->name . ':' . $sql;
		if (!isset(self::$statements[$stmtKey])) {
			self::$statements[$stmtKey] = self::$handles[
				$this->connectionDNS->schema->name
			]->prepare( $sql );
		}

		$statements = self::$statements[$stmtKey];
		if ($statements->execute($params)) {
			$rowCount = $statements->rowCount();
		}
		
		$statements->closeCursor();
		return $rowCount;
	}

	public function lastInsertId(): string
	{
		return $this->connect()->lastInsertId();
	}
}
