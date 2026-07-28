<?php

use Websyspro\Server\Includes\Enums\DriverType;
use Websyspro\Server\Includes\Interfaces\AppStructure;
use Websyspro\Server\Includes\Interfaces\ConnectionDNS;

if( defined( "App" ) === false ){
  define( "App", new AppStructure(
    port: 3000,
    version: 1,
    maxRequests: 1000,
    keepAliveTimeOut: 30,
    host: PHP_OS_FAMILY === 'Windows' ? 'localhost' : '0.0.0.0',
    workers: max(1, (int) shell_exec('nproc'))
  ));
}

if( defined( "CONNECT_LIST" ) === false ){
  define( "CONNECT_LIST", [
    "Global" => new ConnectionDNS(
      driver: DriverType::MySql, 
      host: "localhost", 
      port: "3308", 
      name: "app",
      user: "root", 
      pass: "Qazwsx@123"
    ),
    "Crm" => new ConnectionDNS(
      driver: DriverType::MsSql, 
      host: "localhost", 
      port: "1433", 
      name: "crm",
      user: "sa", 
      pass: "Qazwsx@123"
    ),
  ]);
}