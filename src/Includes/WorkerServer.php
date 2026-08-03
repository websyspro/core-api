<?php

namespace Websyspro\Server\Includes;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Websyspro\Server\Includes\Enums\RequestMethod;
use Websyspro\Server\Includes\Enums\ServiceType;
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
      "/v%s", APP->version
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

  public function start(
  ): void {
    APP->serviceType === ServiceType::Apache
      ? $this->startApache()
      : $this->startTcp();
  }

  private function startApache(
  ): void {
    $method   = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri      = $_SERVER['REQUEST_URI'] ?? '/';
    $headers  = $this->apacheGetHeaders();
    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

    $contentType = strtolower($headers['content-type'] ?? '');

    if (str_contains($contentType, 'multipart/form-data')) {
      $rawBody = '';
    } else {
      $rawBody = file_get_contents('php://input') ?: '';
    }

    $firstLine = sprintf('%s %s %s', $method, $uri, $protocol);

    $parsed = [
      'firstLine' => $firstLine,
      'headers'   => $headers,
      'body'      => $rawBody,
      'remaining' => '',
    ];

    // Injeta $_POST e $_FILES no $parsed para o construtor do Request
    if (str_contains($contentType, 'multipart/form-data')) {
      $parsed['apachePost']  = $_POST;
      $parsed['apacheFiles'] = $_FILES;
    }

    $request  = new Request($parsed);
    $response = $this->handleRequest($request);

    http_response_code($response->status);

    foreach ($response->headers as $key => $value) {
      header(sprintf('%s: %s', $key, $value));
    }

    echo $response->body;
  }

  private function apacheGetHeaders(
  ): array {
    $headers = [];

    if (function_exists('getallheaders')) {
      foreach (getallheaders() as $name => $value) {
        $headers[strtolower($name)] = $value;
      }
      return $headers;
    }

    // Fallback via $_SERVER para CGI/outros SAPIs
    foreach ($_SERVER as $key => $value) {
      if (str_starts_with($key, 'HTTP_')) {
        $name = strtolower(str_replace('_', '-', substr($key, 5)));
        $headers[$name] = $value;
      }
    }

    if (isset($_SERVER['CONTENT_TYPE'])) {
      $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
    }

    if (isset($_SERVER['CONTENT_LENGTH'])) {
      $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
    }

    return $headers;
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
