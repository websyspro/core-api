<?php

namespace Websyspro\Server\Applications\Global\Views;

use Websyspro\Server\Includes\Decorators\Database\OriginSchema;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Enums\Schema;
use Websyspro\Server\Includes\QueryView;

#[Authenticate()]
#[OriginSchema( Schema::Global )]
class PostsView
extends QueryView
{
  public function sql(
  ): string {
    return (
      "SELECT wp_posts.* 
         FROM wp_posts
    LEFT JOIN wp_postmeta on post_id = wp_posts.ID 
        WHERE wp_posts.post_type = 'Obra'
          AND wp_posts.post_status = 'publish'"
    );
  }
}