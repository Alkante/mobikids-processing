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
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkobject.class.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkrequest.class.php");

/**
 * @package Alkanet_Class_Pattern
 * 
 * @class AlkFactory
 * @brief Classe factory et singleton qui centralise la création
 *        et la mémorisation des instances hérités de AlkQuery
 */
final class AlkFactory extends AlkObject
{
  /** Référence sur l'objet de connexion aux bases : possibilité d'avoir 1 connexion par type de SGBD */
  private static $dbConn = array(ALK_SGBD_ORACLE   => array("default" => null), 
                                 ALK_SGBD_MYSQL    => array("default" => null), 
                                 ALK_SGBD_POSTGRES => array("default" => null));

  private static $dbPDO = array();
  
  /** Référence sur l'objet de connexion au serveur LDAP */
  private static $ldapConn = null;

  /** Référence sur l'objet oSpace utilisé */
  private static $oSpace = null;

  /** Référence sur l'objet template utilisé */
  private static $oTemplate = null;

  /** Référence sur l'objet WordIndexer utilisé */
  private static $oWordIndexer = null;

  /** Référence sur l'objet WordIndexer utilisé */
  private static $oMnogoSearch = null;

  /** tableau contenant les références des objets de type query instanciés */
  private static $tabQuery = array();

  /** tableau contenant les références des objets de type queryAction instanciés */
  private static $tabQueryAction = array();

  /** tableau contenant les références des objets de type queryAction instanciés */
  private static $tabQuerySpatial = array();

  /** tableau contenant les références des objets de type AlkAppli instanciées */
  private static $tabAppli = array();
  
  /** tableau contenant les références des objets de type AlkApi instanciées */
  private static $tabApi = array();

  /** tableau contenant les références des objets de type AlkAppliEspace instanciées */
  private static $tabSpace = array();

  /** tableau contenant les valeurs de variables globales à l'application */
  private static $tabProperty = array();

  /** référence vers memcache */
  private static $oMemCache = null;
  
  /** référence vers solr Client */
  private static $oAlkSolrClient = null;
 
  /** objet pCache **/
  private static $oPcache;
  
  /** référence sur le type de navigateur, famille de navigateur */
  private static $strNav    = null;
  
  /** renvoie une instance d'envoi de sms **/
  private static $oSms;
  
  /** renvoie une instance d'envoi de sms ovh **/
  private static $oSmsOvh;  
  
  private static $tabSQLParams = array();
  
  /**
   *  Constructeur par défaut
   */
  public function __construct() { }

  /**
   * Retourne le timestamp unix correspondant à la commande time() php avec prise en compte
   * du décalage horaire entre le navigateur et le serveur
   * 
   * @return int
   */
  public static function getLocalDate($iTime=-1)
  {
    if( $iTime == -1 ) {
      $iTime = time();
    }
    $iDeltaGMTServ = ( isset(self::$tabProperty["serv_deltaGMT"]) ? self::$tabProperty["serv_deltaGMT"] : -date("Z", time())/3600 );   
    $iDeltaGMT = ( isset(self::$tabProperty["user_deltaGMT"]) ? self::$tabProperty["user_deltaGMT"] : $iDeltaGMTServ );
    return strtotime(($iDeltaGMTServ-$iDeltaGMT)." hour", $iTime);
  }

  /**
   * Charge toutes les constantes nécessaires à Alkanet
   * Les paramètres permettent de charger un paramètrage spécifique à l'espace ou l'application
   * @param tabCont   liste des identifiants de l'espace
   * @param tabAppli  liste des identifiants de l'application
   * @param tabAtype  liste des atype id
   */
  public static function loadConstants($tabCont=array(), $tabAppli=array(), $tabAtype=array())
  {
    $oQuery = AlkFactory::getQuery(ALK_ATYPE_ID_ESPACE);
    $dsConst = $oQuery->getDsSqlConstant($tabCont, $tabAppli, $tabAtype);
    while( $drConst = $dsConst->fetch() ) {
      $const_intitule   = $drConst["CONST_INTITULE"];
      $const_valeur     = $drConst["CONST_VALEUR"];
      $const_type       = $drConst["CONST_TYPE"];
        
      if( defined($const_intitule) ) {
        continue;
      }
      
      $strVal = "";
      $bEval = false;
      switch( $const_type ) {
      case "0": // string
      case "2": // date
      	//On regarde si la structure correspond a du json. Dans ce cas il s'agit d'une variable multilingue
      	if( is_string($const_valeur) && (is_object(json_decode($const_valeur)) || is_array(json_decode($const_valeur))) ) {
      		$json_const_valeur = json_decode($const_valeur);
      		$strVal = "\"".($json_const_valeur->lg->$_SESSION["ALK_LG_LOCALE_INTERFACE_FRONT"])."\"";
      	} else {
	        $strVal = "\"".$const_valeur."\"";
        }
        break;
    
      case "1": // int
      case "3": // bool
        $strVal = $const_valeur;
        break;
    
      case "4": // expression php
        $strVal = $const_valeur;
        $bEval = true;
        break;
      }
    
      eval("define(\$const_intitule, ".$strVal.");");  
    }
    
  }

  /**
   * Retourne une référence sur l'objet de connexion correspondant à la configuration passée en paramètre
   *        Si celle-ci n'existe pas, elle est créée
   * @param string  $confName       identifiant correspondant à la configuration de la connexion
   * @param array   $driverConf     configuration de la connexion
   */
  public static function getDbPDO($confName="default", $driverConf=array())
  {
    if( array_key_exists($confName, self::$dbPDO) ) {
      return self::$dbPDO[$confName];
    }
    
    if( empty($driverConf) ) {
      if( defined("ALK_PDO_DRIVERS_CONF") ) {
        $driverConfs = json_decode(ALK_PDO_DRIVERS_CONF, true);
        if( array_key_exists($confName, $driverConfs) ) {
          $driverConf = $driverConfs[$confName];
        } else {
          return self::$oNull;
        }
      } else {
        return self::$oNull;
      }
    }

    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDbPDO.php");

    self::$dbPDO[$confName] = new AlkDbPDO($driverConf);
    self::$dbPDO[$confName]->initAttributes();
    $iDeltaGMT     = ( isset(self::$tabProperty["user_deltaGMT"]) ? self::$tabProperty["user_deltaGMT"] : "-1" );
    $iDeltaGMTServ = ( isset(self::$tabProperty["serv_deltaGMT"]) ? self::$tabProperty["serv_deltaGMT"] : "-1" );
    self::$dbPDO[$confName]->setDeltaGMT($iDeltaGMT, $iDeltaGMTServ);
    
    return self::$dbPDO[$confName];
  }
  
  /**
   *  Retourne une référence sur l'objet de connexion par défaut
   *        Si celle-ci n'existe pas, elle est créée
   * @param typeBD  Type de serveur sgbd utilisé
   * @param bAutoConnect true par défaut pour ouvrir la connexion, faux pour ne pas ouvrir la connexion
   * @deprecated since version 3.6 utiliser getDbPDO()
   * @return AlkDb
   */
  public static function getDbConn($typeBD=ALK_BDD_TYPE, $bAutoConnect=true, $strAlkSGBDEncoding=ALK_SGBD_ENCODING)
  {
    if( defined("ALK_PDO_DRIVERS_CONF") ) {
      return self::getDbPDO();
    }
    
    if( !is_null(self::$dbConn[$typeBD]["default"]) ) {
      return self::$dbConn[$typeBD]["default"];
    }

    switch( $typeBD ) {
    case ALK_SGBD_ORACLE:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdroracle.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdsoracle.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdboracle.class.php");

      self::$dbConn[$typeBD]["default"] = new AlkDbOracle(ALK_ORA_LOGIN, ALK_ORA_PWD, ALK_ORA_SID);
      break;

    case ALK_SGBD_MYSQL:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdrmysql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdsmysql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdbmysql.class.php");
      
      self::$dbConn[$typeBD]["default"] = new AlkDbMySql(ALK_MYSQL_LOGIN,  ALK_MYSQL_HOST,  ALK_MYSQL_PWD,  ALK_MYSQL_BD,  ALK_MYSQL_PORT);
      break;
      
    case ALK_SGBD_POSTGRES:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdrpgsql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdspgsql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdbpgsql.class.php");
      
      self::$dbConn[$typeBD]["default"] = new AlkDbPgSql(ALK_POSTGRES_LOGIN,  ALK_POSTGRES_HOST,  ALK_POSTGRES_PWD,  ALK_POSTGRES_BD,  ALK_POSTGRES_PORT);
      break;
    }
    
    if( !is_null(self::$dbConn[$typeBD]["default"]) ) {
      if( $bAutoConnect ) {
        self::$dbConn[$typeBD]["default"]->connect($strAlkSGBDEncoding);
      }
      $iDeltaGMT = ( isset(self::$tabProperty["user_deltaGMT"]) ? self::$tabProperty["user_deltaGMT"] : "-1" );
      $iDeltaGMTServ = ( isset(self::$tabProperty["serv_deltaGMT"]) ? self::$tabProperty["serv_deltaGMT"] : -1 );
      self::$dbConn[$typeBD]["default"]->setDeltaGMT($iDeltaGMT, $iDeltaGMTServ);
    } else {
      self::$dbConn[$typeBD]["default"] = self::$oNull;
    }
    return self::$dbConn[$typeBD]["default"];
  }
  
  /**
   *  Retourne une référence sur l'objet de connexion spécifiée par les paramètres
   *  Si celle-ci n'existe pas, elle est créée.
   *  Si la connexion recherchée existe, elle est retournée. C'est pourquoi les paramètres de connexion sont optionnels
   * @param idConn      identifiant de la connexion utilisée
   * @param strhost     adresse du host, non utilisé pour oracle
   * @param strPort     numéro de port, non utilisé pour oracle
   * @param strBaseName nom de la base ou SID pour oracle
   * @param strLogin    identifiant de connexion
   * @param strPwd      mot de passe de connexion
   * @param typeBD      type de serveur sgbd
   * @return AlkDb
   */
  public static function getDbConnByParam($idConn, $strhost="", $strPort="", $strBaseName="", $strLogin="", 
                                          $strPwd="", $typeBD=ALK_BDD_TYPE, $strAlkSGBDEncoding=ALK_SGBD_ENCODING)
  {
    if( defined("ALK_PDO_DRIVERS_CONF") ) {
      trigger_error("Vous devez utiliser getDbPDO().", E_USER_DEPRECATED);
      exit();
    }
    
    if( array_key_exists("_".$idConn, self::$dbConn[$typeBD]) && 
        !is_null(self::$dbConn[$typeBD]["_".$idConn]) ) {
      return self::$dbConn[$typeBD]["_".$idConn];
    }

    switch( $typeBD ) {
    case ALK_SGBD_ORACLE:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdroracle.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdsoracle.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdboracle.class.php");

      self::$dbConn[$typeBD]["_".$idConn] = new AlkDbOracle($strLogin, $strPwd, $strBaseName);
      break;

    case ALK_SGBD_MYSQL:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdrmysql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdsmysql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdbmysql.class.php");
      
      self::$dbConn[$typeBD]["_".$idConn] = new AlkDbMySql($strLogin,  $strhost,  $strPwd,  $strBaseName, $strPort);
      break;
      
    case ALK_SGBD_POSTGRES:
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdrpgsql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdspgsql.class.php");
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdbpgsql.class.php");
      
      self::$dbConn[$typeBD]["_".$idConn] = new AlkDbPgSql($strLogin,  $strhost,  $strPwd,  $strBaseName, $strPort);
      break;
    }
    
    if( !is_null(self::$dbConn[$typeBD]["_".$idConn]) ) {
      self::$dbConn[$typeBD]["_".$idConn]->connect($strAlkSGBDEncoding);
      $iDeltaGMT = ( isset(self::$tabProperty["user_deltaGMT"]) ? self::$tabProperty["user_deltaGMT"] : "-1" );
      $iDeltaGMTServ = ( isset(self::$tabProperty["serv_deltaGMT"]) ? self::$tabProperty["serv_deltaGMT"] : -1 );
      self::$dbConn[$typeBD]["_".$idConn]->setDeltaGMT($iDeltaGMT, $iDeltaGMTServ);
    } else {
      self::$dbConn[$typeBD]["_".$idConn] = self::$oNull;
    }
    return self::$dbConn[$typeBD]["_".$idConn];
  }
  
  /**
   *  Retourne une référence sur l'objet de connexion
   *        Si celle-ci n'existe pas, elle est créée
   * @return AlkLDAP
   */
  public static function &getLdapConn()
  {
    if( !is_null(self::$ldapConn) ) {
      return self::$ldapConn;
    }

    if( ALK_LDAP ) {
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkdbldap.class.php");
      
      self::$ldapConn = new AlkDbLDAP(ALK_LDAP_HOST, ALK_LDAP_PORT, ALK_LDAP_BASE_DN, ALK_LDAP_USER, ALK_LDAP_PWD, ALK_LDAP_ACCOUNT_SUFFIX);
      
      if( !is_null(self::$ldapConn ) ) {
        $bRes = self::$ldapConn->connect();
        if( !$bRes ) {
          self::$ldapConn = self::$oNull; 
        }
      } else {
        self::$ldapConn = self::$oNull;
      }
    }
    return self::$ldapConn;
  }
  
  /**
   * Retourne une référence sur un query typé d'une application
   * @param string    $queryType prend la valeur "", "action" ou "spatial"
   * @param int       $atype_id  Identifiant du type applicatif
   * @param AlkDbPDO  $dbPDO     null par défaut pour l'objet de connexion par défaut ou objet de connexion spécifique
   * @return AlkQuery 
   */
  protected static function &getQueryByName($queryType, $atype_id, AlkDbPDO $dbPDO=null)
  {
    if( !self::isAppliTypeAvailable($atype_id) ) {
      return self::$oNull;
    }
    if ( strpos($queryType, "_")===0 ) {
      $queryType = substr($queryType, 1);
    }
    
    $clsName = ucfirst(strtolower($queryType));
    $fileName = ($queryType!="" ? "_".strtolower($queryType) : $queryType);
    
    $strClassName   = constant("ALK_ATYPE_ABREV_".$atype_id);
    $strDirectory = mb_strtolower($strClassName);
    $strAppliAbbrev = mb_strtolower($strClassName).$fileName;
    
    $strClassName  .= $clsName;
    
    if( !array_key_exists($strAppliAbbrev, self::$tabQuery) ) {
      if(file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkquery".$strAppliAbbrev.".class.php") && 
        is_file(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkquery".$strAppliAbbrev.".class.php") ) {
         $strClassName.="_2";
         require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkquery".$strAppliAbbrev.".class.php");
      } else {
        require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE.$strDirectory."/classes/alkquery".$strAppliAbbrev.".class.php");
      }
      if( is_null($dbPDO) ) {
        $dbPDO = self::getDbConn();
      }
      $className = "AlkQuery".$strClassName;
      self::$tabQuery[$strAppliAbbrev] = new $className($dbPDO);
    }

    self::$tabQuery[$strAppliAbbrev]->setLGTabLangue();
    return self::$tabQuery[$strAppliAbbrev];
  }
  
  /**
   * Retourne une référence sur un query d'une application
   * Est appelé par le constructeur de AlkAppli
   * @param int       $atype_id  Identifiant du type applicatif
   * @param AlkDbPDO  $dbPDO     null par défaut pour l'objet de connexion par défaut ou objet de connexion spécifique
   * @return AlkQuery 
   */
  public static function &getQuery($atype_id, AlkDbPDO $dbPDO=null)
  {
    return self::getQueryByName("", $atype_id, $dbPDO);
  }

  /**
   * Retourne une référence sur un queryAction d'une application
   * Est appelé par le constructeur de AlkAppli
   * @param int       $atype_id  Identifiant du type applicatif
   * @param AlkDbPDO  $dbPDO     null par défaut pour l'objet de connexion par défaut ou objet de connexion spécifique
   * @return AlkQuery 
   */
  public static function &getQueryAction($atype_id, AlkDbPDO $dbPDO=null)
  {
    return self::getQueryByName("action", $atype_id, $dbPDO);
  }

  /**
   * Retourne une référence sur un querySpatial d'une application
   * Est appelé par le constructeur de AlkAppli
   * @param int       $atype_id  Identifiant du type applicatif
   * @param AlkDbPDO  $dbPDO     null par défaut pour l'objet de connexion par défaut ou objet de connexion spécifique
   * @return AlkQuery 
   */
  public static function &getQuerySpatial($atype_id, AlkDbPDO $dbPDO=null)
  {
    return self::getQueryByName("spatial", $atype_id, $dbPDO);
  }

  /**
   *  Retourne une référence sur un objet de type AlkAppli
   * @param oSpace    Référence sur l'objet oSpace
   * @param atype_id  Identifiant du type applicatif
   * @param appli_id  Identifiant de l'application, -1 si non connu
   * @return AlkAppli 
   */
  public static function &getAppli($atype_id, $appli_id=-1)
  {
    if( !self::isAppliTypeAvailable($atype_id) )
      return self::$oNull;
    
    $strClassName   = constant("ALK_ATYPE_ABREV_".$atype_id); 
    $strAppliAbbrev = mb_strtolower($strClassName);
    $strAppliIndex = $strAppliAbbrev."_".$appli_id;
    if( !array_key_exists($strAppliIndex, self::$tabAppli) ) {
      if(file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkappli".$strAppliAbbrev.".class.php") && 
         is_file(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkappli".$strAppliAbbrev.".class.php") ) {
         $strClassName.="_2";
         require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkappli".$strAppliAbbrev.".class.php");
      }else {
         require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."".$strAppliAbbrev."/classes/alkappli".$strAppliAbbrev.".class.php");
      }
      $className = "AlkAppli".$strClassName;
      self::$tabAppli[$strAppliIndex] = new $className($appli_id);
    }
    
    self::$tabAppli[$strAppliIndex]->setLGTabLangue();
    return self::$tabAppli[$strAppliIndex];
  }

  /**
   *  Retourne une référence sur un objet de type AlkAppli
   * @param oSpace    Référence sur l'objet oSpace
   * @param atype_id  Identifiant du type applicatif
   * @param appli_id  Identifiant de l'application, -1 si non connu
   * @return AlkAppli 
   */
  public static function &getApi($atype_id, $appli_id=-1)
  {
    if( !self::isAppliTypeAvailable($atype_id) )
      return self::$oNull;
    
    $strClassName   = constant("ALK_ATYPE_ABREV_".$atype_id); 
    $strAppliAbbrev = mb_strtolower($strClassName);
    $strApiIndex = $strAppliAbbrev."_".$appli_id;
    if( !array_key_exists($strApiIndex, self::$tabApi) ) {
      if(file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkapi".$strAppliAbbrev.".class.php") && 
         is_file(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkapi".$strAppliAbbrev.".class.php") ) {
         $strClassName.="_2";
         require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."classes/alkapi".$strAppliAbbrev.".class.php");
      }elseif(file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE.$strAppliAbbrev."/classes/alkapi".$strAppliAbbrev.".class.php") && 
         is_file(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE.$strAppliAbbrev."/classes/alkapi".$strAppliAbbrev.".class.php") ) {
         require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."".$strAppliAbbrev."/classes/alkapi".$strAppliAbbrev.".class.php");
      }else{
        return self::$oNull;
      }
      
      $oAppliApi = self::getAppli($atype_id, $appli_id);
      
      $className = "AlkApi".$strClassName;
      self::$tabApi[$strApiIndex] = new $className($oAppliApi);
    }
    
    self::$tabApi[$strApiIndex]->setLGTabLangue();
    return self::$tabApi[$strApiIndex];
  }

  /**
   *  Retourne une référence sur un objet de type AlkAppliEspace
   * @param cont_id  Identifiant de l'espace, -1 si non connu
   * @return AlkAppliEspace
   */
  public static function &getSpace($cont_id=-1)
  {
  	if( !self::isAppliTypeAvailable(ALK_ATYPE_ID_ESPACE) )
      return self::$oNull;
  	
    if( !is_null(self::$oSpace) && $cont_id==-1 ) {
      return self::$oSpace;
    }

    $strClassName   = "Espace";
    $strAppliAbbrev = mb_strtolower($strClassName);
    $index = $strAppliAbbrev.$cont_id;
    if( !array_key_exists($index, self::$tabSpace) ) {
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."".$strAppliAbbrev."/classes/alkappli".$strAppliAbbrev.".class.php");
      $className = "AlkAppli".$strClassName;
      self::$tabSpace[$index] = new $className($cont_id);

      if( is_null(self::$oSpace) ) {
        self::$oSpace = self::$tabSpace[$index];
      }
    }
    self::$tabSpace[$index]->setLGTabLangue();
    return self::$tabSpace[$index];
  }

  /**
   * retourn true si l'application de type atype_id est instanciable
   * @param atype_id  identifiant du type d'application
   * @return bool
   */
  private static function isAppliTypeAvailable($atype_id)
  {
    return 
      ( defined("ALK_ATYPE_ABREV_".$atype_id) && 
        constant("ALK_ATYPE_ABREV_".$atype_id)==true && 
        defined("ALK_B_ATYPE_".strtoupper(constant("ALK_ATYPE_ABREV_".$atype_id))) &&
        constant("ALK_B_ATYPE_".strtoupper(constant("ALK_ATYPE_ABREV_".$atype_id))) == true );
  }

  /**
   *  Retourne une référence sur un objet de type AlkAppli
   * @param atype_id  Identifiant du type applicatif
   * @param appli_id  Identifiant de l'application, -1 si non connu
   * @return Smarty 
   */
  public static function &getTemplate()
  {
    if( !is_null(self::$oTemplate) ) {
      return self::$oTemplate;
    }

    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."template/Smarty.class.php");
    self::$oTemplate = new Smarty();
    $pathTplC = ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD.
      ( defined("ALK_UPLOAD_TEMPLATES_C") ? ALK_PATH_TEMPLATES_C : "template_c" );
    if( !(file_exists($pathTplC) && is_dir($pathTplC)) ) {
      $pathTplC = ALK_ALKANET_ROOT_PATH."../".ALK_ROOT_UPLOAD.
        ( defined("ALK_UPLOAD_TEMPLATES_C") ? ALK_PATH_TEMPLATES_C : "template_c" );
      if( !(file_exists($pathTplC) && is_dir($pathTplC)) ) {
        $pathTplC = ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD;
      }  
    }
    self::$oTemplate->compile_dir = $pathTplC;

    return self::$oTemplate;
  }

  /**
   *  Retourne une référence sur un objet de type AlkHtml2Pdf
   * @param strUrlSrc          Url complete du fichier html source
   * @param strPathSrc         Chemin complet du fichier html source
   * @param strFileSrc         Nom du fichier html source
   * @param strHtml            Contenu html
   * @param strPathDest        Chemin complet du fichier pdf générer
   * @param strFileDest        Nom du fichier pdf générer
   * @param bDelFileSrc        Non utilisé
   * @param bUseLocalServ      vrai si utilisation du service local, faux utilisation du service html2pdf.alkante.com (par défaut)
   * @return AlkHtml2Pdf
   */
  public static function getHtml2Pdf($strUrlSrc, $strPathSrc="", $strFilerSrc="", $strHtml="",
                                      $strPathDest="", $strFileDest="", $bDelFileSrc=false, $bUseLocalServ=false)
  {
    include_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."html2pdf/alkhtml2pdf.class.php");
    return new AlkHtml2Pdf($strUrlSrc, $strPathSrc, $strFilerSrc, $strHtml,
                           $strPathDest, $strFileDest, $bDelFileSrc, $bUseLocalServ);
  }

  /**
   *  Retourne une référence sur un objet de type AlkHtml2Pdf
   * @param strUrlSrc          Url complete du fichier html source
   * @param strPathSrc         Chemin complet du fichier html source
   * @param strFileSrc         Nom du fichier html source
   * @param strHtml            Contenu html
   * @param strPathDest        Chemin complet du fichier pdf générer
   * @param strFileDest        Nom du fichier pdf générer
   * @param bDelFileSrc        Non utilisé
   * @param bUseLocalServ      vrai si utilisation du service local, faux utilisation du service html2pdf.alkante.com (par défaut)
   * @return AlkHtml2Pdf
   */
  public static function getAlkExcel()
  {
    include_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkexcel.class.php");
    return new AlkExcel();
  }
  
  /**
   *  Retourne une référence sur un objet de type AlkMail
   * @param strSubject  Sujet du message, vide par défaut
   * @param strBody     Corps du message (html ou texte en fonction contentType), vide par défaut
   * @param strFromName Nom de l'expéditeur, vide par défaut
   * @param strFomMail  Adresse de l'expéditeur, vide par défaut
   * @param strToName   Nom du destinataire, vide par défaut
   * @param strToMail   Adresse du destinataire, vide par défaut
   * @return AlkMail
   */ 
  public static function getMail($strSubject="", $strBody="", $strFromName="", $strFromMail="", $strToName="", $strToMail="")
  {
    include_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkmail.class.php");
    return new AlkMail($strSubject, $strBody, $strFromName, $strFromMail, $strToName, $strToMail);
  }

  /**
   *  Retourne une référence sur un objet de type AlkIptc
   * @return AlkIptc
   */ 
  public static function getIptc()
  {
    include_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkiptc.class.php");
    return new AlkIptc();
  }
  
  /**
   *  Retourne une référence sur un objet de type AlkIcs
   * @return AlkIcs
   */ 
  public static function getIcs()
  {
    include_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkics.class.php");
    return new AlkIcs();
  }

  /**
   *  Enregistre la valeur de la propriété
   * @param strName   Nom de la propriété
   * @param strValue  valeur de la propriété
   */
  public static function setSProperty($strName, $strValue)
  {
    self::$tabProperty[$strName] = $strValue;
  }

  /**
   *  Retourne la valeur de la propriété
   * @param strName          Nom de la propriété
   * @param strDefaultValue  valeur par défaut
   * @return la valeur de la propriété
   */
  public static function getSProperty($strName, $strDefaultValue="")
  {
    return ( array_key_exists($strName, self::$tabProperty) 
             ? self::$tabProperty[$strName] 
             : $strDefaultValue );
  }

  /**
   * Mémorise les informations de l'utilisateur connecté dans ce singleton
   */
  public static function initUserSProperties()
  {
    self::setSProperty("user_id",       $_SESSION["alk_idUser"]);
    self::setSProperty("abonne_id",     $_SESSION["alk_idAbonne"]);
    self::setSProperty("abonne_pseudo", $_SESSION["alk_pseudoAbonne"]);
    self::setSProperty("profil_id",     $_SESSION["alk_idProfil"]);
    self::setSProperty("service_id",    $_SESSION["alk_idService"]);
    self::setSProperty("dept_id",       $_SESSION["alk_idDept"]);
    self::setSProperty("service_name",  $_SESSION["alk_serviceName"]);
    self::setSProperty("service_sigle", $_SESSION["alk_serviceSigle"]);
    self::setSProperty("user_priv",     $_SESSION["alk_userPriv"]);
    self::setSProperty("user_right",    ALK_APPLI_RIGHT_NONE);
    self::setSProperty("user_login",    $_SESSION["alk_userLogin"]);
    self::setSProperty("user_pwd",      $_SESSION["alk_userPwd"]);
    self::setSProperty("user_name",     $_SESSION["alk_userName"]);
    self::setSProperty("user_mail",     $_SESSION["alk_userMail"]);
    self::setSProperty("user_home",     $_SESSION["alk_userHome"]);
    self::setSProperty("user_homeSite", $_SESSION["alk_userHomeSite"]);
    self::setSProperty("perf_id",       $_SESSION["alk_idPerf"]);
    self::setSProperty("user_deltaGMT", ( isset($_SESSION["alk_userDeltaGMT"]) ? $_SESSION["alk_userDeltaGMT"] : -date("Z", time())/3600 ));
    self::setSProperty("serv_deltaGMT", -date("Z", time())/3600);
    self::setSProperty("user_adminAuth", $_SESSION["alk_userAdminAuth"]);
    self::setSProperty("user_lg",       $_SESSION["alk_userLg"]);
    self::setSProperty("userDateConn",  $_SESSION["alk_userDateConn"]);
  }

  /**
   * Initialise une session utilisateur anonyme
   */
  public static function initAnonymousSession()
  {
    $bCanConnectAlkanet = ( defined("ALK_B_USER_INTERNET_CANCONNECT_ALKANET") && ALK_B_USER_INTERNET_CANCONNECT_ALKANET == true );
    
    $_SESSION["alk_idUser"] = ( defined("ALK_USER_ID_INTERNET") ? ALK_USER_ID_INTERNET : 2 );
    $_SESSION["alk_idAbonne"] = -1;
    $_SESSION["alk_pseudoAbonne"] = "";
    $_SESSION["alk_idProfil"] = 1;
    $_SESSION["alk_idService"] = 2;
    $_SESSION["alk_idDept"] = 0;
    $_SESSION["alk_userPriv"] = ALK_PRIV_SPACE_USER;
    $_SESSION["alk_userLogin"] = "";
    $_SESSION["alk_userPwd"] = "";
    $_SESSION["alk_userName"] = _("Utilisateur anonyme");
    $_SESSION["alk_userMail"] = "no-reply@alkante.com";
    $_SESSION["alk_userHome"] = "#";
    $_SESSION["alk_userHomeSite"] = "#";
    $_SESSION["alk_serviceName"] = ""; 
    $_SESSION["alk_serviceSigle"]= "";
    $_SESSION["alk_idPerf"] = "-1";
    $_SESSION["alk_userDeltaGMT"] = -date("Z", time())/3600;
    $_SESSION["alk_userAdminAuth"] = ( $bCanConnectAlkanet ? "1" : "0" );
    $_SESSION["alk_userLg"] = AlkLangHandler::getCurrentLocale(); 
    $_SESSION["alk_userDateConn"] = "";
  }

  /**
   *  Retourne le nom du répertoire de scripts d'un module identifié par son atype_id
   * @param atype_id      Identifiant du type applicatif
   * @param bVerifExists  Vérifie que ALK_B_ATYPE_xxx vaut vrai si vrai
   * @param bSecondAbrev  Utilise l'abréviation ALK_SATYPE_ABREV_ au lieu de ALK_ATYPE_ABREV_ si vrai
   * @return string : le nom du répertoire du module
   */
  public static function getModuleName($atype_id, $bVerifExists=false, $bSecondAbrev=false)
  {
    $strAbrev = ( $bSecondAbrev ? "ALK_SATYPE_ABREV_" : "ALK_ATYPE_ABREV_" );
    if( !defined($strAbrev.$atype_id) )
      return "";
    $strClassName = constant($strAbrev.$atype_id);
    if( $bVerifExists ){
      $strClassNameUp = mb_strtoupper($strClassName);
      if( !(defined("ALK_B_ATYPE_".$strClassNameUp) && constant("ALK_B_ATYPE_".$strClassNameUp)==true) )
        return "";
    }
    $strAppliAbbrev = mb_strtolower($strClassName);
    return $strAppliAbbrev;
  }

  /**
   *  Retourne le chemin menant au répertoire de scripts d'un module identifié par son atype_id
   * @param atype_id  Identifiant du type applicatif
   * @return string : le chemin menant au répertoire du module
   */
  public static function getModulePath($atype_id)
  {
    $strAppliAbbrev = self::getModuleName($atype_id);
    if( $strAppliAbbrev=="" )
      return "";
    return ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."".$strAppliAbbrev."/";
  }

  /**
   *  Retourne l'url menant au répertoire de scripts d'un module identifié par son atype_id
   * @param atype_id  Identifiant du type applicatif
   * @return string : l'url menant au répertoire du module
   */
  public static function getModuleUrl($atype_id)
  {
    $strAppliAbbrev = self::getModuleName($atype_id);
    if( $strAppliAbbrev=="" )
      return "";
    return ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."".$strAppliAbbrev."/";
  }

  /**
   *  Retourne le chemin complet menant au répertoire d'upload d'un module identifié par son atype_id
   *        si bWithoutRootPath=true, retourne uniquement le chemin à partir de upload (sans slash en début).
   * @param atype_id         Identifiant du type applicatif
   * @param bWithoutRootPath =false par défaut, =true pour ne pas retourner le chemin complet
   * @param bMediaSharedOff =false par défaut, =true pour ne pas retourner le dossier partagé
   * @return string : le chemin menant au répertoire d'upload du module
   */
  public static function getUploadPath($atype_id, $bWithoutRootPath=false, $bMediaSharedOff=false)
  {
    if( (strcmp ( constant("ALK_ATYPE_ABREV_".$atype_id) , constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_IEDIT) ) == 0 ) || $atype_id == ALK_ATYPE_ID_GEDIT || $atype_id == ALK_ATYPE_ID_LSDIF){
      if ( (!$bMediaSharedOff) && (defined("ALK_B_MEDIA_SHARED") && ALK_B_MEDIA_SHARED==true) ){
        $atype_id = ALK_ATYPE_ID_ESPACE;
      }
    }
    $strAppliAbbrev = self::getModuleName($atype_id);
    $strAppliAbbrev .= ( $strAppliAbbrev!="" ? "/" : "" );
    return ( $bWithoutRootPath
             ? ALK_ROOT_UPLOAD.$strAppliAbbrev
             : ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD.$strAppliAbbrev );
  }

  /**
   *  Retourne l'url complète menant au répertoire d'upload d'un module identifié par son atype_id
   *        si bWithoutRootUrl=true, retourne uniquement le chemin à partir de upload (sans slash en début).
   * @param atype_id          Identifiant du type applicatif
   * @param bWithoutRootUrl   =false par défaut, =true pour ne pas retourner le chemin complet
   * @param bMediaSharedOff =false par défaut, =true pour ne pas retourner le dossier partagé
   * @return string : l'url menant au répertoire d'upload du module
   */
  public static function getUploadUrl($atype_id, $bWithoutRootUrl=false, $bMediaSharedOff=false)
  {
    if( (strcmp ( constant("ALK_ATYPE_ABREV_".$atype_id) , constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_IEDIT) ) == 0) || $atype_id == ALK_ATYPE_ID_GEDIT || $atype_id == ALK_ATYPE_ID_LSDIF){
      if ( (!$bMediaSharedOff) && (defined("ALK_B_MEDIA_SHARED") && ALK_B_MEDIA_SHARED==true) ){
        $atype_id = ALK_ATYPE_ID_ESPACE;
      }
    }
    $strAppliAbbrev = self::getModuleName($atype_id);
    $strAppliAbbrev .= ( $strAppliAbbrev!="" ? "/" : "" );
    return ( $bWithoutRootUrl
             ? ALK_ROOT_UPLOAD.$strAppliAbbrev
             : ALK_ALKANET_ROOT_URL.ALK_ROOT_UPLOAD.$strAppliAbbrev );
  }

  /**
   *  Retourne vrai si l'utilisateur est animateur de l'espace en cours
   *        Attention, ne pas appeler cette méthode avant l'appel oAppli->verifSecu()
   * @return booleen
   */
  public static function isSpaceAnim()
  {
    $user_priv = ( array_key_exists("user_priv", self::$tabProperty) ? self::$tabProperty["user_priv"] : ALK_PRIV_SPACE_NONE );
    return (( $user_priv & ALK_PRIV_SPACE_ANIM ) == ALK_PRIV_SPACE_ANIM );
  }

  /**
   *  Retourne vrai si l'utilisateur est administrateur annuaire de service
   *        Attention, ne pas appeler cette méthode avant l'appel oAppli->verifSecu()
   * @return booleen
   */
  public static function isAnnuAdminServ()
  {
    $user_priv = ( array_key_exists("user_priv", self::$tabProperty) ? self::$tabProperty["user_priv"] : ALK_PRIV_SPACE_NONE );
    return (( $user_priv & ALK_PRIV_ANNU_SERV ) == ALK_PRIV_ANNU_SERV );
  }

  /**
   *  Retourne vrai si l'utilisateur est administrateur de tout l'annuaire
   *        Attention, ne pas appeler cette méthode avant l'appel oAppli->verifSecu()
   * @return booleen
   */
  public static function isAnnuAdminAll()
  {
    $user_priv = ( array_key_exists("user_priv", self::$tabProperty) ? self::$tabProperty["user_priv"] : ALK_PRIV_SPACE_NONE );
    return (( $user_priv & ALK_PRIV_ANNU_ALL ) == ALK_PRIV_ANNU_ALL );
  }

  /**
   *  Retourne vrai si l'utilisateur est animateur de tous les espaces
   *        Attention, ne pas appeler cette méthode avant l'appel oAppli->verifSecu()
   * @return booleen
   */
  public static function isSpaceAnimAll()
  {
    $user_priv = ( array_key_exists("user_priv", self::$tabProperty) ? self::$tabProperty["user_priv"] : ALK_PRIV_SPACE_NONE );
    return (( $user_priv & ALK_PRIV_SPACE_ADMIN ) == ALK_PRIV_SPACE_ADMIN );
  }

  /**
   *  Retourne un tableau contenant des étoiles correspondant à la priorité d'affichage d'une information
   * @param iNbMaxRank   Nombre d'étoile max
   * @param bAddBestRank = false par défaut, 
   *                     = true pour afficher un niveau supplémentaire qui aura pour effet de 
   *                     reduire de 1 toutes les priorités existantes et de placer celle-ci à iNbMaxRank
   * @param strChar      caractère affiché pour caractériser un niveau de priorité
   * @return array
   */
  public static function getTabRank($iNbMaxRank, $bAddBestRank=false, $strChar="*")
  {
    $tabRank = array(0 => "Aucune");
    for($i=1; $i<=$iNbMaxRank; $i++) {
      $tabRank[$i] = str_repeat($strChar, $i);
    }
    if( $bAddBestRank ) {
      $tabRank[$iNbMaxRank+1] = str_repeat($strChar, $iNbMaxRank-1)."[*]";
    }
    return $tabRank;
  }
  
  /**
   *  Ajoute le suffixe de BD du language courent au field
   * @param strChamp    Radical du champ 
   * @return string  : champ suffixé ou non
   */
  public static function getDBCurrentLanguageField($strChamp)
  {
    $tabLg = AlkLangHandler::getLGTabLangue();
    if ( defined("ALK_LG_ID_DATA") && isset($tabLg[ALK_LG_ID_DATA]["bdd"])) {
      $strChamp .= $tabLg[ALK_LG_ID_DATA]["bdd"];
    }
    return $strChamp;
  }

  /**
   *  Retourne un objet de type AlkHtmlCleaner
   * @return AlkHtmlCleaner
   */
  public static function getHtmlCleaner()
  {
    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkhtmlcleaner.class.php");
    return new AlkHtmlCleaner();
  }

  /**
   * Retourne un objet de type AlkMnoGoSearch
   * @param typeBD  type de sgbd utilisé, prend la valeur ALK_BDD_TYPE par défaut
   * @param strMode Mode de recherche mnogosearch, =blob par défaut
   * @return AlkMnogoSearch
   */
  public static function getMnogoSearch($typeBD=ALK_BDD_TYPE, $strMode="blob")
  {
    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."indexer/alkmnogosearch.class.php");

    self::$oMnogoSearch = self::$oNull;
    switch( $typeBD ) {
    case ALK_SGBD_ORACLE:
    	if(defined("ALK_MNOGOSEARCH_LOGIN")){
    		self::$oMnogoSearch = new AlkMnogoSearch(ALK_MNOGOSEARCH_LOGIN,  "",  ALK_MNOGOSEARCH_PWD,  "", ALK_MNOGOSEARCH_SID, "", $strMode, "oracle");
    	}else{
    		self::$oMnogoSearch = new AlkMnogoSearch(ALK_ORA_LOGIN, "", ALK_ORA_PWD, "", ALK_ORA_SID, "", $strMode, "oracle");
    	}
      break;

    case ALK_SGBD_MYSQL:
    	if(defined("ALK_MNOGOSEARCH_LOGIN")){
        self::$oMnogoSearch = new AlkMnogoSearch(ALK_MNOGOSEARCH_LOGIN,  ALK_MNOGOSEARCH_HOST,  ALK_MNOGOSEARCH_PWD,  ALK_MNOGOSEARCH_BD,"", ALK_MNOGOSEARCH_PORT, $strMode, "mysql");  
      }else{
    	  self::$oMnogoSearch = new AlkMnogoSearch(ALK_MYSQL_LOGIN, ALK_MYSQL_HOST,  ALK_MYSQL_PWD,  ALK_MYSQL_BD,  "", ALK_MYSQL_PORT, $strMode, "mysql");
      }
      break;
      
    case ALK_SGBD_POSTGRES:
    	if(defined("ALK_MNOGOSEARCH_LOGIN")){
    	  self::$oMnogoSearch = new AlkMnogoSearch(ALK_MNOGOSEARCH_LOGIN,  ALK_MNOGOSEARCH_HOST,  ALK_MNOGOSEARCH_PWD,  ALK_MNOGOSEARCH_BD,"", ALK_MNOGOSEARCH_PORT, $strMode, "pgsql");	
    	}else{
    		self::$oMnogoSearch = new AlkMnogoSearch(ALK_POSTGRES_LOGIN,  ALK_POSTGRES_HOST,  ALK_POSTGRES_PWD,  ALK_POSTGRES_BD,"", ALK_POSTGRES_PORT, $strMode, "pgsql");
    	}
      break;
    }

    return self::$oMnogoSearch;
  }

  /**
   *  Retourne un objet de type AlkXml
   * @return AlkXml
   */
  public static function getXml()
  {
    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkxml.class.php");
    return new AlkXml();
  }

  /**
   * Retourne un objet de type AlkXmlGEdit
   * @param page_id    identifiant de la page générée
   * @param page_title titre de la page dans la langue sélectionnée
   * @param lg         chaine contenu le suffixe de la langue utiliée (_FR, _UK, etc...), =_FR par défaut 
   * @return AlkXmlGEdit
   */
  public static function getXmlGEdit($page_id, $page_title, $lg="_FR")
  {
    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkxmlgedit.class.php");
    return new AlkXmlGedit($page_id, $page_title, $lg);
  }

  /**
   * Retourne un objet de type AlkFSyndXmlWriter
   * @param strVersion  version du type de flux utilisé
   * @return AlkFSyndXmlWriter
   */
  public static function getFSyndXmlWriter($strVersion)
  {
    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkfsyndxml.class.php");
    return new AlkFSyndXmlWriter($strVersion);
  }

  /**
   *  Retourne une référence sur un objet de type AlkWordIndexer
   * @param language    Langage utilisé (fr, en)
   * @return AlkWordIndexer 
   */
  public static function &getWordIndexer($language="fr")
  {
    if( is_null(self::$oWordIndexer) ) {
      require_once (ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."indexer/alkwordindexer.class.php");
      self::$oWordIndexer = new AlkWordIndexer($language);
    }
    return self::$oWordIndexer;
  }

  /**
   *  Détermine les valeurs iFirst et iLast en fonction du numéro de page donné
   * @param & iNbEltParPage   Nb éléments par page, obtenu par TOKEN ou REQUEST sur champ 'iNbEltParPage' (defaut = ALK_ITEMS_PER_PAGE)
   * @param & iNumPage        Numéro de page courant; obtenu par TOKEN ou REQUEST sur champ 'page' (défaut = 1)
   * @param & iFirst          Indice de début de lecture des données en base
   * @param & iLast           Indice de fin de lecture des données en base
   * @param field_page        Nom du paramètre autre que page utilisé pour la pagination
   * @param field_nbelt       Nom du paramètre autre que iNbEltParPage utilisé pour avoir le nombre d'élts par page
   */
  public static function getIntervalDataByPage(&$iNbEltParPage, &$iNumPage, &$iFirst, &$iLast, $field_page="", $field_nbelt="")
  {
    $field_nbelt = ( $field_nbelt == "" ? "iNbEltParPage" : $field_nbelt );
    $field_page  = ( $field_page  == "" ? "page"          : $field_page  );
    
    $iDefautNbEltParPage = AlkRequest::_REQUESTint($field_nbelt, ( $iNbEltParPage==-1 ? ALK_ITEMS_PER_PAGE : $iNbEltParPage ));
    $iNbEltParPage =  AlkRequest::getToken($field_nbelt, $iDefautNbEltParPage);
    
    $iNumPage =  AlkRequest::getToken($field_page, AlkRequest::_REQUESTint($field_page, 1));
    $iFirst = ($iNumPage-1)*$iNbEltParPage;
    $iLast  = $iNumPage*$iNbEltParPage-1;
  }

  /**
   *  Présente une date sous forme textuelle (dépendant de la langue)
   * @param date10  Date au format JJ/MM/AAAA
   * @param bAbrev  si vrai : abrège le mois (defaut false)
   * @param lg      Langue d'arrivée (defaut fr)
   * 
   * @return Date sous forme textuelle (JJ Mois AAAA)
   */
  public static function ConvertDateToText($date10, $bAbrev=false, $lg="fr")
  {
    if ($bAbrev){
      $tabMonths = array(
        "fr" => array("", "Janv.", "Fév.", "Mars", "Avril", "Mai", "Juin", "Juil.", "Août", "Sept.", "Oct.", "Nov.", "Déc."),
        "en" => array("", "Jan.", "Feb.", "Mar.", "Apr.", "May", "June", "July", "Aug.", "Sept.", "Oct.", "Nov.", "Dec."),
      );
    }
    else {
      $tabMonths = array(
        "fr" => array("", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"),
        "en" => array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"),
      );
    }
    
    $tabDate = explode("/", $date10);
    if (count($tabDate)!=3) return $date10;
    $strMonth = $tabMonths[$lg][intval($tabDate[1])];
          
    return $tabDate[0]." ".$strMonth." ".$tabDate[2];
  }
  
  /**
   * Retourne le type de l'application maître de celui passé en paramètre
   * Si il n'existe pas d'appliation maître, retourne atype_id 
   * @param atype_id  type de l'application
   * @return int
   */
  public static function getMasterATypeId($atype_id)
  {
    $tabTypeId = array("_".ALK_ATYPE_ID_ACTU => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_LIEN => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_GLOS => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_FAQS => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_TACHE => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_DOC   => ALK_ATYPE_ID_IEDIT,
                       "_".ALK_ATYPE_ID_EVENT => ALK_ATYPE_ID_IEDIT,
                      );
    if( array_key_exists("_".$atype_id, $tabTypeId) ) {
      return $tabTypeId["_".$atype_id];  
    }
    return $atype_id;
  }
  
  /**
   * Evalue si un type d'application, donné par son nom de module, est installé
   * @param strAtypeAbrev     nom de module servant d'abreviation
   * @param bConstantsLoaded  booléen à true si les constantes en base de données ont été chargées avant l'appel de cette fontion, false sinon (défaut=true)
   * @return boolean
   */
  public static function isAppliTypeInstalled($strAtypeAbrev, $bConstantsLoaded=true)
  {
    $strAtypeAbrev = mb_strtoupper($strAtypeAbrev);
    
    if ( $bConstantsLoaded ) {
      return defined("ALK_ATYPE_ID_".$strAtypeAbrev) 
          && defined("ALK_B_ATYPE_".$strAtypeAbrev)
          && constant("ALK_B_ATYPE_".$strAtypeAbrev) == true;
    } else {
      $oQuery = AlkFactory::getQuery(ALK_ATYPE_ID_ESPACE);
      $bAType = $oQuery->getConstantValue("ALK_B_ATYPE_".$strAtypeAbrev, array(-1), array(-1), array(-1));
      return defined("ALK_ATYPE_ID_".$strAtypeAbrev) 
          && $bAType == true;
    }
  }
  
  /**
   * Calcul et retourne l'entier de publication pour la gestion editorial à partir des paramètres fournis
   * Retourne un entier compris entre 0 et 3 (2 premiers bits)
   * @param ref object_id  identifiant de l'objet sélectionné
   * @param ref typeAssoc  entier identifiant le type de object_id
   * @param cont_id        identifiant de l'espace, pris en compte si appli_id = -1
   * @param appli_id       identifiant de l'appli si <> -1, pris en compte si data_id et cat_id = -1
   * @param cat_id         identifiant de la catégorie si <> -1, pris en compte si data_id = -1
   * @param data_id        identifiant de la données si <> -1
   * @return int
   */
  public static function getGEditTypeAssoc(&$object_id, &$typeAssoc, $cont_id, $appli_id, $cat_id, $data_id)
  {
    if ( $data_id!="-1" && !binmask_match($typeAssoc, TASSOC_BYDATA) ){
      if ( binmask_match($typeAssoc, TASSOC_BYAPPLI) ){
        $typeAssoc -= TASSOC_BYAPPLI;
      }
      if ( binmask_match($typeAssoc, TASSOC_BYCATEG) ){
        $typeAssoc -= TASSOC_BYCATEG;
      }
      $typeAssoc += TASSOC_BYDATA;
    } 
    else if ( $cat_id!="-1" && !binmask_match($typeAssoc, TASSOC_BYCATEG) ){
      if ( binmask_match($typeAssoc, TASSOC_BYAPPLI) ){
        $typeAssoc -= TASSOC_BYAPPLI;
      }
      if ( binmask_match($typeAssoc, TASSOC_BYDATA) ){
        $typeAssoc -= TASSOC_BYDATA;
      }
      $typeAssoc += TASSOC_BYCATEG;
    } 
    else if ( $cat_id=="-1" && $appli_id!="-1" && !binmask_match($typeAssoc, TASSOC_BYAPPLI) ){
      if ( binmask_match($typeAssoc, TASSOC_BYCATEG) ){
        $typeAssoc -= TASSOC_BYCATEG;
      }
      if ( binmask_match($typeAssoc, TASSOC_BYDATA) ){
        $typeAssoc -= TASSOC_BYDATA;
      }
      $typeAssoc += TASSOC_BYAPPLI;
    }
    $object_id = ( binmask_match($typeAssoc, TASSOC_BYDATA)//$typeAssoc == 0
                   ? $data_id  //$cont_id
                   : ( binmask_match($typeAssoc, TASSOC_BYCATEG)//$typeAssoc == 1
                       ? $cat_id//$appli_id
                       : ( binmask_match($typeAssoc, TASSOC_BYAPPLI)//$typeAssoc == 2
                           ? $appli_id//$cat_id
                           : $cont_id/*$data_id*/ )));
  }
  
  /**
   * Initialise un code de sécurité qui sera affiché dans une image et initialisé en session.
   * Cette méthode est appelée au moment de générer l'image
   * Retourne le code généré
   * 
   * @param iLength  longueur du code à générer, =6 par défaut
   * @param strSet   Ensemble des caractères disponibles, par défaut = 123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz£$
   * @return string
   */
  public static function initSecurityCode($iLength=6, $strSet="123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz£\$")
  {
    if( $strSet == "" ) {
      $strSet = "123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz£\$";
    }
    
    // génére le code en piochant dans les caractères de la liste
    $strCode = "";
    while( strlen($strCode) < $iLength ) {
      $strCode .= $strSet[rand(0, strlen($strSet)-1)];
    }
    
    // on mémorise le code en session
    $_SESSION["ALK_SECURITY_CODE"] = $strCode;
    
    return $strCode;
  }
  
  /**
   * Retourne le code de sécurité
   * A appeler après le post du formulaire et comparant la valeur postée au résultat de cette fonction
   * 
   * @return string
   */
  public static function getLastSecurityCode()
  {
    if( !isset($_SESSION["ALK_SECURITY_CODE"]) ) {
      return  ""; 
    }
    
    return $_SESSION["ALK_SECURITY_CODE"]; 
  }

  /**
   * Encrypte une chaine de caractère selon la méthode paramétrée (md5 par défaut, sha1 sinon)
   * @param str  Chaîne à encrypyter
   * @return string
   */
  public static function encrypt($str)
  {
    if( !defined("ALK_ANNU_PWD_ENCRYPTION") ) {
      define("ALK_ANNU_PWD_ENCRYPTION", "md5"); 
    }
    return call_user_func(ALK_ANNU_PWD_ENCRYPTION, $str);
  }

  /**
   * Retourne une clé calculée à partir du nom du cache et de la signature de la valeur à mettre en cache
   * @param strCacheName  nom du cache (peut prendre la valeur vide et dans ce cas, retourne la clé liée au site)
   * @param strKey        clé de l'information (peut prendre la valeur vide et dans ce cas, retourne la clé liée au cache)
   * @return string
   */
  public static function memCacheGetKey($strCacheName="", $strKey="")
  {
    $cont_id = AlkRequest::getToken("cont_id", "-1");
    $oSpacetmp = AlkFactory::getSpace($cont_id);
    $strContDomaine = $oSpacetmp->oQuery->getEspaceRootContUrlByID($cont_id);
    if( $strContDomaine != "" && stripos( $strContDomaine, "http://" ) === false) $strContDomaine = "http://".$strContDomaine;
    return 
      ( $strContDomaine != "" ? $strContDomaine : (defined("ALK_ROOT_URL_MEMCACHE") ? ALK_ROOT_URL_MEMCACHE : ALK_ROOT_URL)).
      ( $strCacheName != "" ? "_".$strCacheName : "" ).
      ( $strKey != ""       ? "_".md5($strKey)  : "" );
  }
  
  /**
   * Initialise l'objet memcache
   * En cas d'erreur, l'object memCache prend la valeur null
   * la fonction retourne faux si l'objet memCache ne peut pas être utilisé
   * @return boolean
   */
  protected static function memCacheInit()
  {
    //return null; // Pas de cache avec PDO Statement
    if( !isset($_SESSION["ALK_MEMCACHE"]) ) {
      $_SESSION["ALK_MEMCACHE"] = array();
    }
    if( is_null(self::$oMemCache) && class_exists("memcache") ) {
      self::$oMemCache = new Memcache();
      $memcachePort = ( is_numeric(ini_get("memcache.default_port")) 
                        ? ini_get("memcache.default_port") 
                        : 11211 );
      $bRes = @self::$oMemCache->connect("localhost", $memcachePort);
      
      if( !$bRes ) {
        self::$oMemCache = null;
      }
    }
    return !is_null(self::$oMemCache);
  }
  
  /**
   * Ajoute dans le cache, l'élément oObject avec un délai d'expiration en seconde > 0
   * Retourne true si ok, false sinon
   * @param strCacheName   nom du cache
   * @param strKey         clé de l'information
   * @param oObject        référence ou valeur de l'objet à mettre en cache
   * @param iExpire        délai d'expiration
   * @return boolean
   */
  public static function memCacheSetData($strCacheName, $strKey, $oObject, $iExpire) 
  {
    $bRes = false;
    if( self::memCacheInit() ) {
      $key = self::memCacheGetKey($strCacheName, $strKey);
      $bRes = @self::$oMemCache->set($key, $oObject, 0, $iExpire);
      //echo "add data = > $key"; echo "\n\n<br><br>";
      if( $bRes ) {
        // mémorise en cache, la clé générée pour le cacheName
        $keyCache = self::memCacheGetKey($strCacheName);
        $tabMemKeysC = @self::$oMemCache->get($keyCache);
        if( !is_array($tabMemKeysC) ) {
          $tabMemKeysC = array();
        }
        $tabMemKeysC[$key] = true;
        //echo "add CACHE = > $keyCache"; print_r($tabMemKeysC); echo "\n\n<br><br>";
        @self::$oMemCache->set($keyCache, $tabMemKeysC, 0, 0);
        
        // mémorise en cache, le cacheName pour le site
        $keySite = self::memCacheGetKey();
        $tabMemKeysS = @self::$oMemCache->get($keySite);
        if( !is_array($tabMemKeysS) ) {
          $tabMemKeysS = array();
        }
        $tabMemKeysS[$keyCache] = true;
        //echo "add SITE = > $keySite"; print_r($tabMemKeysS); echo "\n\n<br><br>";
        @self::$oMemCache->set($keySite, $tabMemKeysS, 0, 0);  
      }
    }
    return $bRes;
  }
  
  /**
   * Retourne l'information identifiée
   * Retourne null si l'information demandée n'existe pas
   * @param strCacheName   nom du cache
   * @param strKey         clé de l'information
   * @return mixed
   */
  public static function memCacheGetData($strCacheName, $strKey)
  {
    $oRes = null;
    if( self::memCacheInit() ) {
      $key = self::memCacheGetKey($strCacheName, $strKey);
      //echo "get $key<br><br>";
      $oRes = @self::$oMemCache->get($key);
    }
    return $oRes;    
  }
  
  /**
   * Fait en sorte que tous les éléments du cache identifié par strCacheName ont expiré
   * @param strCacheName   nom du cache
   */
  public static function memCacheFlush($strCacheName="", $bDel=true)
  {
    if( self::memCacheInit() ) {
      $keySite = self::memCacheGetKey();
      if( !$bDel ) echo $keySite."<br>"; 
      $tabMemKeysSite = @self::$oMemCache->get($keySite);
      if( !is_array($tabMemKeysSite) ) {
        $tabMemKeysSite = array();
      }
      if( $strCacheName == "" ) {
        // récupère tous les caches du site à supprimer
        $tabMemKeysSiteDel = $tabMemKeysSite;
      } else {
        $keyCache = self::memCacheGetKey($strCacheName);
        $tabMemKeysSiteDel = array($keyCache => true);
      }
      foreach($tabMemKeysSiteDel as $keyCache => $bBool) {
        if( !$bDel ) echo $keySite." / ".$keyCache."<br>";
        $tabMemKeys = @self::$oMemCache->get($keyCache);
        $tabDel = array();
        if( is_array($tabMemKeys) ) {
          foreach($tabMemKeys as $keyCacheData => $bool) {
            if( !$bDel ) echo " - $keyCacheData<br>";
            if( $bDel ) @self::$oMemCache->delete($keyCacheData);
            $tabDel[] = $keyCacheData;
          }
          foreach($tabDel as $keyCacheData) {
            //if( !$bDel ) echo "del meta info ".$keyCacheData."<br>";
            if( $bDel ) unset($tabMemKeys[$keyCacheData]);
          }
          if( $bDel ) @self::$oMemCache->set($keyCache, $tabMemKeys, 0, 0);
        } else {
          if( $bDel ) @self::$oMemCache->set($keyCache, array(), 0, 0);
        }
        //if( !$bDel ) echo "meta cache ".$keyCacheData."<br>";
        if( $bDel ) unset($tabMemKeysSite[$keyCache]);
      }
      //if( !$bDel ) echo "set site cache ".$keySite."<br>";
      if( $bDel ) @self::$oMemCache->set($keySite, $tabMemKeysSite, 0, 0);
    }
  }
  
  /**
   * Initialise l'objet SolrClient
   * En cas d'erreur, l'object SolrClient prend la valeur null
   * la fonction retourne faux si l'objet SolrClient ne peut pas être utilisé
   * @return boolean
   */
  public static function getSolrClient()
  {
    if( is_null(self::$oAlkSolrClient) && class_exists("SolrClient") ) {
      // Teste l'accessibilité du serveur SOLR
      $info = array();
      $response = @http_get("http://".ALK_SOLR_SERVER.":".ALK_SOLR_PORT."/solr", array("timeout"=>1), $info);
      if ( $info["response_code"]==0 || $info["response_code"]==404 ){
        return self::$oNull;
      }
      
      self::$oAlkSolrClient = new SolrClient(array("hostname" => ALK_SOLR_SERVER,
                                                   "login" => ALK_SOLR_USER,
                                                   "password" => ALK_SOLR_PASSWORD,
                                                   "port" => ALK_SOLR_PORT,
                                                   "path" =>  ALK_SOLR_PATH));
    }
    return self::$oAlkSolrClient;
  }
  
  
  
  /**
   * retourne l'obket alkpChart
   * @param unknown_type $xsize
   * @param unknown_type $ysize
   */
  public static function getpChart($xsize, $ysize)
  {
    if( !(defined("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE) && defined("ALK_B_ATYPE_".mb_strtoupper(constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE)))) )
      return self::$oNull;

    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkpChart.class.php");
    return new pChart($xsize, $ysize);

  }
  
  /**
   * retourne l'objet pData
   */
  public static function getpData()
  {
    if( !(defined("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE) && defined("ALK_B_ATYPE_".mb_strtoupper(constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE)))) )
      return self::$oNull;

    require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkpData.class.php");
    return new pData;

  }
  
  /**
   * retourne l'objet pCache
   */
  public static function getpCache()
  {
    if( !(defined("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE) && defined("ALK_B_ATYPE_".mb_strtoupper(constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE)))) )
      return self::$oNull;
          
    if( is_null(self::$oPcache)){
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alkpCache.class.php");
      $cachefolder = ALK_ALKANET_ROOT_PATH.self::getUploadPath(ALK_ATYPE_ID_GRAPHE, true)."Cache/";
      if( !(@file_exists($cachefolder) ) ){
        @mkdir($cachefolder, 0770);
      }
      self::$oPcache = new pCache($cachefolder);
      
    }
    
    return self::$oPcache;
  }

  /**
   * retourne une instance d'envoi de sms
   */
  public static function getSMS($ts=ALK_SMS_SENDER_ALKANET)
  {
    if( is_null(self::$oSms)){
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alksms.class.php");
      self::$oSms = new AlkSms($ts);
    }
    return self::$oSms;
  }  
  
  /**
   * Retourne le type de navigateur client actuellement connecté
   * Calcul et mémorise cette valeur, ainsi que la famille de navigateur et la famille css du navigateur
   * Défini les constantes ALK_NAV, ALK_NAVFAM et ALK_NAVCSS
   * Retourne CLI, si le script est exécuté en CLI
   * @return string
   */
  public static function getNavigator()
  {
    if( !is_null(self::$strNav) ) {
      return self::$strNav;
    }
  
    $strUserAgent = ( isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : ALK_NAV_CLI );

    // Attention : ne pas changer l'ordre de ce tableau. L'ordre correspond à celui de la surcharge css
    $tabCSSNav = 
      array(ALK_NAVCSS_FF  => array(ALK_NAV_FFx, ALK_NAV_FF4, ALK_NAV_FF36, ALK_NAV_FF35, ALK_NAV_FF3, ALK_NAV_FF2,
                                    ALK_NAV_CHROMEx, ALK_NAV_CHROME10, ALK_NAV_CHROME9, ALK_NAV_CHROME8, ALK_NAV_CHROME7,
                                    ALK_NAV_SAFARIx, ALK_NAV_SAFARI5, ALK_NAV_SAFARI4,
                                    ALK_NAV_OPERAx, 
                                    ALK_NAV_OTHER,
                                    ),
            ALK_NAVCSS_IE  => array(ALK_NAV_IE11, ALK_NAV_IE10,ALK_NAV_IE9, ALK_NAV_IE8),                       
            ALK_NAVCSS_IE6 => array(ALK_NAV_IE6),
            ALK_NAVCSS_IE7 => array(ALK_NAV_IE7));                  

    $tabHTML5Nav = array(ALK_NAV_FFx, ALK_NAV_FF4, ALK_NAV_FF36,
                         ALK_NAV_CHROMEx, ALK_NAV_CHROME10, 
                         ALK_NAV_SAFARIx, ALK_NAV_SAFARI5);

    $strNavCSS = null;
    $strNavFAM = null;
    if( $strUserAgent == ALK_NAV_CLI ) {
      self::$strNav    = ALK_NAV_CLI;
      $strNavCSS = ALK_NAVCSS_FF;
      $strNavFAM = ALK_NAVFAM_FIREFOX;
    } else {
      // Attention : ne pas changer l'ordre du tableau
      $tabNavs = 
        array(ALK_NAVFAM_IEXPLORER => array('msie 6' => ALK_NAV_IE6,
                                            'msie 7' => ALK_NAV_IE7,
                                            'msie 8' => ALK_NAV_IE8,
                                            'msie 9' => ALK_NAV_IE9,
                                            'msie 10' => ALK_NAV_IE10,
                                            'Trident/7.0; rv:11.0' => ALK_NAV_IE11,
                                            ),
              ALK_NAVFAM_FIREFOX => array('FireFox/2.'  => ALK_NAV_FF2,
                                          'FireFox/3.5' => ALK_NAV_FF35,
                                          'FireFox/3.6' => ALK_NAV_FF36,
                                          'FireFox/3.'  => ALK_NAV_FF3,
                                          'FireFox/4.'  => ALK_NAV_FF4,
                                          'FireFox/'   => ALK_NAV_FFx,
                                          ),
              ALK_NAVFAM_CHROME => array('Chrome/7.'  => ALK_NAV_CHROME7,
                                         'Chrome/8.'  => ALK_NAV_CHROME8,
                                         'Chrome/9.'  => ALK_NAV_CHROME9,
                                         'Chrome/10.' => ALK_NAV_CHROME10,
                                         'Chrome/'   => ALK_NAV_CHROMEx,
                                         ),
              ALK_NAVFAM_SAFARI => array('Safari' => array('Version/4.' => ALK_NAV_SAFARI4,
                                                           'Version/5.' => ALK_NAV_SAFARI5,
                                                           'Version/'   => ALK_NAV_SAFARIx,
                                                           ),
                                         ),
              ALK_NAVFAM_OPERA => array('Opera' => ALK_NAV_OPERAx,
                                        ),
              );
  
      foreach($tabNavs as $strFamily => $tabNav) {
        foreach($tabNav as $strPattern => $oValue) {
          if( !(stripos($strUserAgent, $strPattern) === false) ) {
            if( is_array($oValue) ) {
              foreach($oValue as $strSubPattern => $oSubValue) {
                if( !(stripos($strUserAgent, $strSubPattern) === false) ) {
                  $strNavFAM = $strFamily;
                  self::$strNav = $oSubValue;
                  break 3; // sort des 3 boucles 
                }
              }
            } else {
              $strNavFAM = $strFamily;
              self::$strNav = $oValue;
              break 2; // sort des 2 boucles
            }
          }
        }   
      }
        
      if( is_null(self::$strNav) ) {
        self::$strNav = ALK_NAV_OTHER;
      }
    }

    $strNavCSS = null;
    foreach($tabCSSNav as $strNavCss => $tabNav) {
      if( in_array(self::$strNav, $tabNav) ) {
        $strNavCSS = $strNavCss;
        break;
      }
    }

    $strNavHTML = ALK_NAVHTML4;
    if( in_array(self::$strNav, $tabHTML5Nav) ) {
      $strNavHTML = ALK_NAVHTML5;
    }

    if( !defined("ALK_NAV") ) {
      define("ALK_NAV", self::$strNav);
    }
    if( !defined("ALK_NAVFAM") ) {
      define("ALK_NAVFAM", $strNavFAM);
    }
    if( !defined("ALK_NAVCSS") ) {
      define("ALK_NAVCSS", $strNavCSS);
    }
    if( !defined("ALK_NAVHTML") ) {
      define("ALK_NAVHTML", $strNavHTML);
    }

    return self::$strNav;
  }

  
  /**
   * Remise à vide du tableau tabSqlParams utilisé pour mémoriser
   * les associations entre paramètres postés et paramètres du composant JqueryDataTable
   */
  public static function emptySQLParamsName()
  {
    self::$tabSQLParams = array();
  }
  
  /**
   * Initialise le tableau tabSqlParams utilisé pour mémoriser
   * les associations entre paramètres postés et paramètres du composant JqueryDataTable
   */
  public static function setSQLParamsName() 
  {
    // JqueryDataTable param => post param
    $taParams = array("searchable" => "bSearchable",
                      "sortable"   => "bSortable", 
                      "limit"      => "iDisplayLength",
                      "start"      => "iDisplayStart",
                      "columns"    => "sColumns",
                      "iColumSort" => "iSortCol",
                      "iColumSorting" => "iSortingCols",
                      "sSortDir"   => "sSortDir",
                      "nbColumns"  => "iColumns",
                      "sSearchGlobal" => "sSearchGlobal",
                      "sSearch"    => "sSearch",
                      "sEcho"      => "sEcho"
                      );
   
    foreach ($taParams as $strkey => $strParamName) {
      self::$tabSQLParams[$strkey] = $strParamName;
    }
  }
  
  /**
   * Retourne la valeur d'une variable postée à partir d'une des clés du composant JqueryDataTable
   * @param strKey           clé correspondant aux clés du composant JqueryDataTable
   * @param strDefaultValue  valeur par défaut si la valeur postée n'existe pas
   * @return any
   */
  public static function getSQLParamsName($strKey, $strDefaultValue="") 
  {
    // La sécurité reste à améliorer
    $strParamName = ( isset(self::$tabSQLParams[$strKey]) 
                      ? self::$tabSQLParams[$strKey] 
                      : "");
    if( AlkRequest::_REQUEST("iColumns", false) !== false && is_array($strDefaultValue)){
      $tabReturnValue = array();
      $nbColonneDT = AlkRequest::_REQUEST("iColumns", 1);
      for( $dtI = 0; $dtI < $nbColonneDT; $dtI++ ){
        if( isset($_REQUEST[$strParamName."_".$dtI]) ){
          $tabReturnValue[$dtI] = AlkRequest::_REQUEST($strParamName."_".$dtI, "");
        }
      }
      return $tabReturnValue;
    }
    
    return AlkRequest::_REQUEST($strParamName, $strDefaultValue);
  }
  
  
  
}

/** définit les constantes ALK_NAV, ALK_NAVFAM, ALK_NAVCSS et ALK_NAVHTML sitôt son chargement */
AlkFactory::getNavigator();

?>