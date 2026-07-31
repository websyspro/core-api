<?php

namespace Websyspro\Server\Includes;

use PDO;
use PDOException;
use PDOStatement;
use Websyspro\Server\Includes\Enums\Driver;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;
use Websyspro\Server\Includes\Enums\Schema;
use function array_slice;
use function sprintf;
use function defined;
use function count;

class Connection
{
	private static array $handles = [];
	private static array $statements = [];
	
	public function __construct(
		private ConnectionDNS $connectionDNS
	){}

	public static function getConnectionDNSBySchema(
		Schema $schema
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
		Schema $schema
	): Connection|null {
		return new static( 
			self::getConnectionDNSBySchema( $schema )
		);
	}	

	public static function connectionDNS(
		Schema $schema
	): ConnectionDNS|null {
		return self::getConnectionDNSBySchema($schema);
	}	

	public function connect(
	): void {
		if ( isset( self::$handles[ $this->connectionDNS->schema->name ]) === false ){
			self::$handles[ $this->connectionDNS->schema->name ] = new PDO(
				match( $this->connectionDNS->driver ) {
					Driver::PostgreSQL => $this->getPostgresSQL(),
					Driver::Sqlite => $this->getSqlLite(),
					Driver::MsSql => $this->getMsSql(),
					Driver::MySql => $this->getMySQL(),
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
			sprintf("Driver '%s' nao suportado", $this->connectionDNS->driver->name)
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

	private function statementKey(
		string $sql
	): string {
		return sprintf(
			"%s:%s", $this->connectionDNS->schema->name, $sql
		);
	}

  public function query(
		string $sql,
		array $params = [],
		bool $single = false,
		array $fetchAll = [],
	): array|object|null {
		$this->connect();

		$statementKey = $this->statementKey( $sql );
		if ( isset( self::$statements[ $statementKey ]) === false ) {
			if (count(self::$statements) > 1000) {
				self::$statements = array_slice(
					self::$statements, -500, 500, true
				);
			}

			self::$statements[ $statementKey ] = self::$handles[
				$this->connectionDNS->schema->name
			]->prepare( $sql );
		}

		$statements = self::$statements[ $statementKey ];
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
		return static::query( 
			$sql, $params, true
		);
  }	

	public function execute(
		string $sql, 
		array $params = [],
		int $rowCount = 0
	): int {
		$this->connect();
		
		$statementKey = $this->statementKey( $sql );
		if (!isset(self::$statements[$statementKey])) {
			if (count(self::$statements) > 1000) {
				self::$statements = array_slice(
					self::$statements, -500, 500, true
				);
			}

			self::$statements[ $statementKey ] = self::$handles[
				$this->connectionDNS->schema->name
			]->prepare( $sql );
		}

		$statements = self::$statements[ $statementKey ];
		if ($statements->execute( $params )) {
			$rowCount = $statements->rowCount();
		}
		
		$statements->closeCursor();
		return $rowCount;
	}

	public function lastInsertId(
	): string {
		return $this->connect()->lastInsertId();
	}
}
