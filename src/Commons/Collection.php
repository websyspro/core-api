<?php

namespace Websyspro\Server\Commons;

use Closure;
use function count;
use function is_array;
use function array_slice;

class Collection
{
  public function __construct(
    public array $items = []
  ){}

  public function add(
    mixed $mixedOrKey,
    mixed $mixed = null
  ): void {
    if( is_array( $mixedOrKey ) && $mixed === null ){
      foreach( $mixedOrKey as $mixedOrAliasItem ){
        $this->items[] = $mixedOrAliasItem;
      }
    } else {
      if( $mixed !== null ){
        $this->items[ $mixedOrKey ] = isset( $this->items[ $mixedOrKey ])
          ? [ $this->items[ $mixedOrKey ], $mixed ] : $mixed;
      } else {
        $this->items[] = $mixedOrKey;
      }
    }
  }

  public function toArray(
  ): array {
    return $this->items;
  }  

  public function count(
  ): int {
    return count( value: $this->items );
  }  

  public function empty(
  ): bool {
    return $this->count() === 0;
  }

  public function mapper(
    Closure $fn
  ): Collection {
    return new Collection(
      items: array_map(
        callback: $fn,
        array: $this->items
      )
    );
  }

  public function where(
    Closure $fn
  ): Collection {
    $arrayFilter = array_filter(
      array: $this->items, 
      callback: $fn, 
      mode: ARRAY_FILTER_USE_BOTH
    );

    return new Collection(
      items: array_values(
        $arrayFilter
      )
    );
  }

  public function first(
  ): mixed {
    if( $this->empty() ){
      return null;
    }

    [ $firstValue ] = $this->toArray();
    return $firstValue;
  }  

  public function find(
    Closure $fn
  ): mixed {
    return $this->where(fn: $fn)->first();
  }

  public function findByKey(
    mixed $key
  ): mixed {
    if( isset( $this->items[ $key ])){
      return $this->items[ $key ];
    }

    return null;
  }  

  public function slice(
    int $start,
    int|null $length = null
  ): Collection {
    return new Collection(
      items: array_slice(
        array: $this->items, 
        offset: $start, 
        length: $length
      )
    );
  }

  public function indexOf(
    mixed $needle
  ): int {
    return array_search(
      needle: $needle, haystack: $this->items
    );
  }   

  public function reduce(
    mixed $initial,
    callable $callback
  ): Collection {
    return new Collection(
      items: array_reduce(
        array: $this->items, 
        callback: $callback, 
        initial: $initial
      )
    );
  }
  
  public function chunk(
    int $length
  ): Collection {
    return new Collection(
      items: array_chunk(
        array: $this->items,
        length: $length
      )
    );
  }

  public function join(
    string $separator = ""
  ): string {
    return implode(
      separator: $separator,  
      array: $this->items
    );
  }

  public function joinWithComma(
  ): string {
    return implode(
      separator: ", ",
      array: $this->items
    );
  }
  
  public function joinWithSpace(
  ): string {
    return implode(
      separator: " ",
      array: $this->items
    );
  }  

  public static function create(
    array $items = []
  ): Collection {
    return new static(
      items: $items
    );
  }
}