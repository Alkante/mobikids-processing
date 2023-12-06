<?php

/*licence/

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Module::JMaplink
Module JMaplink.
Ce module appartient au framework Alkanet.

Ce logiciel est régi par la licence CeCILL-C soumise au droit français et
respectant les principes de diffusion des logiciels libres. Vous pouvez
utiliser, modifier et/ou redistribuer ce programme sous les conditions
de la licence CeCILL-C telle que diffusée par le CEA, le CNRS et l'INRIA
sur le site http://www.cecill.info.

En contrepartie de l'accessibilité au code source et des droits de copie,
de modification et de redistribution accordés par cette licence, il n'est
offert aux utilisateurs qu'une garantie limitée. Pour les mêmes raisons,
seule une responsabilité restreinte pèse sur l'auteur du programme, le
titulaire des droits patrimoniaux et les concédants successifs.

A cet égard l'attention de l'utilisateur est attirée sur les risques
associés au chargement, à l'utilisation, à la modification et/ou au
développement et à la reproduction du logiciel par l'utilisateur étant
donné sa spécificité de logiciel libre, qui peut le rendre complexe à
manipuler et qui le réserve donc à des développeurs et des professionnels
avertis possédant des connaissances informatiques approfondies. Les
utilisateurs sont donc invités à charger et tester l'adéquation du
logiciel à leurs besoins dans des conditions permettant d'assurer la
sécurité de leurs systèmes et ou de leurs données et, plus généralement,
à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.

Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
pris connaissance de la licence CeCILL-C, et que vous en avez accepté les
termes.

/licence*/

include_once(ALK_ALKANET_ROOT_PATH."lib/app_conf_alkanet.php");
include_once(ALK_ALKANET_ROOT_PATH."classes/pattern/alkfactory.class.php");
include_once(ALK_ALKANET_ROOT_PATH."scripts/util.php");

/**
 * @package Alkanet_Module_JMaplink
 * @class AlkTrackingMonitorErcoGener
 *
 * Classe chargée de traiter les remontées des balises ErcoGener
 * pour la segmentation en trajet lors 
 * de chaque insertion des données en historique
 */
class AlkTrackingMonitorAst
{
  public $dbConn = "";
  protected $fileLogger = null;
  
  // liste des différents états 
  // de la machine d'état de détection de trajet
  private static $STATE_NONE        			= -1;
  private static $STATE_START_POS   			=  0;
  private static $STATE_MOVING      			=  1;
  private static $STATE_STOPPED     			=  2;
  private static $STATE_STOP_POS    			=  3;
 
  private static $STATE_MICROSTOP_POS 			=  4; // debut micro arret possible (v<1km/h)
  private static $STATE_MICROSTOP_POS2 			=  5; // micro arret possible, mais non confirme (<15s)
  private static $STATE_MICROSTOPPED        	=  6; // micro arret confirme (>=15s)
  
  // filtres microarret
  private static $FILTER_MICROSTOP_NONE = 0;
  private static $FILTER_MICROSTOP_SHORTMOTORIZED = 1;
    
  // variables d'état
  protected $lastState 		= -1;
  protected $previousPosId= -1;
  protected $currentState 	= null;
  protected $currentPosKnown 	= false; 	// est-ce la position de la remontee courante est connue ?
  protected $currentSpeed 	= 0; 		// vitesse courante
  protected $currentLogCode 	= null; 	// code de retour ErcoGener de la remontee courante
  protected $subTrackPeriod 	= 0; 		// temps entre la remontee precedente et la remontee courante en s.
  protected $subTrackLength 	= 0; 		// distance parcourue entre la remontee precedente et la remontee courante en m.
  
  // variables d'état microstop
  protected $lat 	            = null; 	// current latitude
  protected $lon                = null; 	// current longitude
  protected $time               = null;   // time of current pos
  protected $pos               = null;   // gid of current pos
  protected $distMicroStop      = null;
  protected $periodMicroStop      = null;  
  protected $FILTER_MICROSTOP  = null;
  
  protected $device_id 		= 1; 	// identifiant de la balise

  

  // codes de log de la balise RF-TRACK
  protected static $AST_INACTIV         = 'inactv';
  protected static $AST_GPS_STOP        = 'gps_0|stop';
  protected static $AST_GPS_START       = 'gps_1|start';
  protected static $AST_GPS_TTF         = 'ttf';
  
  // constantes liées  au fonctionnement du Tracker RF-TRACK
  ////protected static $AST_TRACKER_GPS_PERIOD  = 5; // period entre 2 prises de position GPS en s.
  
  
  
  // constantes d'optimisation
  protected static $CORRECTPOS_TOO_OLD		= 3600; 	// 1 heure
  protected static $MIN_TRACK_DURATION		= 60; 		// 1 minute	
  protected static $MIN_STOP_DURATION			= 60; 		// 1 minute	
  protected static $FAKE_TRACK_DURATION   = 1800;   // 30 minutes (duree max d'une sequence d'immobilité identifiée comme trajet pour filtrage)
  protected static $FAKE_TRACK_RADIUS     = 100;     // 100 m (amplitude geo max d'une sequence d'immobilité identifiée comme trajet pour filtrage)
  
  
  // constantes rattachement lieu/trajet
  protected static $LIEU_MAX_DISTANCE		= 50; 	// 50m (avt 150m)
  protected static $LIEU_MAX_DURATION		= 300; 	// 5 minutes
  protected static $DURATION_MIN_INTER_TRACK = 1800; // 30 minutes

  // constantes traitement micro arret 
  ////protected static $MICROSTOP_MAX_SPEED = 3;      // vitesse maximale dans micro arret
  ////protected static $MICROSTOP_MAX_DISTANCE = 20;  // rayon maximal du micro arret
  ////protected static $MICROSTOP_MIN_DURATION = 30;  // durée minimum du micro arret (en s.)
  ////protected static $MICROSTOP_MODALITY_LOOKUP_DURATION = 180; // duree d'etude de la modalité de transport (vitesse) avant et après le micro-arret (en s.)
  ////protected static $MICROSTOP_MODALITY_MOTOR_FILTER_MIN_DURATION = 50; // duree minimum d'un micro-arret en mode motorise (en s.)
  ////protected static $MICROSTOP_MODALITY_MOTOR_MIN_SPEED = 15; // vitesse minimale de détection d'un déplacement motorisé avant et après micro-arret (en km/h)
  
  // constantes process diverses
  protected static $AJUST_LIEU = true;
  
  // filtrage des extrémité de trajets
  protected static $TRACK_EX_FILTER_WINDOW_DURATION = 30; // taille de la fenêtre glissante de synthèse en s.
  protected static $TRACK_EX_FILTER_WINDOW_START_OFFSET = 30; // decalage du début de la fenêtre glissante en début de trajet (pour éviter l'effet demarrage d'activité)
  protected static $TRACK_EX_ANALYSIS_DURATION = 3600;    // taille de la fenêtre d'analyse des extrémités de trajets 
  protected static $TRACK_EX_MAX_CAP0_PER_WINDOW = 2;     // nombre minimum de cap à 0 par fenetre glissante qualifiant le statut d'immobilité (dépendant de la taille de la fenêtre)
  protected static $TRACK_EX_RADIUS_LIEU = 50;    // avt 120       // distance max entre le centroide des position précédente et une position dans une zone d'immobilité
  protected static $TRACK_EX_MED_VIT_IMMOBILITY = 2.00;    // vitesse mediane limite en deça de laquelle on considère immobilité sur la fenêtre (dépendant de la taille de la fenêtre)
  
  // correction TTF 
  protected static $TTF_MIN_CORRECT = 20;       // duree minimale à partir de la quelle on cherche à compenser le ttf
  protected static $TTF_MIN_CORRECT_DIST = 50; //100 avt // seuil minimal par defaut de l'ecart entre la derniere position et 
 
  // parametres
  protected $tablename_position;
  protected $tablename_track;
  protected $trackNumber;

  // memory state
  protected $currentMicroStop = null;
  protected $currentLieu = null;
  protected $firstLieu = null;
  protected $lastLieu = null;
  protected $microStopTrack = array(); // liste des microstop associé au trajet courant
  
  
  
  
  /**
   * Constructeur par défaut
   * @param dbConn Classe de connection à la base
   * @param fileLogger Classe de log
   */
  public function __construct(&$dbConn, &$fileLogger = null, 
    $tablename_position='positions', 
    $tablename_track='trajets', 
    $tablename_lieu='lieux', 
    $tablename_microarret='microarrets', 
    $tablename_info='infos', 
    $params=null)
  {
    $this->dbConn = $dbConn;
    $this->fileLogger = $fileLogger;
    $this->tablename_position = $tablename_position;
    $this->tablename_track = $tablename_track;
    $this->tablename_lieu = $tablename_lieu;
    $this->tablename_microarret = $tablename_microarret;
    $this->tablename_info = $tablename_info;
    $this->trackNumber = 0;
    
    //$this->FILTER_MICROSTOP = self::$FILTER_MICROSTOP_NONE;
    //filtrage micro arret short motorized
    $this->FILTER_MICROSTOP = self::$FILTER_MICROSTOP_SHORTMOTORIZED;
    
    $this->fileLogger = $fileLogger;
    if ($this->fileLogger==null)
      $this->fileLogger = new AlkFileLogger(realpath(dirname(__FILE__)) . "/" . __CLASS__ . ".log");
    
    $this->fileLogger->write("this->tablename_position " . $this->tablename_position);

    // recupération des paramètres
    if ($params!=null) { 
      $this->params = $params;
    }
    else {
      $this->params = $this->defaultParams;
    }
  }

  public function run() {
    // nettoyage de la table trajets et des colonnes de traitement dans position;
    $this->fileLogger->write("[".__CLASS__."] Nettoyage des données");
    $sqlCommands = array(
      "TRUNCATE $this->tablename_track",
      "UPDATE $this->tablename_position SET track = null, trackmode = null"
    );
    $this->execSqlCommands($sqlCommands);


    // reconstruction des itinéraires
    $strSql = "SELECT gid as id FROM $this->tablename_position ORDER BY time DESC LIMIT 1";
    $lastId = $this->dbConn->getScalarSql($strSql, -1);	
    $strSql = "SELECT gid as id FROM $this->tablename_position ORDER BY time ASC";	
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
        $id = $dr->getValueName('id');
        $lastPos = intval($id)==intval($lastId);
        $res = $this->handle($id, $lastPos);
        //$this->fileLogger->write("[".__CLASS__."] Processing message $id : $res");				
    }
  }
  
  public function handle($pos_id, $isLastRemontee=false)
  {
   
    // recupération de l'état précédent 
    // et des informations sur la remontée courante
    $remontee = $this->getRemontee($pos_id);
    if (count($remontee) == 0)
        return false;
    $lastRemontee = $this->getLastRemontee($pos_id);
    // micro Stop ?
    $microStop = $this->getMicroStopInfo($pos_id);
    
    ////////////////////////////
    // Machine d'état
    ////////////////////////////
    if ($isLastRemontee) {
      // cas particulier de la dernière position
      // en arrêt de façon systématique
      $this->currentState = self::$STATE_STOP_POS;
    }
    else { 
      switch($this->lastState) {
        // pas de remontee precedentes
        case self::$STATE_NONE:
          $this->currentState = self::$STATE_STOPPED;
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              $this->currentState = self::$STATE_START_POS;
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else {
                  $this->currentState = self::$STATE_START_POS;
              }
          }
          break;
        
        case self::$STATE_START_POS:
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              $this->currentState = self::$STATE_MOVING;
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else {
                  $this->currentState = self::$STATE_MOVING;
              }
          }
          break;
        
        case self::$STATE_STOP_POS:
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              $this->currentState = self::$STATE_START_POS;
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOPPED;
              }
              else {
                  $this->currentState = self::$STATE_STOPPED;
              }
          }
          break;
        
        case self::$STATE_MOVING:
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
            if ($this->subTrackPeriod >= self::$DURATION_MIN_INTER_TRACK) {
              $this->currentState = self::$STATE_START_POS;
            }
            else {
              $this->currentState = self::$STATE_MOVING;
            }
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else if ($this->currentSpeed<=$this->params["MICROSTOP_MAX_SPEED"] && $this->subTrackLength<=$this->params["MICROSTOP_MAX_DISTANCE"]) {
                  $this->currentState = self::$STATE_MICROSTOP_POS;
              }
              else {
                  $this->currentState = self::$STATE_MOVING;
              }
          }
          break;
        
        case self::$STATE_STOPPED:
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              $this->currentState = self::$STATE_START_POS;
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOPPED;
              }
              else {
                  $this->currentState = self::$STATE_STOPPED;
              }
          }
          break;
          
        case self::$STATE_MICROSTOP_POS:
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              if ($this->subTrackPeriod >= self::$DURATION_MIN_INTER_TRACK) {
                $this->currentState = self::$STATE_START_POS;
              }
              else {
                $this->currentState = self::$STATE_MOVING;
              }
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else if ($microStop && 
                      ($this->currentSpeed<=$this->params["MICROSTOP_MAX_SPEED"] && 
                      $this->distMicroStop<=$this->params["MICROSTOP_MAX_DISTANCE"] &&
                      $this->periodMicroStop<=$this->params["MICROSTOP_MIN_DURATION"] )) {
                  $this->currentState = self::$STATE_MICROSTOP_POS2;
              }
              else {
                  $this->currentState = self::$STATE_MOVING;
              }
          }
          break;
          
        case self::$STATE_MICROSTOP_POS2:
          //$this->fileLogger->write("[".__CLASS__."] : Microstop --> pos_id: $pos_id, lastState: $this->lastState, microstop : $microStop, currentspeed : $this->currentSpeed, distMicrostop: $this->distMicroStop, periodMicroStop: $this->periodMicroStop");
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              if ($this->subTrackPeriod >= self::$DURATION_MIN_INTER_TRACK) {
                $this->currentState = self::$STATE_START_POS;
              }
              else {
                $this->currentState = self::$STATE_MOVING;
              }
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else if ($microStop && 
                      ($this->currentSpeed<=$this->params["MICROSTOP_MAX_SPEED"] && 
                      $this->distMicroStop<=$this->params["MICROSTOP_MAX_DISTANCE"])) {
                  if ($this->periodMicroStop<$this->params["MICROSTOP_MIN_DURATION"]) { 
                      $this->currentState = self::$STATE_MICROSTOP_POS2;
                  }
                  else {
                      $this->currentState = self::$STATE_MICROSTOPPED;
                  }
              }
              else {
                  $this->currentState = self::$STATE_MOVING;
              }
          }
          break;
        
        case self::$STATE_MICROSTOPPED:
          //$this->fileLogger->write("[".__CLASS__."] : Microstop --> pos_id: $pos_id, lastState: $this->lastState, microstop : $microStop, currentspeed : $this->currentSpeed, distMicrostop: $this->distMicroStop, periodMicroStop: $this->periodMicroStop");
          if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_START)) ||
              ($this->matchLogCode($this->currentLogCode,self::$AST_GPS_TTF))) {
              if ($this->subTrackPeriod >= self::$DURATION_MIN_INTER_TRACK) {
                $this->currentState = self::$STATE_START_POS;
              }
              else {
                $this->currentState = self::$STATE_MOVING;
              }
          }
          else {
              if (($this->matchLogCode($this->currentLogCode,self::$AST_GPS_STOP)) ||
                  ($this->matchLogCode($this->currentLogCode,self::$AST_INACTIV))) {
                  $this->currentState = self::$STATE_STOP_POS;
              }
              else if ($microStop && 
                      ($this->currentSpeed<=$this->params["MICROSTOP_MAX_SPEED"] && 
                      $this->distMicroStop<=$this->params["MICROSTOP_MAX_DISTANCE"])) {
                  $this->currentState = self::$STATE_MICROSTOPPED;
              }
              else {
                  $this->currentState = self::$STATE_MOVING;
              }
          }
          break;    
          
        
        default:
          // ne doit pas arriver
          $this->fileLogger->write("[".__CLASS__."] : Unexpected case in state machine transitions.");
        break;
      
      }
    }
    /////////////////////////////
    // traitement micro arret
    /////////////////////////////

    if ($this->currentMicroStop!=null) {
      // si micro stop confirmé et terminé en durée, on met à jour les infos de validité et de fin
      if (($this->lastState == self::$STATE_MICROSTOPPED) && 
        ($this->currentState!= self::$STATE_MICROSTOPPED) && 
        ($this->currentState != self::$STATE_STOP_POS) ) {
        $this->currentMicroStop["valid"] = true;
        $this->currentMicroStop["time_end"] = $lastRemontee['time'];
        $this->currentMicroStop["pos_end"] = $lastRemontee['pos_id'];
      }
      // sinon on verifie qu'on est toujours en microstop probable
      // ou qu'il ne faut pas enregistrer le micro stop
      if (($this->currentState != self::$STATE_MICROSTOP_POS) && 
        ($this->currentState != self::$STATE_MICROSTOP_POS2) && 
        ($this->currentState != self::$STATE_MICROSTOPPED)) {
  
        if ($this->currentState != self::$STATE_STOP_POS 
          && $this->currentMicroStop["valid"]==true) {
          $toBeKept = $this->checkMicroStop($this->currentMicroStop);
          if ($toBeKept)
            $this->microStopTrack[] = $this->currentMicroStop;
          
          //$this->saveMicroStop();
        }
        // réinitialisation micro stop courant
        $this->currentMicroStop = null;
      }
    } 
    else if ($this->currentState == self::$STATE_MICROSTOP_POS) {
      $this->currentMicroStop = array(
        "valid" => false,
        "type" => "uncategorized",
        "time_start" => $this->time,
        "pos_start" => $pos_id
      );
    }
    
    ////////////////////////
    // traitement lieu
    ////////////////////////
    $idLieu = $this->getCurrentLieu($pos_id);
    if ($idLieu!=null) {
      if ($this->currentLieu==null) {
        $this->currentLieu = array(
          "pos_start" => $pos_id,
          "id" => $idLieu,
          "pos_end" => $pos_id
        );
      }
      else {
        if ($this->currentLieu["id"] == $idLieu) {
          $this->currentLieu["pos_end"] = $pos_id;
        }
        else {
          $this->currentLieu = array(
            "pos_start" => $pos_id,
            "id" => $idLieu,
            "pos_end" => $pos_id
          );
        }
      }
      if ($this->firstLieu==null) {
          $this->firstLieu = $this->currentLieu;        
      }
      $this->lastLieu = $this->currentLieu;
    }
    else {
      $this->currentLieu = null;
    }
   
    // enregistrement de l'état courant
    $this->updateField($pos_id, $this->tablename_position, "trackmode", $this->currentState);
    if (($this->currentState !=  self::$STATE_STOPPED) 
        && ($this->currentState !=  self::$STATE_NONE)) {
            
        $this->updateField($pos_id, $this->tablename_position, "track", $this->trackNumber);
    }
    //////////////////////////////////
    // traitement de l'état courant
    //////////////////////////////////
    // -> cas des trajets avec fin non explicite côté GPS
    if (($this->currentState == self::$STATE_START_POS && 
        ($this->lastState == self::$STATE_MOVING || 
         $this->lastState == self::$STATE_MICROSTOP_POS || 
         $this->lastState == self::$STATE_MICROSTOP_POS2 || 
         $this->lastState == self::$STATE_MICROSTOPPED))
         //|| (intval($remontee["duration"]) >= (5*$this->params["TRACKER_GPS_PERIOD"]))
         ) { // on est sur un trajet dont le stop n'a pas été capturé par les info du GPS
        // mise à jour de l'état de la position précédente : MOVING -> STOP_POS
        $this->updateField($this->previousPosId, $this->tablename_position, "trackmode", $this->currentState);
        $pos_start = $this->getStartEndPosId(array(self::$STATE_START_POS), $this->previousPosId, $this->device_id);
        $pos_end = $this->previousPosId;
        if (!($pos_start==null)) {
            //recuperation info track; 
            $trackInfo = $this->getTrackInfo($this->device_id, $pos_start, $pos_end);
            $trackValid = $this->isValidTrack($trackInfo);
            if ($trackValid) {
              $this->processTrack($pos_start, $pos_end, $trackInfo);
              // enregistrements des microarret associes  
              $this->saveMicroStops($this->microStopTrack, $this->trackNumber, $pos_start, $pos_end);
              $this->microStopTrack = array();
              $this->trackNumber++;
              
            }
            else {
              $this->fileLogger->write("[".__CLASS__."] : FAKE TRACK beetween $pos_start and $pos_end (" . $trackInfo["radius"] . ")");
            }
        }
        else {
            $this->fileLogger->write("[".__CLASS__."] : Impossible de de déterminer la position de départ du trajet (device_id : $this->device_id, pos_id : $pos_id)");
        }
    }
    // -> cas classique
    if ($this->currentState == self::$STATE_STOP_POS) { // on est sur un STOP avec position connue --> on enregistre le trajet
        $pos_start = $this->getStartEndPosId(array(self::$STATE_START_POS), $pos_id, $this->device_id);
        $pos_end = $pos_id;
        if (!($pos_start==null)) {
          //recuperation info track; 
          $trackInfo = $this->getTrackInfo($this->device_id, $pos_start, $pos_end);
          $trackValid = $this->isValidTrack($trackInfo);
          if ($trackValid) {
            $this->processTrack($pos_start, $pos_end, $trackInfo);    
            // enregistrements des microarret associes
            $this->saveMicroStops($this->microStopTrack, $this->trackNumber, $pos_start, $pos_end);
            $this->microStopTrack = array();
            $this->trackNumber++;
            
          }
          else {
            $this->fileLogger->write("[".__CLASS__."] : FAKE TRACK beetween $pos_start and $pos_end");
            $this->fileLogger->write("[".__CLASS__."] : " . print_r($trackInfo, true));
          }
        }
        else {
            $this->fileLogger->write("[".__CLASS__."] : Impossible de de déterminer la position de départ du trajet (device_id : $this->device_id, pos_id : $pos_id)");
        }
    }
    
    $etatActuel    = $this->prettyPrintState($this->currentState);
    $etatPrecedent = $this->prettyPrintState($this->lastState);
    $positionConnue = ($this->currentPosKnown>0) ? 'oui' : 'non';
    
    return "Balise " . $this->device_id . " Status: " . $this->currentLogCode . " Etat précédent:  " . $etatPrecedent ." Etat actuel: " . $etatActuel . " avec position: " . $positionConnue;
  }
  
  protected function getRemontee($pos_id){
      
      $strSql = "select 
            1 as device_id,
            lat,
            lon,
            time,
            vit as speed,
            nbsat as sat,
            hdop as hdop,
            seg_length as segment_length,
            seg_duration as duration,
            --CASE 
            --    WHEN msg_info like 'ttf%' THEN 'ttf'
            --    ELSE msg_info 
            --END as log_code
            msg_info as log_code
            from 
                $this->tablename_position
            where 
                gid = $pos_id";
      $ds = $this->dbConn->initDataSet($strSql);
      $res = Array();
      if ($dr = $ds->getRowIter()){
          $res["device_id"] = $dr->getValueName("device_id");
          $res["lat"] = $dr->getValueName("lat");
          $res["lon"] = $dr->getValueName("lon");
          $res["speed"] = $dr->getValueName("speed");
          $res["segment_length"] = $dr->getValueName("segment_length");
          $res["log_code"] = $dr->getValueName("log_code");
          $res["duration"] = $dr->getValueName("duration");
          $res["time"] = $dr->getValueName("time");
          
          // initialisation des variables d'état
          $this->subTrackLength	=	$res["segment_length"];
          $this->currentSpeed		=	$res["speed"];
          $this->currentPosKnown	=	$res["lat"]!=null && $res["lon"]!=null;
          $this->currentLogCode	=	$res["log_code"];
          $this->device_id		=	$res["device_id"];   
          $this->subTrackPeriod 	= $res["duration"];
          $this->lat                = $res["lat"];
          $this->lon                = $res["lon"]; 
          $this->time               = $res["time"]; 
      }
      return $res;
  }
  
  protected function getLastRemontee($pos_id){
      
      $strSql = "select 
                    trackmode as position_track_mode,
                    gid,
                    r2.time
                    from (
                        select time from $this->tablename_position where gid = $pos_id
                  ) r1
                    join(
                        select time, trackmode, gid from $this->tablename_position
                  ) r2
                    on r1.time > r2.time
                    order by r2.time desc
                    limit 1";
      $strSql = "
          SELECT 
            trackmode as position_track_mode,
            gid,
            time 
          FROM $this->tablename_position WHERE gid = ($pos_id-1)";
      $ds = $this->dbConn->initDataSet($strSql);
      
      $res = Array();
      if ($dr = $ds->getRowIter()){
          $res["mode"] = $dr->getValueName("position_track_mode");
          $res["time"] = $dr->getValueName("time");
          $res["pos_id"] = $dr->getValueName("gid");
          //initialisation des variables d'état
          $this->lastState 		= $res["mode"];
          $this->previousPosId = $res["pos_id"];
      }
      //echo $pos_id . " " . $res["pos_id"] . "\n";  
      return $res;
      
  }
  
  protected function updateField($pos_id, $tablename, $fieldname, $value) {
      
      $res = true;
      $this->dbConn->setSchema('public');
      //looking if a row already exists for pos_id
      if ($this->checkExistingRow($tablename,$pos_id)){
  
          $strSQL = "UPDATE
                      $tablename
                      SET $fieldname = '$value'
                      WHERE gid = $pos_id";
  
          $res = $this->dbConn->executeSQL($strSQL/*,false*/);
      }else{
          $strSQL = "INSERT INTO $tablename (id)
                      SELECT $pos_id
                      WHERE NOT EXISTS (
                          SELECT gid
                          FROM $tablename
                          WHERE
                          gid = $pos_id
                      )";
  
          $this->dbConn->executeSQL($strSQL/*,false*/);
  
          $strSQL = "UPDATE
                      $tablename
                      SET $fieldname = '$value'
                      WHERE gid = $pos_id";
  
          $res = $this->dbConn->executeSQL($strSQL/*,false*/);
           
      }
      //die($strSQL);
      return $res;
      
    }


    protected function checkExistingRow($tablename,$pos_id) {
        
        //looking if a row already exists for pos_id
        $res = false;
        $strSQL = "SELECT * FROM $tablename WHERE gid = $pos_id LIMIT 1";

        $ds = $this->dbConn->initDataset($strSQL);
        while($dr = $ds->getRowIter()){
            $res = true;
        }
        return $res;
        
    }
    
    protected function processTrack($pos_start, $pos_end, $trackInfo) {
      $lieu_start = -1;
      $lieu_end = -1;
/*
      $trackInfo = $this->correctTTF($trackInfo);
      $pos_start = $trackInfo['pos_s'];
      $pos_end   = $trackInfo['pos_e'];
      list($new_pos_start, $new_pos_end) = $this->adjustTrackExtremities($pos_start, $pos_end);
      if ($trackInfo['comments']=='') { // pas d'ajustement ttf
        if ($new_pos_start!=$pos_start || $new_pos_end!=$pos_end) {
          $trackInfo = $this->getTrackInfo($this->device_id, $new_pos_start, $new_pos_end);
          if ($pos_start != $new_pos_start) {
            $trackInfo['comments'] = $trackInfo['comments'] . "ajustement extremite depart;";
            $trackInfo['pos_s'] = $new_pos_start;
            $pos_start = $new_pos_start;
          }
          if ($pos_end != $new_pos_end) {
            $trackInfo['comments'] = $trackInfo['comments'] . "ajustement extremite arrivee;";
            $trackInfo['pos_e'] = $new_pos_end;
            $pos_end = $new_pos_end;
          }
        }
      }
      else { // ajustement ttf realise
        if ($new_pos_start!=$pos_start || $new_pos_end!=$pos_end) {
          $existingComment = $trackInfo['comments'];
          $trackInfo = $this->getTrackInfo($this->device_id, $trackInfo['pos_s'], $new_pos_end, $trackInfo['time_s']);
          if ($new_pos_end!=$pos_end) {
            $pos_end = $new_pos_end;
            $trackInfo['pos_e'] = $new_pos_end;
            $trackInfo['comments'] = $existingComment . "ajustement extremite arrivee;";
          }
        }
      }
  */    
      if ($this->firstLieu!=null && $this->checkPositionLieu($pos_start, $this->firstLieu) && self::$AJUST_LIEU) {
        $lieu_start = $this->firstLieu["id"];
        //$pos_start = $this->firstLieu["pos_end"]; // debut du trajet quand on sort du lieu
      }
      else {
        $lieu_start = $this->saveLieu($pos_start);
      }
      if ($this->lastLieu!=null && $this->checkPositionLieu($pos_end, $this->lastLieu) && self::$AJUST_LIEU) {
        $lieu_end = $this->lastLieu["id"];
        //$pos_end = $this->lastLieu["pos_start"]; // fin du trajet quand on entre dans le lieu
      }
      else {
        $lieu_end = $this->saveLieu($pos_end);
      }
      $this->fileLogger->write("[".__CLASS__."] Sauvegarde trajet $this->trackNumber (pos_start : $pos_start -> pos_end : $pos_end | lieu_start : $lieu_start -> lieu_end : $lieu_end)");
      $this->insertTrack($this->device_id, $pos_start, $pos_end, $lieu_start, $lieu_end, $this->trackNumber, $trackInfo);

      $this->currentLieu = null;
      $this->firstLieu = null;
      $this->lastLieu = null;
    }
    
    
    

    
    
    /**
     * get information about track starting at pos_start and ending at pos_end
     * @return $array of track properties
     */
    protected function getTrackInfo($device_id, $pos_start, $pos_end, $time_start=null, $time_end=null) {
      $trackInfo = array();
      $strSql = "SELECT
                    p_start.*,
                    p_end.*,
                    extract('epoch' from(p_end.time_e -p_start.time_s)) as track_duration,
                    (SELECT 
                      radius
                    FROM 
                      (SELECT 
                        sqrt(ST_Area(ST_MinimumBoundingCircle(st_collect(st_transform(the_geom,2154)))) / pi()) as radius
                      FROM $this->tablename_position 
                      WHERE 
                        gid>=$pos_start and gid<=$pos_end
                      ) en
                    ) as radius,
                    COALESCE((SELECT 
                      sum(coalesce(seg_length,0))::int 
                    FROM 
                      $this->tablename_position 
                    WHERE 
                      gid IN (SELECT gid FROM $this->tablename_position WHERE gid>$pos_start AND gid<$pos_end)
                    ),0.0) as track_length
                 FROM
                    (SELECT
                      lat as lat_s, lon as lon_s," . 
                      ($time_start!=null ? "'" . $time_start . "'::timestamp without time zone" : "time ")  . " as time_s," .
                      "address_light as add_s 
                    FROM 
                      $this->tablename_position 
                    WHERE gid= $pos_start) p_start,
                    (SELECT
                      lat as lat_e, lon as lon_e," . 
                      ($time_end!=null ? "'" . $time_end . "'::timestamp without time zone" : "time ")  . " as time_e," .
                      "address_light as add_e 
                    FROM 
                      $this->tablename_position 
                    WHERE gid= $pos_end) p_end
                  ";
      $ds = $this->dbConn->initDataSet($strSql);
      if ($dr = $ds->getRowIter()){
        foreach($dr as $key => $val) {
          $trackInfo[$key] = $val;
        }
      }
      //var_dump($trackInfo);
      
      $trackInfo['pos_s'] = $pos_start;
      $trackInfo['pos_e'] = $pos_end;
      
      // empty comments about processing
      $trackInfo['comments'] = "";
      return $trackInfo;
    }
    
    
    /**
     * renvoie true si le trajet decrit par trackInfo est un trajet valide
     * false sinon
     * @param type $trackInfo
     * @return boolean track validity
     */
    protected function isValidTrack($trackInfo) {
      $res = true;
      // filtrage sequence immobilité
      $notValid = /*$trackInfo['track_duration']<=self::$FAKE_TRACK_DURATION && */$trackInfo['radius']<=self::$FAKE_TRACK_RADIUS;
      
      return !$notValid;
    }
    
    /**
     * 
     * @param int $device_id
     * @param type $pos_start
     * @param type $pos_end
     * @param type $lieu_start
     * @param type $lieu_end
     * @param type $trackNumber
     * @return boolean status of db insertion
     */
    protected function insertTrack($device_id, $pos_start, $pos_end, $lieu_start, $lieu_end, $trackNumber, $trackInfo){
      $strSql = "INSERT INTO $this->tablename_track (
              gid,device_id, 
              pos_start, lat_start, lon_start, time_start, 
              pos_end, lat_end, lon_end, time_end,  
              track_duration, track_length, the_geom, lieu_start, lieu_end, comments)
            VALUES ($trackNumber, $device_id, 
              ".$trackInfo['pos_s'].",
              ".$trackInfo['lat_s'].",  
              ".$trackInfo['lon_s'].",
              '".$trackInfo['time_s']."',
              ".$trackInfo['pos_e'].",
              ".$trackInfo['lat_e'].",  
              ".$trackInfo['lon_e'].",
              '".$trackInfo['time_e']."',
              ".$trackInfo['track_duration'].",
              ".$trackInfo['track_length'].",
               (SELECT 
                  --st_lineMerge(st_collect(seg_geom))
                  st_makeLine(t.the_geom) 
               FROM 
                  (SELECT the_geom, gid FROM $this->tablename_position 
                   WHERE gid>=".$trackInfo['pos_s']." AND gid<=".$trackInfo['pos_e']." ORDER BY gid ASC) t),
               $lieu_start,
               $lieu_end,
               '".$trackInfo['comments'] ."' 
            );";
      return $this->dbConn->executeSQL($strSql);
    }
    
    
    
    /**
     * renvoie la première position precedente de la balise device_id qui possède
     * un des modes du tableau $modes ou null si aucune position n'est trouvée
     */ 
    protected function getStartEndPosId($modes=array(-1), $pos_id, $device_id){
        $posStartEnd = null;
        $str_modes = count($modes>0) ? implode(',', $modes) : '-1';
        $strSql = "SELECT 
            gid as id
          FROM 
            $this->tablename_position
          WHERE 
            time < (
              SELECT time 
              FROM $this->tablename_position 
              WHERE gid = $pos_id
            )
          AND 
            trackmode IN ($str_modes)
          ORDER BY TIME DESC
          LIMIT 1;";
        $ds = $this->dbConn->initDataSet($strSql);
        if($dr = $ds->getRowIter()){
            $posStartEnd = $dr->getValueName("id");
        }
        else
            $this->fileLogger->write($strSql);
        /*
        if ($posStartEnd!=null) {
          // si la position correspond déjà à un début de d'itinéraire 
          // enregistré dans jmaplink_tracks, c'est que le début réel du 
          // parcours n'a pu etre bien capturé par la machine d'état, on 
          // prend comme début de ce parcours la fin du parcours enregistré
          $strSql = "SELECT 
                pos_start,
                r1.time as pos_start_time,
                pos_end,
                r2.time as pos_end_time,
                r.time as current_pos_time
                FROM 
                jmaplink_tracks t
                LEFT JOIN
                jmaplink_remontee r1
                ON 
                t.pos_start = r1.id
                LEFT JOIN
                jmaplink_remontee r2
                ON 
                t.pos_end = r2.id,
                (SELECT time FROM jmaplink_remontee WHERE id =".$pos_id.") r    
                WHERE
                r.time>=r2.time 
                AND
                t.device_id='".$device_id."'
                AND
                ".$posStartEnd." IN (SELECT pos_start FROM jmaplink_tracks WHERE device_id='".$device_id."' AND pos_end IS NOT NULL)
                ORDER BY r2.time DESC";
          $ds = $this->dbConn->initDataSet($strSql);
          if($dr = $ds->getRowIter()){
            $posStartEnd = $dr->getValueName("pos_end");
          }   
        }*/
        return $posStartEnd;
    }


    /**
     * @return a string describing the @param state
     * 
     **/ 
    protected function prettyPrintState($state) {
    $str_states = array(
      -1 	=> "STATE_NONE",
      0 	=> "STATE_START_POS",
      1 	=> "STATE_MOVING",
      2 	=> "STATE_STOPPED",
      3 	=> "STATE_STOP_POS",
      4 	=> "STATE_MICROSTOP_POS", 
      5	    => "STATE_MICROSTOP_POS2",
      6	    => "STATE_MICROSTOPPED",
      7	    => "UNKNOWN");
    return $str_states[$state];
  }


  protected function matchLogCode($logCode, $filter) {
    $res = preg_match("/".$filter."/",$logCode)===1;
    return $res;
  }

  protected function getMicroStopInfo($pos_id) {
    $res = false;
    $this->distMicroStop    = null;
    $this->periodMicroStop  = null;
    
    if ($this->lat==null || $this->lon==null)
        return false;
    
    $posStartMicroStop = null;
    $mode = self::$STATE_MICROSTOP_POS;
    $strSql = "
          SELECT 
            gid as id
          FROM 
            $this->tablename_position
          WHERE 
            time < (
              SELECT time 
              FROM $this->tablename_position 
              WHERE gid = $pos_id
            )
          AND 
            trackmode IN ($mode)
          ORDER BY TIME DESC
          LIMIT 1;";
    $ds = $this->dbConn->initDataSet($strSql);
    if($dr = $ds->getRowIter()){
        //$posStartMicroStop = $dr->getValueName("id") - 1; // le microstop commence juste avant l'état MICROSTOPPOS
        $posStartMicroStop = $dr->getValueName("id"); // le microstop commence juste avant l'état MICROSTOPPOS
    }
    else {
        //$this->fileLogger->write($strSql);
        return false;
    }
    
    
    if ($posStartMicroStop==null) 
        return false;
    
    $nbPosNonStop = null;
    $modes = implode(',', array(self::$STATE_MICROSTOP_POS, self::$STATE_MICROSTOP_POS2, self::$STATE_MICROSTOPPED));    
    $strSql = "
      SELECT 
        count(*) as nbPosNonStop
      FROM 
        $this->tablename_position
      WHERE 
        time < (
          SELECT time 
          FROM $this->tablename_position 
          WHERE gid = $pos_id
        )
      AND
        time > (
          SELECT time 
          FROM $this->tablename_position 
          WHERE gid = $posStartMicroStop
        )
      AND
        trackmode NOT IN ($modes)
      ;";    
    $ds = $this->dbConn->initDataSet($strSql);
    if($dr = $ds->getRowIter()){
        $nbPosNonStop = $dr->getValueName("nbPosNonStop");
    }
    else {
        //$this->fileLogger->write($strSql);
        return false;
    }
    
    if (($nbPosNonStop == null) || ($nbPosNonStop > 0))
        return false;
    
    $strSql = "
        SELECT
            extract('epoch' from(t.end_time-t.start_time)) as duration,
            --ST_Distance(
            --    ST_GeometryFromText('POINT(' || $this->lon || ' ' || $this->lat || ')',4326)::geography,
            --    t.geom_bary::geography) as distance
            sqrt(ST_Area(ST_MinimumBoundingCircle(geom_collect)) / pi())*2.0 AS distance
        FROM
          (SELECT 
            --ST_GeometryFromText('POINT(' || avg(p.lon) || ' ' || avg(p.lat) || ')',4326) as geom_bary,
            ST_COLLECT(st_transform(p.the_geom,2154)) as geom_collect,
            min(t1.time) as end_time,
            min(t2.time) as start_time
          FROM 
            $this->tablename_position p ,
            (SELECT time 
              FROM $this->tablename_position 
              WHERE gid = $pos_id) t1,
            (SELECT time 
              FROM $this->tablename_position 
              WHERE gid = $posStartMicroStop) t2
          WHERE 
            --p.time < (t2.time + interval '" . $this->params["MICROSTOP_MIN_DURATION"] . " second') 
          --AND
            --p.time >= t2.time
            p.gid<=$pos_id
            AND
            p.gid>=$posStartMicroStop
          ) t
      ;";
    // obl : debug
    //echo $strSql."\n";    
    $ds = $this->dbConn->initDataSet($strSql);
    if($dr = $ds->getRowIter()) {
        $this->distMicroStop    = $dr->getValueName("distance");
        $this->periodMicroStop  = $dr->getValueName("duration");
    }
    if ($this->distMicroStop==null || $this->periodMicroStop==null) {
        $this->fileLogger->write($strSql);
        $this->fileLogger->write('PB !');
        exit(0);
    }
    
    return $this->distMicroStop!=null && $this->periodMicroStop!=null;
  }

  protected function saveMicroStops($microStopTrack, $trackNumber, $track_pos_start, $track_pos_end) {
    $strSql = "SELECT coalesce(max(gid),0)+1 FROM $this->tablename_microarret";
    $microStopId = intval($this->dbConn->getScalarSql($strSql,1));
    $commands = array();
    $gid = $microStopId;
    for($i=0; $i<count($microStopTrack); $i++) {
      $pos_start = $microStopTrack[$i]["pos_start"];
      $time_start = $microStopTrack[$i]["time_start"];
      $pos_end = $microStopTrack[$i]["pos_end"];
      $time_end = $microStopTrack[$i]["time_end"];
      $stop_type = $microStopTrack[$i]["type"];
      if ($pos_start>$track_pos_start && $pos_end<$track_pos_end) {
        $commands[] = "INSERT INTO $this->tablename_microarret(gid, lat, lon, the_geom, pos_start, time_start, pos_end, time_end, duration, radius, stop_type, track) 
          VALUES (
            $gid,
            (SELECT st_y(st_centroid(st_collect(the_geom))) as the_geom FROM $this->tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
            (SELECT st_x(st_centroid(st_collect(the_geom))) as the_geom FROM $this->tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
            (SELECT st_centroid(st_collect(the_geom)) as the_geom FROM $this->tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
            $pos_start,
            '".$time_start."',
            $pos_end,
            '".$time_end."',
            (SELECT extract('epoch' from('$time_end'::timestamp without time zone - '$time_start'::timestamp without time zone))),
            (SELECT sqrt(ST_Area(ST_MinimumBoundingCircle(st_collect(st_transform(the_geom,2154)))) / pi()) AS distance FROM $this->tablename_position WHERE gid>=$pos_start AND gid<=$pos_end),
            '$stop_type',
            $trackNumber
          )";
        $gid = $gid+1;
      }
    } 
    return $this->execSqlCommands($commands);
  }

  protected function getCurrentLieu($pos_id) {
    $res = null;
    
    $strSql = "SELECT 
          gid
        FROM
          $this->tablename_lieu
        WHERE
          ST_WITHIN(
            (SELECT st_transform(the_geom,2154) as the_geom FROM $this->tablename_position WHERE gid=$pos_id),
             the_geom
          ) 
        LIMIT 1";
    $ds = $this->dbConn->initDataSet($strSql);
    if ($dr = $ds->getRowIter()) {
        $res = $dr->getValueName("gid");
    }
    return $res;
  }
  
  protected function checkPositionLieu($pos, $lieu) {
    // on verifie si la distance min entre la position $pos et le lieu $lieu < $LIEU_MAX_DISTANCE
    // et s'il y a moins de $LIEU_MAX_DURATION seconde entre le point $pos et le point de $lieu 
    // le plus proche en horodatage de $lieu
    
    $strSql = "SELECT count(*) as cpt
      FROM
        $this->tablename_lieu AS l,
        (SELECT * FROM $this->tablename_position WHERE gid=$pos) AS p
          
      WHERE 
        ST_DWithin(l.the_geom, ST_Transform(p.the_geom,2154), ". self::$LIEU_MAX_DISTANCE. ")
      AND
       (SELECT 
        min(abs(extract('epoch' from(time - p.time)))) 
       FROM $this->tablename_position 
      WHERE gid >=". $lieu['pos_start'] . " and gid <= ". $lieu['pos_end']. ")<= ". self::$LIEU_MAX_DURATION . "
    ";
    //$this->fileLogger->write("[".__CLASS__."] $strSql"); 
    $res = intval($this->dbConn->getScalarSql($strSql,0))>0;
    return $res;
  }
  
  protected function saveLieu($pos) {
    $strSql = "SELECT coalesce(max(gid),0)+1 FROM $this->tablename_lieu";
    $lieuId = intval($this->dbConn->getScalarSql($strSql,1));
    
    $strSql = "INSERT INTO $this->tablename_lieu (gid, the_geom, pos_list, lat, lon) 
            VALUES 
              ($lieuId, 
              (SELECT ST_Buffer(ST_Transform(the_geom,2154),5) FROM $this->tablename_position WHERE gid=$pos),
              ARRAY[$pos]::int[],
              (SELECT lat FROM $this->tablename_position WHERE gid=$pos),
              (SELECT lon FROM $this->tablename_position WHERE gid=$pos) 
              )";
    
    $this->dbConn->executeSql($strSql);
    return $lieuId;
  }
  
  protected function checkMicroStop(&$microStopInfo) {
    $res = true;
    // recuperation des information de contexte sur le microarret :
    // vit_moy : vitesse moy avant et apres le microarret
    // vit_stddev : ecart type vitesse avant et apres microarret
    // vit_moy_pre : vitesse moy avant le microarret
    // vit_stddev_pre : ecart type vitesse avant microarret
    // vit_moy_post : vitesse moy après le microarret
    // vit_stddev_post : ecart type vitesse après microarret
    $contextInfo = array();

    $time_start = $microStopInfo['time_start'];
    $time_end = $microStopInfo['time_end'];
    $duration = self::duration($microStopInfo['time_start'], $microStopInfo['time_end']);
    $strSql = " SELECT
      (select avg(vit) as vit_moy from $this->tablename_position where (time>=('$time_start'::timestamp without time zone - interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time<'$time_start'::timestamp without time zone) or (time<=('$time_end'::timestamp without time zone + interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time>'$time_end'::timestamp without time zone)) 
        as vit_moy,
      (select stddev_pop(vit) as vit_stddev  from $this->tablename_position where (time>=('$time_start'::timestamp without time zone - interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds')and time<'$time_start'::timestamp without time zone) or (time<=('$time_end'::timestamp without time zone + interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time>'$time_end'::timestamp without time zone)) 
        as vit_stddev,
      (select avg(vit) as vit_moy_pre from $this->tablename_position where time>=('$time_start'::timestamp without time zone - interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time<'$time_start'::timestamp without time zone) 
        as vit_moy_pre,
      (select avg(vit) as vit_moy_post from $this->tablename_position where time<=('$time_end'::timestamp without time zone + interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time>'$time_end'::timestamp without time zone) 
        as vit_moy_post,
      (select stddev_pop(vit) as vit_stddev_pre from $this->tablename_position where time>=('$time_start'::timestamp without time zone - interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds')  and time<'$time_start'::timestamp without time zone) 
        as vit_stddev_pre,
      (select stddev_pop(vit) as vit_stddev_post from $this->tablename_position where time<=('$time_end'::timestamp without time zone + interval '".$this->params["MICROSTOP_MODALITY_LOOKUP_DURATION"]." seconds') and time>'$time_end'::timestamp without time zone) 
        as vit_stddev_post
     ";
    
    $ds = $this->dbConn->initDataSet($strSql);
    if ($dr = $ds->getRowIter()) {
        foreach($dr as $key => $val) {
          $contextInfo[$key] = $val;
        }
    }
    
    // strategie de qualification
    $shortType  = "short motorized";
    $longType   = "long motorized";
    // vitesse correspondant à une vitesse motorisée avant ou apres le microstop 
    if ($contextInfo['vit_moy_pre']>=$this->params["MICROSTOP_MODALITY_MOTOR_MIN_SPEED"] || 
      $contextInfo['vit_moy_post']>=$this->params["MICROSTOP_MODALITY_MOTOR_MIN_SPEED"]) {
      if ($duration >= $this->params["MICROSTOP_MODALITY_MOTOR_FILTER_MIN_DURATION"]) {
        $microStopInfo['type'] = $longType;
      }
      else {
        $microStopInfo['type'] = $shortType;
        if ($this->FILTER_MICROSTOP & self::$FILTER_MICROSTOP_SHORTMOTORIZED == self::$FILTER_MICROSTOP_SHORTMOTORIZED)
          $res = false;
      }
    }
    return $res;   
  }
  
  
  
  // fonction d'ajustement des extrémité de trajets
  protected function adjustTrackExtremities($pos_start, $pos_end) {
    
    
    $new_pos_start  = $this->adjustTrackExtremity($pos_start, $pos_end, true);
    $new_pos_end    = $this->adjustTrackExtremity($pos_start, $pos_end, false);
        
    return array($new_pos_start, $new_pos_end);
  }
  
  private function adjustTrackExtremity($track_start, $track_end, $doStart=true) {
    $w_pos_len = round((self::$TRACK_EX_FILTER_WINDOW_DURATION/$this->params["TRACKER_GPS_PERIOD"])/2);
    $nb_pos = round((self::$TRACK_EX_ANALYSIS_DURATION/$this->params["TRACKER_GPS_PERIOD"]));
    
    $strSql = 
      "select 
        gid,
        vit, 
        cap,
        hdop, 
        nbsat,
        w_len,
        w_time,
        round(((w_len*1.0)/(w_time*1.0))*(3600.0/1000.0),2) as w_vitmoy,
        w_max_vit,
        w_moy_vit,
        w_med_vit,
        seg_length,
        seg_duration,
        w_start,
        w_end,
        d_center, 
        w_cap_0 
      from
      (select 
        p.*,
        (select sum(seg_length) as w_len from (select seg_length from $this->tablename_position where gid>=w_start and gid<=w_end order by gid asc) t1) as w_len,
        ((w_end-w_start+1)*". $this->params["TRACKER_GPS_PERIOD"]. ") as w_time,
        (select max(vit) as w_len from $this->tablename_position where gid>=w_start and gid<=w_end) as w_max_vit,
        (select avg(vit) as w_len from $this->tablename_position where gid>=w_start and gid<=w_end) as w_moy_vit,
        (select median(vit) as w_len from $this->tablename_position where gid>=w_start and gid<=w_end) as w_med_vit,
        w_start,
        w_end,
        (select count(*) from $this->tablename_position where gid>=w_start and gid<=w_end and cap=0) as w_cap_0,".
        ($doStart ?
        "coalesce((select st_distance((select st_transform(st_centroid(st_collect(the_geom)),2154)  as center from $this->tablename_position where gid>=$track_start and gid<p.gid), st_transform(the_geom, 2154))),0.0) as d_center " 
         : 
        "coalesce((select st_distance((select st_transform(st_centroid(st_collect(the_geom)),2154)  as center from $this->tablename_position where gid<=$track_end and gid>p.gid), st_transform(the_geom, 2154))),0.0) as d_center "   
       ).
      "from $this->tablename_position p
      inner join
      (select 
        t.gid,
        greatest($track_start::int8, t.gid-$w_pos_len) as w_start, least($track_end::int8, t.gid+$w_pos_len) as w_end
      from
        (select * from $this->tablename_position where gid>=$track_start and gid<=$track_end) t
      ) t2
      on p.gid=t2.gid
      ) p_info
      order by gid " . ($doStart ? "asc" : "desc") . " " .
      "limit $nb_pos";
     
    //die($strSql . "\n");
    $posTable = array();
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
      $posTable[] =array();
      foreach($dr as $key => $val) {
        $posTable[count($posTable)-1][$key] = $val;
      }
    }
    
    $i=0;
    $inImmobility = true;
    $iLastImmobility = $i;
    while ($i< count($posTable) && $inImmobility) {
      $posInfo = $posTable[$i];
      
      // eviter l'effet activité lié au demarrage du datalogger
      if (!($doStart && (($i*$this->params["TRACKER_GPS_PERIOD"])<self::$TRACK_EX_FILTER_WINDOW_START_OFFSET))) {
        $inImmobility = 
          (intval($posInfo["w_cap_0"])>=self::$TRACK_EX_MAX_CAP0_PER_WINDOW) &&
          (floatval($posInfo["d_center"])<=self::$TRACK_EX_RADIUS_LIEU) &&
          (floatval($posInfo["w_med_vit"])<=self::$TRACK_EX_MED_VIT_IMMOBILITY);
        if ($inImmobility)
          $iLastImmobility = $i;  
        $this->fileLogger->write("[adjustTrackExtremity] ". intval($posInfo["gid"]) . "  "  . intval($posInfo["w_cap_0"]) . "  (" . self::$TRACK_EX_MAX_CAP0_PER_WINDOW . ") " . floatval($posInfo["d_center"]) . " (" . self::$TRACK_EX_RADIUS_LIEU . ")  " . floatval($posInfo["w_med_vit"]) . "(" . self::$TRACK_EX_MED_VIT_IMMOBILITY  . ")" ) ;
      }
      $i++;
    }
    
    $res = $doStart ? $track_start : $track_end;
    if (!$inImmobility) {
      $gid = intval($posTable[($iLastImmobility)]["gid"]);
      $res = $doStart ?
          max($gid - $w_pos_len, $gid) :
          min($gid + $w_pos_len, $gid);
    }
    
    return $res;
  }
  
  
  /**
   * get ttf associated with the pos, return 0 if no ttf associated
   * @return int ttf value in s.
   */
  protected function getTTF($pos_start) {
    // getting ttf 
    $strSql = "SELECT 
                coalesce(substring(msg_info from '.*ttf([0-9]+).*'),'0')::int as ttf 
               FROM $this->tablename_position 
               WHERE 
                msg_info ilike'%ttf%'
               AND gid = $pos_start
                ";
    $ttf = intval($this->dbConn->getScalarSql($strSql,0)) * $this->params["TRACKER_GPS_PERIOD"];

    return $ttf;
  }
  
  
  /**
   * correct 
   * @param $trackInfo info about track
   */
  
  
  protected function correctTTF($trackInfo) {
    $pos_start = $trackInfo['pos_s'];
    $ttf = $this->getTTF($pos_start);
    //echo "Correcting track...[ttf : $ttf]\n";
    if ($ttf>0) {
      
      // prevTrackEnd : pas bonne stratégie à cause du filtrage de trajet immobile amont...
      /*
      $prev_track_end = $this->getPrevTrackEnd($pos_start);
      if ($prev_track_end==-1)
        return $trackInfo; // pas de trace précédente, on ne peut pas corriger
      */
      
      $prevPos = $pos_start -1;
      if ($prevPos==-1)
        return $trackInfo; // pas de trace précédente, on ne peut pas corriger
      
      // correcting start time
      $segInfo = $this->getSegInfo($pos_start);
      $distTtf = ($segInfo['speed']/3.6)*$ttf; // in m // not used
      $distMin = self::$TTF_MIN_CORRECT_DIST;
      
      $trackInfo['time_s']= $this->subFromTime($trackInfo['time_s'], $ttf);
      $trackInfo['track_duration'] = $trackInfo['track_duration'] + $ttf;
      
      $trackInfo['comments'] = $trackInfo['comments'] . "correction ttf horaire (-$ttf s);";
      if ($segInfo['length']>$distMin) {
        //$trackInfo['pos_s'] = $prev_track_end;
        $trackInfo['pos_s'] = $prevPos;
        $trackInfo['track_length'] = $trackInfo['track_length'] + $segInfo['length'];
        $trackInfo['comments'] = $trackInfo['comments'] . "correction ttf position;";
      }
    }
    return $trackInfo;
    
    
  }
  
  
  
  
  protected function getPrevTrackEnd($pos_start) {
    $strSql = "select pos_end from $this->tablename_track order by gid desc limit 1";
    $pos_end = intval($this->dbConn->getScalarSql($strSql,-1));
    return $pos_end;
  }
  
  protected function getSegInfo($pos) {
    $res = array();
    $strSql = "select seg_length, seg_duration, vit from $this->tablename_position where gid=$pos";
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
      $res["length"] = $dr->getValueName("seg_length");
      $res["duration"] = $dr->getValueName("seg_duration");
      $res["speed"] = $dr->getValueName("vit");
    }
    return $res;
  }
  
  
  protected function subFromTime($strTime, $seconds) {
    $d = DateTime::createFromFormat ( 'Y-m-d H:i:s' ,$strTime);
    $d->sub(new DateInterval("PT".$seconds."S"));
    return $d->format('Y-m-d H:i:s');
  }
  
  // misc utility functions
  protected static function duration($strDateTime1, $strDateTime2) {
    $d1 = DateTime::createFromFormat('Y-m-d H:i:s', $strDateTime1);
    $d2 = DateTime::createFromFormat('Y-m-d H:i:s', $strDateTime2);
    $duration = $d2->getTimestamp() - $d1->getTimestamp();
    return $duration;
  }
  
  protected function execSqlCommands($commands) {
    $res = true;
    $this->dbConn->initTransaction();
    $i=0;
    while ($res && $i<count($commands)) {
      $res = $this->dbConn->executeSql($commands[$i]);
      $i++;
    }
    if ($res)
      $this->dbConn->commitTransaction();
    else
      $this->dbConn->roolbackTransaction();
    return $res;
  }
  
}
?>
