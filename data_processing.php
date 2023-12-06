<?php

require_once("./libconf/app_conf.php");
require_once(ALK_ALKANET_ROOT_PATH . "lib/app_conf_alkanet.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkobject.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkrequest.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkfactory.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "scripts/alkpositionloader_ast.class.php");

function main($argc, $argv) {
  $cleanDb = false;
  
  $logFilename = "./trackingMonitor.log";


  // utilisation paramètres en ligne de commande
  if (strcmp($argv[$argc - 1], 'clean') == 0) {
    $cleanDb = true;
  }
  if ($argc >= 3) {

    $datFilePath = realpath($argv[1]);
    $dataDir = realpath($argv[2]);

    $filePrefix = basename($datFilePath, '.dat');
    if ($argc >= 4) {
      $logFilename = $argv[3];
    }
  }

  $dbConn = AlkFactory::getDbConn();

  $fileLogger = new AlkFileLogger($logFilename);
  $outDir = './out';
  $loader = new AlkPositionLoaderAst($dbConn, $dataDir, $outDir, $fileLogger);

  //clean db for tests
  //$loader->cleanDb();

  if ($cleanDb) {
    echo "Cleaning Db..\n";
    $loader->cleanDb();
    exit(0);
  }

  $loader->handle($datFilePath, $filePrefix);
}

// calling main
main($argc, $argv);
exit(0);
