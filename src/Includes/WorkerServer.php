<?php

namespace Websyspro\Server\Includes;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Websyspro\Server\Includes\Enums\RequestMethod;
use Websyspro\Server\Includes\HandleRequest;
use Websyspro\Server\Includes\Request;
use Websyspro\Server\Includes\Response;
use Websyspro\Server\Includes\Container;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Module;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Decorators\Server\Post;
use Websyspro\Server\Includes\Decorators\Server\Put;
use Websyspro\Server\Includes\Decorators\Server\Patch;
use Websyspro\Server\Includes\Decorators\Server\Delete;
use function strtoupper;
use function explode;
use function preg_match;
use function preg_replace;
use function array_keys;
use function array_combine;
use function array_slice;
use function sprintf;

class WorkerServer 
extends AbstractWorkerServer
{
  private array  $routers = [];
  private string $prefix;

  public function __construct(
  ){
    parent::__construct();
    $this->definePrefix();
  }

  private function definePrefix(
  ): void {
    $this->prefix = sprintf(
      "/v%s", App->version
    );
  }

  private function registerRouter(
    string $method, 
    string $path,
    Closure $handler,
    bool $requiresAuth = false
  ): WorkerServer {
    $this->routers[
      sprintf( "%s %s%s", 
        strtoupper( $method ), 
          $this->prefix, $path
      )
    ] = [ 
      "handler" => $handler,
      "requiresAuth" => $requiresAuth
    ];

    return $this;
  }

  private function matchRoute(
    string $method,
    string $requestPath,
    array $paramNames = []
  ): HandleRequest {
    foreach ($this->routers as $key => $route) {
      [ $routeMethod, $routePath ] = explode(
        " ", $key, 2
      );

      if ($routeMethod !== $method) {
        continue;
      }

      preg_match_all( "#:([a-zA-Z_]+)#", $routePath, $matches );
      $paramNames = $matches[1];

      $pattern = preg_replace( "#:([a-zA-Z_]+)#", '([^/]+)', $routePath);
      $pattern = "#^{$pattern}$#";

      if ((bool)preg_match( $pattern, $requestPath, $values ) === false ) {
        continue;
      }

      [ "handler" => $handler, "requiresAuth" => $requiresAuth
      ] = $this->routers[$key];

      return new HandleRequest(
        $handler, array_combine(
          $paramNames, array_slice( $values, 1)
        ) ?: [], $requiresAuth
      );
    }
    
    return new HandleRequest();
  }

  public function get(
    string $path,
    Closure $handler
  ): WorkerServer {
    return $this->registerRouter(
      RequestMethod::GET->value,
      $path, $handler
    );
  }

  public function post(
    string $path,
    Closure $handler
  ): WorkerServer {
    return $this->registerRouter(
      RequestMethod::POST->value, 
      $path, $handler
    );
  }

  public function put(
    string $path, Closure $handler
  ): WorkerServer {
    return $this->registerRouter(
      RequestMethod::PUT->value,
      $path, $handler
    );
  }

  public function patch(
    string $path,
    Closure $handler
  ): WorkerServer {
    return $this->registerRouter(
      RequestMethod::PATCH->value,
      $path, $handler
    );
  }

  public function delete(
    string $path,
    Closure $handler
  ): WorkerServer {
    return $this->registerRouter(
      RequestMethod::DELETE->value,
      $path, $handler
    );
  }

  public function registerModules(
    array $modules
  ): WorkerServer {
    foreach( $modules as $moduleClass ) {
      $reflection = new ReflectionClass($moduleClass);
      $moduleAttr = $reflection->getAttributes( 
        Module::class
      )[0] ?? null;

      if ($moduleAttr === null) {
        continue;
      }

      $module = $moduleAttr->newInstance();
      $modulePrefix = $module->name !== "" 
        ? "/" . trim($module->name, "/")
        : "";

      // Passagem 1 — cria/sincroniza tabelas sem FKs
      //$schema = SchemaManager::create();
      //foreach ($module->entities as $entityClass) {
          // $schema->syncTable($entityClass);
      //}

      // Passagem 2 — aplica FKs após todas as tabelas existirem
      //foreach ($module->entities as $entityClass) {
          // $schema->applyForeignKeys($entityClass);
      //}

      $this->registerControllers(
        $modulePrefix,
        $module->controllers
      );
    }

    return $this;
  }

  public function registerControllers(
    string $modulePrefix = '',
    array $controllers = []
  ): WorkerServer {
    foreach ($controllers as $controllerClass) {
      $this->register(
        $controllerClass, 
        $modulePrefix
      );
    }

    return $this;
  }

  public function register(
    string $controllerClass, 
    string $modulePrefix = ""
  ): WorkerServer {
    $reflection = new ReflectionClass($controllerClass);
    $controllerAttr = $reflection->getAttributes(
      Controller::class
    )[0] ?? null;

    if ($controllerAttr === null) {
      return $this;
    }

    $controllerPath = "/" . trim( $controllerAttr->newInstance()->prefix, "/");
    $basePath = "{$modulePrefix}{$controllerPath}";
    $instance = Container::make($controllerClass);
    $httpAttrs = [ Get::class, Post::class, Put::class, Patch::class, Delete::class ];

    $controllerHasAuth = !empty(
      $reflection->getAttributes(
        Authenticate::class
      )
    );

    Logger::info("Controller -> {$reflection->getShortName()}");
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
      foreach ($httpAttrs as $attrClass) {
        $attrs = $method->getAttributes($attrClass);
        if (empty($attrs)) {
            continue;
        }

        $subPath = $attrs[0]->newInstance()->path;
        $httpMethod = strtoupper((new ReflectionClass($attrClass))->getShortName());
        $fullPath = $basePath . ($subPath === "/" ? "" : $subPath);
        $handler = Closure::fromCallable([$instance, $method->getName()]);

        $methodHasAllowAnonymous = !empty( $method->getAttributes( AllowAnonymous::class ) );
        $methodHasAuth = !empty( $method->getAttributes( Authenticate::class ) );

        $requiresAuth = !$methodHasAllowAnonymous && (
          $methodHasAuth || $controllerHasAuth
        );

        $this->registerRouter(
          $httpMethod,
          $fullPath,
          $handler,
          $requiresAuth
        );
      }
    }

    return $this;
  }

  public function getRoutes(
  ): array {
    return array_keys(
      $this->routers
    );
  }

  protected function handleRequest(
    Request $request
  ): Response {
    $handleMatchResult = $this->matchRoute(
      $request->method, $request->path
    );

    if ($handleMatchResult->isNotExists()) {
      return Response::notFound( 
        "Route {$request->method} {$request->path} not found"
      );
    }

    return $handleMatchResult->execute( $request );
  }
}
