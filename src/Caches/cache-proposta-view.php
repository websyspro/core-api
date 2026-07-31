<?php

return [
  'hash'   => '8da433d41d6eb57b973d9f75d1bd093c',
  'table'  => 'Proposta',
  'key'    => 'Id',
  'fields' => [
    [ 'name' => 'Id', 'type' => 'text' ],
    [ 'name' => 'IsActive', 'type' => 'bit' ],
    [ 'name' => 'IsDeleted', 'type' => 'bit' ],
    [ 'name' => 'Created', 'type' => 'datetime' ],
    [ 'name' => 'CreatedById', 'type' => 'text' ],
    [ 'name' => 'Updated', 'type' => 'datetime' ],
    [ 'name' => 'UpdatedById', 'type' => 'text' ],
    [ 'name' => 'Status', 'type' => 'integer' ],
    [ 'name' => 'ContatoId', 'type' => 'text' ],
    [ 'name' => 'InstituicaoId', 'type' => 'text' ],
    [ 'name' => 'DistribuidorId', 'type' => 'text' ],
    [ 'name' => 'ConsultorVendasEspeciaisId', 'type' => 'text' ],
    [ 'name' => 'Observacao', 'type' => 'text' ],
    [ 'name' => 'PrazoFaturamento', 'type' => 'text' ],
    [ 'name' => 'NomeContato', 'type' => 'text' ],
    [ 'name' => 'NomeProposta', 'type' => 'text' ],
    [ 'name' => 'DescontoFinalCliente', 'type' => 'decimal' ],
    [ 'name' => 'Arquivada', 'type' => 'bit' ],
    [ 'name' => 'ComentarioArquivamento', 'type' => 'text' ],
    [ 'name' => 'Versao', 'type' => 'integer' ],
    [ 'name' => 'IdPipelineZoho', 'type' => 'text' ],
    [ 'name' => 'Frete', 'type' => 'bit' ]
  ],
];