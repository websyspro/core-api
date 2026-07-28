<?php

namespace Websyspro\Server\Applications\Global\Views;

use Websyspro\Server\Includes\Enums\DriverSchema;
use Websyspro\Server\Includes\QueryView;

class PostsView
extends QueryView
{
  protected DriverSchema $schema = DriverSchema::Global;

  public function sql(
  ): string {
    return (
      "SELECT wp_posts.* 
         FROM wp_posts
    LEFT JOIN wp_postmeta on post_id = wp_posts.ID 
        WHERE wp_posts.post_type  = 'Obra'"
    );
  }
}