<?php

use Websyspro\Server\Applications\Crm\CrmModule;
use Websyspro\Server\Includes\WorkerServer;

$ws = new WorkerServer();
$ws->registerModules([
  CrmModule::class
]);

$ws->start();