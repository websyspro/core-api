<?php

namespace Websyspro\Server\Includes\Decorators\Server;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Put
{
    public function __construct(
        public readonly string $path = '/'
    ) {}
}
