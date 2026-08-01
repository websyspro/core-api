<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Includes\Enums\StateView;

class QueryProps
{
  public function __construct(
    public readonly string $view = "",
    public readonly StateView $state = StateView::Read,
    public readonly int $page = 1,
    public readonly int $pageRows = 14,
    public readonly object|null $order = null,
    public readonly object|null $where = null,
    public readonly array|null $rows = [],
  ){}
}
