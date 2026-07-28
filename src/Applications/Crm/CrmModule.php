<?php

namespace Websyspro\Server\Applications\Crm;

use Websyspro\Server\Applications\Crm\Controllers\CargosController;
use Websyspro\Server\Includes\Decorators\Server\Module;

#[Module(
  name: "crm",
  controllers: [
    CargosController::class
  ],
  entities: []
)]
class CrmModule {}
