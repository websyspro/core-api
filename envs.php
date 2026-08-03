<?php

use Websyspro\Server\Commons\Collection;
use Websyspro\Server\Includes\Enums\Driver;
use Websyspro\Server\Includes\Enums\Schema;
use Websyspro\Server\Includes\Interfaces\AppStructure;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;

if( defined( "ROOT" ) === false ) {
  define( "ROOT", __DIR__ . DIRECTORY_SEPARATOR );
}

if( defined( "APP" ) === false ){
  define( "APP", new AppStructure(
    port: 3000,
    version: 1,
    maxRequests: 1000,
    keepAliveTimeOut: 30,
    host: PHP_OS_FAMILY === "Windows" ? "localhost" : "localhost",
    workers: max(1, (int) shell_exec( "nproc" ))
  ));
}

if( defined( "CONNECT_LIST" ) === false ){
  define( "CONNECT_LIST", new Collection([
      new ConnectionDNS(
        schema: Schema::Global, 
        driver: Driver::MySql,
        host: "localhost", 
        port: "3306", 
        name: "edocente",
        user: "root", 
        pass: "qazwsx"
      ),
      new ConnectionDNS(
        schema: Schema::Crm,
        driver: Driver::MsSql,
        host: "localhost", 
        port: "1433", 
        name: "crm",
        user: "sa", 
        pass: "Qazwsx@123"
      ),
    ])
  );
}