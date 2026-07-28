<?php

defined( "ROOT" ) || define(
  "ROOT", __DIR__ . DIRECTORY_SEPARATOR
);

require_once ROOT . "vendor/autoload.php";

if( file_exists( ROOT . "envs.php" )){
  require_once ROOT . "envs.php";
  require_once ROOT . "src/main.php";
}