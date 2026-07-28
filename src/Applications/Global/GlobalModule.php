<?php

namespace Websyspro\Server\Applications\Global;

use Websyspro\Server\Applications\Global\Controllers\PostsController;
use Websyspro\Server\Includes\Decorators\Server\Module;

#[Module(
  name: "global",
  controllers: [
    PostsController::class
  ],
  entities: []
)]
class GlobalModule {}
