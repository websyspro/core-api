<?php

use Websyspro\Server\Applications\Crm\CrmModule;
use Websyspro\Server\Applications\Global\GlobalModule;
use Websyspro\Server\Includes\WorkerServer;

$ws = new WorkerServer();
$ws->registerModules([
  CrmModule::class,
  GlobalModule::class
]);

$ws->start();