<?php
/*licence/ 

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Class::Pattern
Module fournissant les classes de base Alkanet.
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

/**
 * @package alkanet_classes_pattern
 * @class AlkErrorLog
 * 
 * @brief classe qui prend en charge le gestionnaire d'erreur et d'arret
 */
class AlkErrorLog
{
  const ERROR_HANDLER       =    1; // activer la capture d'erreur
  const EXCEPTION_HANDLER   =    2; // activer la capture d'exception non attrapée
  const REGISTER_SHUTDOWN   =    4; // activer la capture de l'événement shutdown
  const LOG_FILE            =    8; // activer l'écritre de log en fichier
  const DISPLAY_ERROR_ON    =   16; // activer l'affichage à l'écran des LOG_NOTICE, LOG_WARNING et LOG_ERROR, prioritaire sur LOG_FILE
  const DISPLAY_CONTEXT_ON  =   32; // activer l'affichage de log de type context, prioritaire sur LOG_FILE
  const LOG_CONTEXT         =   64; // activer la capture de log de type context, avec LOG_FILE ou DISPLAY_CONTEXT_ON
  const LOG_NOTICE          =  128; // activer la capture de log de type notice
  const LOG_WARNING         =  256; // activer la capture de log de type warning
  const LOG_ERROR           =  512; // activer la capture de log de type error
  const ACTION_LEVEL_ALL    = 1023; // = tous
  
  const ACTION_LEVEL_PROD   =  911; // = ALL - DISPLAY_ERROR_ON - DISPLAY_CONTEXT_ON - LOG_CONTEXT
  const ACTION_LEVEL_QUALIF =  987; // = ALL - REGISTER_SHUTDOWN - DISPLAY_CONTEXT_ON
  const ACTION_LEVEL_DEBUG  = 1019; // = ALL - REGISTER_SHUTDOWN 
  
  /** combinaison des constantes ci-dessus correspondant aux actions activées ou non */
  protected static $actionLevel = 0;
  
  /** chemin complet de base pour les fichiers de log */
  protected static $pathLog = "";
  
  /** temps mémorisé d'un début d'action pour chronométrage */
  protected static $startContext = array("time" => 0, "class" => "", "method" => "", "filename" => "", "line" => 0);
  
  /** active ou non la levée d'exception en cas d'erreur, désactivée par défaut */
  protected static $throwsException = false;

  /** Mémorise la dernière exception */
  protected static $lastException = null;
  
  /**
   * Démarre la prise en charge :
   * - du gestionnaire d'erreur 
   * - du gestionnaire de levée d'exception
   * - du gestionnaire d'arrêt du script
   * @param actionLevel   niveau de gestion d'erreur : champ de bits obtenu à partir des constantes de cette classe
   * @param pathLog       chemin complet du répertoire où seront écrits les fichiers de log (utile uniquement si LOG_FILE est actif)
   */
  public static function startErrorHandler($actionLevel=0, $pathLog="")
  {  
    // interdit la déconnexion
    //ignore_user_abort(true);
    
    self::$actionLevel = $actionLevel;
  
    // gestion personnalisée des erreurs détectées
    if( ($actionLevel & self::ERROR_HANDLER) == self::ERROR_HANDLER ) {
      set_error_handler("AlkErrorLog::errorHandler", E_ALL | E_STRICT);
    }
  
    // gestion personnalisée par défaut des levées d'exception non attrappées
    if( ($actionLevel & self::EXCEPTION_HANDLER) == self::EXCEPTION_HANDLER ) {
      set_exception_handler('AlkErrorLog::exceptionHandler' );
    }
    
    // gestion personnalisée des erreurs fatales : timeout / aborted
    if( ($actionLevel & self::REGISTER_SHUTDOWN) == self::REGISTER_SHUTDOWN ) {
      register_shutdown_function('AlkErrorLog::registerShutdown');
    }
    
    if( ($actionLevel & self::LOG_FILE) == self::LOG_FILE ) {
      self::$pathLog = ( $pathLog != ""
                         ? $pathLog
                         : ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD."log/" );
    }
    
    ini_set("display_errors", ( ($actionLevel & self::DISPLAY_ERROR_ON) == self::DISPLAY_ERROR_ON ? "on" : "off") );
    error_reporting( E_ALL );
  }
  
  /**
   * Mémorise un context qui peut être utilisé pour un ultérieur qui ne possède pas ces informations
   * @param string 
   */
  public static function startContext($class=__CLASS__, $method=__METHOD__, $filename=__FILE__, $line=__LINE__)
  {
    self::$startContext = array("time"     => microtime(true), 
                                "class"    => $class, 
                                "method"   => $method, 
                                "filename" => $filename, 
                                "line"     => $line);
  }
  
  /**
   * Clos le contexte démarré, et enregistre le log si le mode info est actif
   * @param string logstr  message associé à ce contexte qui sera inscrit dans le log
   */
  public static function endContext($logstr, $logcategory="Alkanet")
  {
    if( self::$startContext["time"]>0 ) {
      if( (self::$actionLevel & self::LOG_CONTEXT) == self::LOG_CONTEXT ) {
        self::addLog(new AlkException($logstr, self::$startContext["class"], self::$startContext["method"], 
                                      0, 0, self::$startContext["filename"], self::$startContext["line"], $logcategory));
      }      
    }
    self::$startContext = array("time" => 0, "class" => "", "method" => "", "filename" => "", "line" => 0);
  }
  
  /**
   * Retourne le temps de démarrage mémorisé
   * @return int
   */
  public static function getStartTime()
  {
    return self::$startContext["time"];
  }
  
  /**
   * Retourne le temps de démarrage mémorisé
   * @return int
   */
  public static function getLastException()
  {
    return self::$lastException;
  }
  
  /**
   * Active la levée des exceptions
   */
  public static function enableException()
  {   
    self::$throwsException = true;
  }
  
  /**
   * Désactive la levée des exceptions
   */
  public static function disableException()
  {   
    self::$throwsException = false;
  }
  
  /**
   * Désactive la levée des exceptions
   */
  public static function isExceptionEnable()
  {   
    return self::$throwsException;
  }
  
  /**
   * Prend en compte l'exception en fonction du paramétrage de cette classe
   * @param AlkException $exception  exception levée
   * @throws AlkException si self::$throwsException est actif et si l'exception ne provient pas du errorHandler : caractérisé par code error=code sévérité
   */
  public static function logException(AlkException $exception)
  {
    self::$lastException = $exception;
    self::addLog($exception);
    $bErr = ( $exception->getCode() == $exception->getSeverity() ? true : false );
    if( self::$throwsException && !$bErr ) {
      $exception->mark();
      throw $exception;
    }
  }

  /**
   * Retourne le message de l'exception en fonction mode de sortie
   * @param AlkException $exception  exception levée
   * @param unknown_type $screenMode =true pour le mode écran, =false pour le mode fichier (par défaut)
   * @return string
   */
  protected static function getExceptionMsg(AlkException $exception, $screenMode=false)
  {
    $exception->setScreenLog($screenMode);
    $strMsg   = (string)$exception;
    $diff = ( self::$startContext["time"]>0 ? microtime(true)-self::$startContext["time"] : 0 );
    $strMsg = str_replace("#duration#", $diff, $strMsg);
    return $strMsg; 
  }
  
  /**
   * Reporte le log vers un fichier
   * @param AlkException $exception   exception levée à logger
   */
  protected static function addLog(AlkException $exception)
  {
    $category = $exception->getCategory();
    
    $s = $exception->getSeverity();
    $c = $exception->getCode();
    $bContext = ( $s == 0 && $c == 0   ? true : false );
    $bNotice  = ( $s == E_USER_NOTICE  ? true : false );
    $bWarning = ( $s == E_USER_WARNING ? true : false );
    $bError   = ( $s != E_USER_NOTICE && $s != E_USER_WARNING ? true : false );
    
    if( $bContext && (self::$actionLevel & self::LOG_CONTEXT) == self::LOG_CONTEXT ) {
      if( (self::$actionLevel & self::DISPLAY_CONTEXT_ON) == self::DISPLAY_CONTEXT_ON ) {
        // log écran
        echo self::getExceptionMsg($exception, true);
      }
      elseif( (self::$actionLevel & self::LOG_FILE) == self::LOG_FILE ) {
        // log fichier interne
        error_log(self::getExceptionMsg($exception), 3, self::$pathLog.$category);
      }
      else {
        // non enregistré
      }
    }
    elseif( ($bNotice  && (self::$actionLevel & self::LOG_NOTICE)  == self::LOG_NOTICE ) || 
            ($bWarning && (self::$actionLevel & self::LOG_WARNING) == self::LOG_WARNING) || 
            ($bError   && (self::$actionLevel & self::LOG_ERROR)   == self::LOG_ERROR  ) ) {
      if( (self::$actionLevel & self::DISPLAY_ERROR_ON) == self::DISPLAY_ERROR_ON ) {
        // log écran
        echo self::getExceptionMsg($exception, true);
      } 
      elseif( (self::$actionLevel & self::LOG_FILE) == self::LOG_FILE ) {
        // log fichier interne
        error_log(self::getExceptionMsg($exception), 3, self::$pathLog.$category);
      }
      else {
        // log par défaut
        error_log(self::getExceptionMsg($exception), 0);
      }
    }
  }

  /**
   * Capture les erreurs utilisateurs
   * Transforme l'erreur en une AlkException
   * @reurn bool
   */
  public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
  {
    self::logException(new AlkException($errstr, __CLASS__, __METHOD__, $errno, $errno, $errfile, $errline));
    // ne pas exécuter le gestionnaire d'erreur par défaut de php
    return false;
    // retourner true sinon.
  }
  
  /**
   * Capture les exceptions non attrapées 
   * et inscription dans le log si l'exception est une AlkException
   */
  public static function exceptionHandler(Exception $exception)
  {
    if( $exception instanceof AlkException && !$exception->isMark() ) {
      self::logException($exception);
    }
  }
  
  /**
   * Gestionnaire d'arrêt de script
   */
  public static function registerShutdown()
  {
    $iStatus = connection_status();
    //echo "shutdown $iStatus<br>";
    $strMsg = '';
    switch( $iStatus ) {
      case CONNECTION_TIMEOUT:
        $strMsg = 'Maximum execution time of '.ini_get('max_execution_time').' seconds exceeded.';
        break;
      
    case CONNECTION_ABORTED:
      $strMsg = 'Script aborted.';
      break;
      
    default:
      $strLimit = ini_get("memory_limit");
      if( strpos($strLimit, "M")===false ) {
        $iLimit = $strLimit*1;
        $strLimit .= "B";
      } else {
        $iLimit = trim(str_replace("M", "", $strLimit))*1024*1024;
      }
      if( memory_get_peak_usage(true) >= $iLimit ) {
        $strMsg = 'Out of memory. Unable to allocate more than '.$strLimit.'.';
      }
      break;
    }
    
    if( $iStatus != CONNECTION_NORMAL || $strMsg != '' ) {
      if( self::$startContext["time"] != 0 ) {
        throw new AlkException($strMsg, self::$startContext["class"], self::$startContext["method"], 
                               0, E_USER_ERROR, self::$startContext["filename"], self::$startContext["line"]);
      } else {
        throw new AlkException($strMsg);
      }
    }
    exit();
  }
}



