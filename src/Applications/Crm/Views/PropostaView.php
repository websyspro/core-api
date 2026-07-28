<?php

namespace Websyspro\Server\Applications\Crm\Views;

use Websyspro\Server\Includes\QueryView;

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