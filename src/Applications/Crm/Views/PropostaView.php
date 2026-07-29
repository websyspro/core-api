<?php

namespace Websyspro\Server\Applications\Crm\Views;

use Websyspro\Server\Includes\Decorators\Database\OriginSchema;
use Websyspro\Server\Includes\Enums\Schema;
use Websyspro\Server\Includes\QueryView;

#[OriginSchema( Schema::Crm )]
class PropostaView 
extends QueryView
{
  public function sql(
  ): string {
    return (
      "SELECT Proposta.* 
         FROM Proposta
             ,ItemProposta
    LEFT JOIN Obra on Obra.Id = ItemProposta.ObraId 
        WHERE ItemProposta.PropostaId  = Proposta.Id"
    );
  }
}