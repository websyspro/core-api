<?php

/**
 * Watch — Hot Reload para desenvolvimento
 * 
 * Uso: php watch.php
 * 
 * - Inicia o servidor (php index.php) como processo filho
 * - Varre src/ a cada segundo monitorando timestamps
 * - Quando detecta criação, modificação ou deleção de arquivo → reinicia o servidor
 */

define( "WATCH_DIR",      __DIR__ . DIRECTORY_SEPARATOR . "src" );
define( "SERVER_SCRIPT",  __DIR__ . DIRECTORY_SEPARATOR . "index.php" );
define( "SCAN_INTERVAL",  1 ); // segundos entre cada varredura

// ─── Cores para o terminal ───────────────────────────────────────────────────
function watchLog( string $level, string $message ): void
{
  $colors = [
    'INFO'    => "\033[32m",
    'WARN'    => "\033[33m",
    'RESTART' => "\033[35m",
  ];

  $color = $colors[ $level ] ?? "\033[0m";
  $reset = "\033[0m";
  $time  = date( 'Y-m-d H:i:s' );

  echo "{$color}[{$time}] {$level}{$reset} {$message}\n";
}

function clearTerminal(): void
{
  // Escape ANSI: move cursor para topo e limpa tela
  echo "\033[2J\033[H";
}

function printHeader(): void
{
  $reset  = "\033[0m";
  $purple = "\033[35m";
  $green  = "\033[32m";
  $time   = date( 'Y-m-d H:i:s' );

  echo "{$purple}PHP Watch — Hot Reload{$reset}\n";
  echo "{$green}Executando php index.php{$reset}\n";
  echo "{$green}Monitorando src/ — {$time}{$reset}\n\n";
}

// ─── Varre recursivamente src/ e retorna [ filepath => mtime ] ───────────────
function scanFiles( string $dir ): array
{
  // Limpa o cache interno do PHP para stat de arquivos
  clearstatcache();

  $result   = [];
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
  );

  foreach( $iterator as $file ){
    if( $file->isFile() ){
      // Ignora a pasta Caches — arquivos de cache não devem disparar reload
      if( str_contains( $file->getPathname(), DIRECTORY_SEPARATOR . "Caches" . DIRECTORY_SEPARATOR ) ){
        continue;
      }

      $result[ $file->getPathname() ] = $file->getMTime();
    }
  }

  return $result;
}

// ─── Inicia o servidor como processo filho ───────────────────────────────────
function startServer(): mixed
{
  clearTerminal();
  printHeader();

  $cmd = PHP_BINARY . " " . SERVER_SCRIPT;

  $descriptors = [
    0 => [ "pipe", "r"  ],
    1 => STDOUT,
    2 => STDERR,
  ];

  $process = proc_open( $cmd, $descriptors, $pipes );

  if( !is_resource( $process ) ){
    watchLog( "WARN", "Falha ao iniciar o servidor" );
    return null;
  }

  fclose( $pipes[0] );

  return $process;
}

// ─── Mata o processo do servidor ─────────────────────────────────────────────
function stopServer( mixed $process ): void
{
  if( !is_resource( $process ) ){
    return;
  }

  $status = proc_get_status( $process );

  if( $status['running'] ){
    // Windows
    if( PHP_OS_FAMILY === "Windows" ){
      $pid = $status['pid'];
      exec( "taskkill /F /T /PID {$pid} 2>NUL" );
    } else {
      $pid = $status['pid'];
      exec( "kill -TERM {$pid} 2>/dev/null" );
    }
  }

  proc_close( $process );
  watchLog( "WARN", "Servidor encerrado" );
}

// ─── Loop principal ───────────────────────────────────────────────────────────
$process  = startServer();
$snapshot = scanFiles( WATCH_DIR );

while( true ){
  sleep( SCAN_INTERVAL );

  $current = scanFiles( WATCH_DIR );

  // Detecta modificações e criações
  foreach( $current as $file => $mtime ){
    if( !isset( $snapshot[$file] ) ){
      watchLog( "RESTART", "Arquivo criado: " . basename( $file ) );
      goto restart;
    }

    if( $snapshot[$file] !== $mtime ){
      watchLog( "RESTART", "Arquivo modificado: " . basename( $file ) );
      goto restart;
    }
  }

  // Detecta deleções
  foreach( $snapshot as $file => $mtime ){
    if( !isset( $current[$file] ) ){
      watchLog( "RESTART", "Arquivo deletado: " . basename( $file ) );
      goto restart;
    }
  }

  continue;

  restart:
  stopServer( $process );
  sleep( 1 );
  $process  = startServer();
  $snapshot = $current;
}