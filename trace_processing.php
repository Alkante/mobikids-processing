<?php

require_once(dirname(__FILE__) . "/libconf/app_conf.php");
require_once(ALK_ALKANET_ROOT_PATH . "lib/app_conf_alkanet.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkobject.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkrequest.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "classes/pattern/alkfactory.class.php");
require_once(ALK_ALKANET_ROOT_PATH . "scripts/alkpositionloader_ast.class.php");
require_once(dirname(__FILE__) . "/libconf/parameters.php");


/*define("AST_STATE_ENQ_CREATED", 0);
define("AST_STATE_ENQ_DATADIR_CREATED", 1);
define("AST_STATE_ENQ_PROCESSING_DAT", 2);
define("AST_STATE_ENQ_PROCESSING_MOBILITY", 3);
define("AST_STATE_ENQ_PROCESSING_TRACK", 4);
define("AST_STATE_ENQ_ROADMAP_CREATED", 5);*/

define("AST_STATE_ENQ_CREATED", 0);
define("AST_STATE_ENQ_DATADIR_CREATED", 1);
define("AST_STATE_ENQ_PROCESSING_DAT", 2);
define("AST_STATE_ENQ_PROCESSING_TRACK", 3);
define("AST_STATE_ENQ_ROADMAP_CREATED", 4);


/**
 * fonction de creation du répertoire de traitement
 * du fichier .dat astrollendro
 * @param type $datFilePath
 * @param type $dataDir
 * @return int code de sortie des commandes systemes
 */
function prepareDir($datFilePath, $dataDir) {
  $res = null;
  // creation du repertoire
  $res = mkdir($dataDir);
  
  // copie du fichier .dat
  $newFilePath = $dataDir . '/' . basename($datFilePath);
  $res = $res && rename($datFilePath, $newFilePath);
  
  return $res;
}


function listFiles($dir, $ext) {
  $res = array();
  $datFiles = scandir($dir);
  // filtrage sur extension '.dat'
  $datFiles = array_values(array_filter($datFiles, function($e) use ($ext) { return strpos($e,$ext)>=0 && strpos($e,$ext)===(strlen($e)-strlen($ext)); }));
  $res = $datFiles;
  return $res;
} 


function mergeCsvFiles($dir, $ext, $resultPrefix) {
  $res = true;
  echo "Fusion des fichiers $ext vers le fichier " . $resultPrefix . $ext . "\n";
  $csvFiles = listFiles($dir, $ext);
  
  // test du bon nommage des fichiers
  for($i=0;$i<count($csvFiles);$i++) {
    if (!(preg_match('/^[^_]+_[0-9]{6}_[0-9]{6}.*/',$csvFiles[$i])===1)) {
      echo "Problème dans le nommage du fichier " . $csvFiles[$i] . " : impossible de déduire l'horodatage\n";
      echo "Fusion impossible\n";
      $res = false;
    }
  }
  if (!$res)  
    return $res;
  
  // tri des fichiers par rapport à l'hordatage tiré du nom
  //echo print_r($csvFiles, true) . "\n";
  usort($csvFiles,
    function($a, $b) {
      $time_a = $a;
      $tab_a = array();
      if (preg_match('/^[^_]+_([0-9]{2})([0-9]{2})([0-9]{2})_([0-9]{6}).*/', $a, $tab_a)===1) {
        $time_a = $tab_a[3] . $tab_a[2] . $tab_a[1] . $tab_a[4];
      }
      $time_b = $b;
      $tab_b = array();
      if (preg_match('/^[^_]+_([0-9]{2})([0-9]{2})([0-9]{2})_([0-9]{6}).*/', $b, $tab_b)===1) {
        $time_b = $tab_b[3] . $tab_b[2] . $tab_b[1] . $tab_b[4];
      }
      return "time_a"<"time_b" ? 1 : -1;
    }
  );
  $csvFiles = array_values($csvFiles);
  //echo print_r($csvFiles, true) . "\n";

  // fusion des différents fichiers
  $resultFilePath = $dir . '/' . $resultPrefix . $ext;
  $strCmdTab = array("cat " .  $dir . '/' . $csvFiles[0] . " > $resultFilePath");
  for($i=1;$i<count($csvFiles);$i++) {
    $strCmd = "tail -n +2 " . $dir . '/' . $csvFiles[$i] . " >> $resultFilePath";
    array_push($strCmdTab, $strCmd);
  }
  
  $strCmd = implode(';', $strCmdTab);
  //echo $strCmd . "\n";
  $output = array();
  $cmdRes = 0;
  $res && exec($strCmd, $output, $cmdRes);
  $res = $res && ($cmdRes==0);
  return $res;
}

/**
 * fonction de creation du sous répertoire de traitement
 * des fichiers .dat mobikids
 * @param type $dataDir
 * @return int code de sortie des commandes systemes
 */
function prepareDirDat($dataDir) {
  $res = null;
  // creation du repertoire
  $res = mkdir($dataDir.'/dat');
  $datFiles = listFiles($dataDir, '.dat');
  //print_r($datFiles);
  for($i=0;$i<count($datFiles);$i++) {
    // copie du fichier .dat
    $filePath = $dataDir . '/' . $datFiles[$i];
    $newFilePath = $dataDir . '/dat/' . $datFiles[$i];
    //echo ($res ? 1 : 0 ) . " Moving $filePath to $newFilePath\n";
    $res = $res && rename($filePath, $newFilePath);
  }
  return $res;
}

/**
 * fonction d'appel du script python de décodage des fichiers 
 * .dat astrollendro
 * @param type $datFilePath
 * @param type $dataDir
 * @return boolean true if exec ok, false else
 */
function decodeDatFiles($dataDir) {
  $res = true;
  $dataDirDat = $dataDir . '/dat';
  $datFiles = listFiles($dataDirDat, '.dat');
  
  // suprression des fichier csv existants...
  $csvFiles = listFiles($dataDirDat, '.csv');
  $res = array_reduce($csvFiles, function($carry,$f) use ($dataDirDat) { return $carry && unlink($dataDirDat.'/'.$f);}, true);
  
  // traitement des fichiers .dat de façon individuelle
  for($i=0;$i<count($datFiles);$i++) {
    $datFilePath = $dataDirDat . '/' . $datFiles[$i];
    $strCmd = "python " . ALK_AST_PROCESSING_PATH . "/python/dataDecoder.py -gab -i $datFilePath -o $dataDirDat"; 
    // lancement de la commande
    $output = array();
    echo "Decodage du fichier " . $datFilePath . "\n";
    $cmdRes = 0;
    $res && exec($strCmd, $output, $cmdRes);
    $res = $res && ($cmdRes==0);
  }
  // fusion des fichiers csv
  $decodeFiles = array();
  $resultPrefix = basename($dataDir);
  if (count($datFiles)>1) {
    $res = $res && mergeCsvFiles($dataDirDat, '_info.csv', $resultPrefix); 
    $res = $res && mergeCsvFiles($dataDirDat, '_gps.csv', $resultPrefix); 
    $res = $res && mergeCsvFiles($dataDirDat, '_battery.csv', $resultPrefix);
    $res = $res && mergeCsvFiles($dataDirDat, '_accelero.csv', $resultPrefix);
    $decodedFiles = array(
      $resultPrefix . '_info.csv',
      $resultPrefix . '_gps.csv',
      $resultPrefix . '_battery.csv',
      $resultPrefix . '_accelero.csv'
    );
    // deplacement des fichiers finaux
    $res = $res && array_reduce($decodedFiles, 
    function($carry,$f) use ($dataDir) { 
      $test = rename($dataDir . '/dat/' . $f, $dataDir . '/' . $f); 
      return $carry && ($test==0);
    },
    true); 
  }
  else {
    $decodedFiles = listFiles($dataDirDat, '.csv');
    // deplacement des fichiers finaux
    $res = $res && array_reduce($decodedFiles, 
    function($carry,$f) use ($dataDir, $resultPrefix) { 
      $parts = explode('_',$f);
      $suffix = $parts[count($parts)-1]; 
      $test = rename($dataDir . '/dat/' . $f, $dataDir . '/' . $resultPrefix . '_' . $suffix); 
      return $carry && ($test==0);
    },
    true);
  }

  

  return $res;
}

/**
 * fonction d'appel du programme de claucl d'activité par segment 
 * pour la détection du mode de transport 
 * @param string $inFilePath chemin complet du fichier de données accélérométrique (csv)
 * @param string $outFilePath chemin complet du fichier de sortie des données d'activité par segment (csv)
 * @return int code de sortie de la commande externe de calcul de l'activité
 */
function computeMobilityInfo($inFilePath, $outFilePath) {
  
  $sampleSize = defined("ALK_AST_MOBILITY_SAMPLE_SIZE") ? ALK_AST_MOBILITY_SAMPLE_SIZE : 500;
  $strCmd = ALK_AST_PROCESSING_PATH . '/libmobility/bin/activity_mobility -s ' . ALK_AST_MOBILITY_SAMPLE_SIZE . " $inFilePath > $outFilePath";
   
  // lancement de la commande
  $res = null;
  $output = array();
  echo "Calcul des informations d'activité à partir du fichier " . $inFilePath . "\n";
  exec($strCmd, $output, $res);
  
  return $res;
}


/**
 * fonction d'appel des traitements des traces GPS
 * @param type $idEnquete
 * @param type $dataDir
 * @param type $datFilePath
 * @param type $filePrefix
 * @return type
 */
function processData($idEnquete, $dataDir, $datFilePath, $filePrefix, $params) {
  $res = false;
  $dbConn = AlkFactory::getDbConn();
  $logFilename = $dataDir . "/" . "trackingMonitor.log";
  
  $fileLogger = new AlkFileLogger($logFilename);
  $outDir = $dataDir;
  $loader = new AlkPositionLoaderAst($dbConn, $idEnquete, $params, $dataDir, $outDir, $fileLogger);
  echo "Traitement des fichiers traces pour l'enquete " . $idEnquete . "\n";
  $res = $loader->handle($datFilePath, $filePrefix);
  
  return $res;
  
}


/**
 * 
 * @param int $idEnquete
 * @param int $state : statut de la mise à jour 
 * @return boolean True en cas de succes de la mise à jour, false sinon
 */
function updateEnqueteState($enqueteId, $state) {
  $res = false;
  
  $dbConn = AlkFactory::getDbConn();
  $strSql = "UPDATE astrollendro.ast_enquete
             SET enq_state=:value
             WHERE enq_id = :enq_id";

  $sth = $dbConn->prepare($strSql);
  $res = $sth->execute(array(
                  ':enq_id' => $enqueteId, 
                  ':value'  => $state
                    )
    );
  return $res;
  
}


/**
 * Extrait le numero de l'enquete à partir du nom de répertoire
 * @param string $dirName
 * @return int le numero de l'enquete
 */
function getNumEnquete($dirName) {
  $res = null;
  $matches = array();
  preg_match("/^.*_([0-9]+)$/", $dirName, $matches);
  if (count($matches)>1)
    $res = $matches[1];
  return $res;
}

/**
 * Enregistre une nouvelle enquête en base et renvoie son numéro
 * @param string $dirName
 * @return int le numero de l'enquete
 */
/*
function getNumEnquete($filename) {
  $res = null;
  
  $dataLoggerId = 'unknown';
  $matches =array();
  $test = preg_match('/^([0-9]+)_.*$/', $filename, $matches);
  if ($test==1 && count($matches)>1) {
    $dataLoggerId = $matches[1];
  }
  
  
  $conn = AlkFactory::getDbConn();
  $strSql = "INSERT INTO astrollendro.ast_enquete(
                  enq_famille, enq_dataloggerid, enq_timelastsync, enq_datadir, 
                  enq_json, enq_state)
                VALUES (
                  'Test', 
                  :enq_dataloggerid, 
                  now(), 
                  :enq_datadir, 
                  '{}', 
                  0)";
  $sth = $conn->prepare($strSql);
  $res = $sth->execute(array(
                  ':enq_dataloggerid' => $dataLoggerId,
                  ':enq_datadir' => $filename
                )
  );
  
  if ($res) {
    $enqueteId = intval($conn->lastInsertId("astrollendro.ast_enquete_enq_id_seq"));
  }
  return $enqueteId;
  
}
*/

/**
 * 
 */
function exportDataToGisFiles($idEnquete=-1, $outDir="") {
  $res = null;
  
  $strCmd = ALK_AST_PROCESSING_PATH . "/export_track_data.sh " . $outDir . " " . $idEnquete;
  $output = array();
  echo "Export des traces de l'enquete $idEnquete au format SIG\n";
  exec($strCmd, $output, $res);
  
  return $res;
}

/**
 * Nettoyage du fichier csv de traces GPS pour supprimer 
 * les doublons et ré-ordonner les traces
 */
function cleanGpsFile($gpsCsvFilePath) {
  $cleanedFilePath = $gpsCsvFilePath . '_cleaned';
  $strCmd = 'head -n 1 '.$gpsCsvFilePath.' > ' . $cleanedFilePath. ';';
  $strCmd .= 'tail -n +2 '.$gpsCsvFilePath.' | sort -u -t";" -k1,1 - >> ' . $cleanedFilePath. ';';
  $strCmd .= 'mv ' . $cleanedFilePath. ' ' . $gpsCsvFilePath;
  $output = array();
  echo "Nettoyage du fichier de trace GPS $gpsCsvFilePath\n";
  exec($strCmd, $output, $res);
  
  return $res;

}

/**
 * Nettoyage des données en base
 */
function cleanDataInDb($idEnquete, $dataDir, $datFilePath, $filePrefix, $params) {
  $res = false;
  $dbConn = AlkFactory::getDbConn();
  $logFilename = $dataDir . "/" . "trackingMonitor.log";
  
  $fileLogger = new AlkFileLogger($logFilename);
  $outDir = $dataDir;
  $loader = new AlkPositionLoaderAst($dbConn, $idEnquete, $params, $dataDir, $outDir, $fileLogger);
  echo "Nettoyage de la base " . $idEnquete . "\n";
  $res = $loader->cleanDb();
  
  return $res;
}

/**
 * Fonction principale
 * @param int $argc  nombre d'arguments passés au script en ligne de commande
 * @param string array $argv tableau des arguments
 */
function main($argc, $argv) {
  // todo à améloorer
  global $PARAMS_ASTRO;
  global $PARAMS_MOBIKIDS;
  
  // récupération paramètres généraux de traitement
  $params = $PARAMS_MOBIKIDS;

  // utilisation paramètres en ligne de commande
  if ($argc < 2) {
    error_log("Usage " . $argv[0] . " dataDir Enq [clean]");
    exit(-1);
  }
  
  $cleanDb = false;
  if (strcmp($argv[$argc - 1], 'clean') == 0) {
    $cleanDb = true;
  }
/*
  $datFilePath = realpath($argv[1]);
  $filePrefix = basename($datFilePath, '.dat');
  $dataDir = dirname($datFilePath) .  '/' . $filePrefix;
  */

  $dataDir =  realpath($argv[1]);
  $filePrefix = basename(rtrim($dataDir, '/'));
  //die($datFilePath . "  " . $dataDir);
  /*  
  $res = prepareDir($datFilePath, $dataDir);  
  if ($res!=true) {
    error_log("Erreur dans l'initialisation du répertoire");
    exit(-1);
  }
  // mise à jour chemin fichier .dat 
  $datFilePath = $dataDir . '/' . basename($datFilePath);
  */
  $res = prepareDirDat($dataDir);  
    
  //exit(0);
  // récupération de l'id de l'enquete
  $idEnquete = getNumEnquete(basename($dataDir));
  //echo "idEnquete " . $idEnquete . "\n";
    
  // decodage fichier .dat
  updateEnqueteState($idEnquete, AST_STATE_ENQ_PROCESSING_DAT);
  $res = decodeDatFiles($dataDir); 
  if ($res!=0) {
    error_log("Erreur dans le traitement des fichiers .dat");
    exit(-1);
  }

  // nettoyage fichier gps
  $gpsFilePath = $dataDir  . '/' . basename($dataDir) . '_gps.csv';
  $res = cleanGpsFile($gpsFilePath);
  if ($res!=0) {
    error_log("Erreur dans le nettoyage du fichier GPS");
    exit(-1);
  }


  // extraction des données d'activités
  $inFilePath = $dataDir  . '/' . basename($dataDir) . '_accelero.csv'; 
  $outFilePath = $dataDir  . '/' . basename($dataDir) . '_mobility.csv';
  $res = computeMobilityInfo($inFilePath, $outFilePath);  
  if ($res!=0) {
    error_log("Erreur dans l'extraction des informations de mobilité");
    $params["MOBILITY_FILE_ERROR"] = true;
    //exit(-1);
  }
  else {
    $params["MOBILITY_FILE_ERROR"] = false;
  }

  //echo "fileprefix " . $filePrefix . "\n"; 
  // nettoyage eventuelle de la base
  if ($cleanDb) {
    $res = cleanDataInDB($idEnquete, $dataDir, $datFilePath, $filePrefix, $params);  
    if ($res!=true) {
      error_log("Erreur dans le nettoyage de la base");
      exit(-1);
    }
  }

  // traitement des données
  //echo $idEnquete. "  " . $dataDir. "  " . $datFilePath. "  " . $filePrefix ."\n";

  updateEnqueteState($idEnquete, AST_STATE_ENQ_PROCESSING_TRACK);
  $res = processData($idEnquete, $dataDir, $dataDir, $filePrefix, $params);  
  updateEnqueteState($idEnquete, AST_STATE_ENQ_ROADMAP_CREATED);
  if ($res!=true) {
    error_log("Erreur dans le traitement des traces");
    exit(-1);
  }
  
  // export format SIG
  $res = exportDataToGisFiles($idEnquete, $dataDir);
  if ($res!=0) {
    error_log("Erreur dans l'export des traces au format SIG");
    exit(-1);
  }
  
  // suppression du fichier .process
  $res = unlink(ALK_AST_DATADIR_PATH . '/' . '.process');
  if (!$res) {
    error_log("Impossible de supprimer le fichier lock .process");
    exit(-1);
  }

}

// calling main
main($argc, $argv);
exit(0);
