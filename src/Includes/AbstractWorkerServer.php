<?php

namespace Websyspro\Server\Includes;

use Websyspro\Server\Includes\Interfaces\AppStructure;
use function defined;
use function in_array;
use function array_search;
use function array_merge;
use function array_column;
use function stream_select;
use function stream_socket_accept;
use function stream_socket_get_name;
use function stream_set_blocking;
use function stream_socket_server;
use function fread;
use function fwrite;
use function fclose;
use function strpos;
use function substr;
use function strlen;
use function explode;
use function strtolower;
use function trim;
use function time;
use function getmypid;
use function pcntl_fork;
use function pcntl_wait;
use function sleep;
use const WNOHANG;
use const PHP_OS_FAMILY;

// Define WNOHANG para Windows onde pcntl_* não existe
abstract class AbstractWorkerServer
{
  private string $host;
  private int    $port;
  private int    $workers;
  private int    $keepAliveTimeout;
  private int    $maxRequests;
  private mixed  $server = null;
  private array  $pids   = [];

  public function __construct(
  ){
    if( defined( "APP" )){
      if( APP instanceof AppStructure ){
        $this->host = APP->host;
        $this->port = APP->port;
        $this->keepAliveTimeout = APP->keepAliveTimeOut;
        $this->maxRequests = APP->maxRequests;
        $this->workers = APP->workers;
      }
    }
  }

  private function parseRequest(string $buffer): ?array
  {
      $headerEnd = strpos($buffer, "\r\n\r\n");
      if ($headerEnd === false) {
          return null;
      }

      $headerSection = substr($buffer, 0, $headerEnd);
      $body          = substr($buffer, $headerEnd + 4);
      $lines         = explode("\r\n", $headerSection);
      $firstLine     = array_shift($lines);
      $parsedHeaders = [];

      foreach ($lines as $line) {
          if (strpos($line, ':') !== false) {
              [$name, $value]                         = explode(':', $line, 2);
              $parsedHeaders[strtolower(trim($name))] = trim($value);
          }
      }

      $contentLength = (int) ($parsedHeaders['content-length'] ?? 0);
      if (strlen($body) < $contentLength) {
          return null; // body incompleto, aguarda mais dados
      }

      return [
          'firstLine' => $firstLine,
          'headers'   => $parsedHeaders,
          'body'      => substr($body, 0, $contentLength),
          'remaining' => substr($body, $contentLength),
      ];
  }

  /**
   * Subclasses implementam este metodo para processar a requisicao
   * e retornar o body da resposta.
   */
  abstract protected function handleRequest(Request $request): Response;
  abstract public function getRoutes(): array;

  /**
   * Event loop do worker — roda em loop infinito processando conexoes e dados.
   * Cada worker filho executa este metodo de forma independente.
   */
  private function runWorker(int $workerId): void
  {
      // $clients mapeia (int)$socket => ['socket', 'buffer', 'lastActivity']
      $clients      = [];
      $requestCount = 0;

      Logger::info("Worker $workerId iniciado (PID: " . getmypid() . ")");

      while (true) {
          $sockets = array_column($clients, 'socket');
          $read    = array_merge([$this->server], $sockets);
          $write   = null;
          $except  = null;

          // stream_select aguarda atividade em qualquer socket (timeout 1s)
          // o timeout de 1s permite verificar conexoes idle periodicamente
          if (stream_select($read, $write, $except, 1, 0) === false) {
              break;
          }

          // Nova conexao chegando no socket servidor
          if (in_array($this->server, $read)) {
              $client = @stream_socket_accept($this->server, 0);
              if ($client) {
                  stream_set_blocking($client, false);
                  $clients[(int) $client] = [
                      'socket'       => $client,
                      'buffer'       => '',
                      'lastActivity' => time(),
                  ];
                  $addr = stream_socket_get_name($client, true);
                  Logger::info("[Worker $workerId] Conexao de $addr (total: " . count($clients) . ")");
              }
              unset($read[array_search($this->server, $read)]);
          }

          // Processa clientes com dados prontos para leitura
          foreach ($read as $socket) {
              $id = (int) $socket;
              if (!isset($clients[$id])) {
                  continue;
              }

              $chunk = fread($socket, 8192);

              if ($chunk === false || $chunk === '') {
                  fclose($socket);
                  unset($clients[$id]);
                  Logger::info("[Worker $workerId] Desconectou (restam: " . count($clients) . ")");
                  continue;
              }

              $clients[$id]['buffer']      .= $chunk;
              $clients[$id]['lastActivity'] = time();

              // Processa todas as requisicoes completas no buffer (HTTP pipelining)
              while (true) {
                  $request = $this->parseRequest($clients[$id]['buffer']);
                  if ($request === null) {
                      break;
                  }
                  
                  // Popula $_SERVER para compatibilidade com Apache/Nginx
                  [$method, $uri] = explode(' ', $request['firstLine'], 3);
                  
                  // ⚠️ REMOVIDO: $_SERVER é compartilhado entre requests no mesmo worker
                  // Usar $request->method e $request->path ao invés de $_SERVER
                  // $_SERVER['REQUEST_METHOD'] = $method;
                  // $_SERVER['REQUEST_URI'] = $uri;
                  
                  $connection = $request['headers']['connection'] ?? 'keep-alive';
                  $keepAlive  = strtolower($connection) !== 'close';
                  $response   = $this->handleRequest(new Request($request));

                  fwrite($socket, $response->build($keepAlive));

                  Logger::info("[Worker $workerId] {$request['firstLine']} (keep-alive: " . ($keepAlive ? 'sim' : 'nao') . ")");

                  $clients[$id]['buffer'] = $request['remaining'];

                  // Incrementa o contador e encerra o worker ao atingir o limite
                  // O master detecta a saida e sobe um novo worker limpo
                  $requestCount++;
                  if ($requestCount >= $this->maxRequests) {
                      Logger::warn("[Worker $workerId] Limite de {$this->maxRequests} requisicoes atingido, encerrando...");
                      foreach ($clients as $info) {
                          fclose($info['socket']);
                      }
                      exit(0);
                  }

                  if (!$keepAlive) {
                      fclose($socket);
                      unset($clients[$id]);
                      break;
                  }
              }
          }

          // Fecha conexoes ociosas que ultrapassaram o keep-alive timeout
          $now = time();
          foreach ($clients as $id => $info) {
              if (($now - $info['lastActivity']) >= $this->keepAliveTimeout) {
                  fclose($info['socket']);
                  unset($clients[$id]);
                  Logger::warn("[Worker $workerId] Conexao idle fechada por timeout");
              }
          }
      }
  }

  /**
   * Inicia o servidor: cria o socket, faz fork dos workers e
   * mantém o master monitorando e reiniciando workers mortos.
   */
  public function start(): void
  {
      $this->server = stream_socket_server(
          "tcp://{$this->host}:{$this->port}",
          $errno,
          $errstr
      );

      if (!$this->server) {
          die("Erro ao criar servidor: $errstr ($errno)\n");
      }

      stream_set_blocking($this->server, false);

      foreach ($this->getRoutes() as $route) {
          [$method, $path] = explode(' ', $route, 2);
          Logger::info("$method $path");
      }

      if (PHP_OS_FAMILY === 'Windows') {
          $this->startSingleProcess();
      } else {
          $this->startMultiProcess();
      }
  }

  /**
   * Modo single-process para Windows.
   * Sem fork — roda o event loop direto no processo atual.
   * Ideal para desenvolvimento local sem Docker.
   */
  private function startSingleProcess(): void
  {
      Logger::info("Master PID: " . getmypid());
      Logger::info("Porta: {$this->port}");
      Logger::info("Modo: single-process (Windows)");
      Logger::info("Keep-Alive: {$this->keepAliveTimeout}s");
      Logger::info("Server running on http://{$this->host}:{$this->port}");

      $this->runWorker(1);
  }

  private function startMultiProcess(
  ): void {
    if( function_exists( "pcntl_fork" )){
      if( function_exists( "pcntl_wait" )){
        if (defined( "WNOHANG" ) === false ) {
            define( "WNOHANG", 1 );
        }

        Logger::info( "Master PID: " . getmypid());
        Logger::info( "Porta: {$this->port}" );
        Logger::info( "Workers: {$this->workers}" );
        Logger::info( "Keep-Alive: {$this->keepAliveTimeout}s" );
        Logger::info( "Max Requests: {$this->maxRequests}" );
        Logger::info( "Server running on http://{$this->host}:{$this->port}" );

        for ($i = 0; $i < $this->workers; $i++) {
          $pid = pcntl_fork();
          if ($pid === -1) {
            die("Falha ao criar worker $i\n");
          }
          if ($pid === 0) {
            $this->runWorker($i + 1);
            exit(0);
          }

          $this->pids[] = $pid;
        }

        while (true) {
          $status = 0;
          $pid = pcntl_wait(
            $status, WNOHANG
          );

          if ($pid > 0) {
            $idx = array_search( $pid, $this->pids );
            Logger::warn( "Worker PID $pid morreu, reiniciando..." );
            $newPid = pcntl_fork();
            if ($newPid === 0) {
              $this->runWorker($idx + 1);
              exit(0);
            }
            $this->pids[$idx] = $newPid;
          }

          sleep(1);
        }
      }
    }
  }
}
