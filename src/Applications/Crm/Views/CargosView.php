<?php

namespace Websyspro\Server\Applications\Crm\Views;

use Websyspro\Server\Includes\QueryView;

class CargosView 
extends QueryView
{
  public function sql(
  ): string {
    return (
      "SELECT * FROM Cargo"
    );
  }
}