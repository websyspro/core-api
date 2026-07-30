<?php

namespace Websyspro\Server\Includes;

use Closure;
use Exception;
use ReflectionFunction;
use ReflectionNamedType;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\File;
use Websyspro\Server\Includes\Decorators\Server\Param;
use Websyspro\Server\Includes\Decorators\Server\Query;
use Websyspro\Server\Includes\Request;
use function call_user_func;
use function is_array;
use function is_object;

class HandleRequest
{
  public function __construct(
    public readonly Closure|null $closure = null,
    public readonly array|null $params = null
  ){}

  public function isNotExists(
  ): bool {
    return $this->closure === null;
  }

  private function resolveArgs(
    Closure $handler,
    Request $request,
    array $args = [],
  ): array {
    $reflection = new ReflectionFunction($handler);

    foreach( $reflection->getParameters() as $param ){
      $type = $param->getType();

      if( $type instanceof ReflectionNamedType && $type->getName() === Request::class) {
        $args[] = $request;
        continue;
      }

      $sourceMap = [
        Body::class  => $request->body,
        Query::class => $request->query,
        Param::class => $request->params,
        File::class => $request->files,
      ];

      $resolved = false;
      foreach ($sourceMap as $attrClass => $source) {
        $attrs = $param->getAttributes($attrClass);
        if( empty( $attrs )){
          continue;
        }

        $key = $attrs[0]->newInstance()->key;
        $modelClass = $type instanceof ReflectionNamedType ? $type->getName() : null;

        if( $key !== "" ){
          $value = is_array($source) 
            ? ($source[$key] ?? null) : ( is_object( $source ) 
            ? ($source->$key ?? null) : null);
          $args[] = $value;
        } elseif ($modelClass && is_subclass_of($modelClass, Model::class)) {
          $args[] = $modelClass::from($source);
        } else {
          $args[] = $source;
        }

        $resolved = true;
        break;
      }

      if( $resolved === false ){
        $args[] = null;
      }
    }

    return $args;
  }  

  public function execute(
    Request $request
  ): Response {
    if( $this->closure instanceof Closure ){
      try {
        $executeValue = @call_user_func( 
          $this->closure, $this->resolveArgs(
            $this->closure, $request->setParams( $this->params )
          )
        );

        if( $executeValue instanceof Response ){
          return $executeValue;
        }

        return Response::json( $executeValue );
      } catch( Exception $error ){
        return Response::badRequest( $error->getMessage() );
      }
    }

    return Response::noContent();
  }
}