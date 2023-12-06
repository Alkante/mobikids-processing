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

require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkErrorLog.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkException.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDbPlateformMysql.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDbPlateformOracle.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDbPlateformPgsql.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDsPDO.php");
require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDrPDO.php");

/**
 * Classe de connexion PDO Alkanet
 * 
 */
class AlkDbPDO extends PDO implements AlkIntDbPlateform {
  
  const DRIVER_MYSQL  = "mysql";
  const DRIVER_PGSQL  = "pgsql";
  const DRIVER_ORACLE = "oci";
    
  /** référence sur la plate-forme associée */
  protected $plateform = null;
  
  /** nom du schéma à utiliser */
  protected $dbSchema;
  
  /** encodage de la liaison avec le serveur*/
  protected $dbEncoding;
  
  /** nom du driver utilisé correspondant à l'une des constantes DRIVER_xxx */
  protected $driverName;
  
  /** différence en heure entre heure locale du navigateur et GMT, <0 à l'est, >0 à l'ouest */
  protected $deltaGMT;

  /** différence en heure entre heure locale du serveur et GMT, <0 à l'est, >0 à l'ouest */
  protected $deltaGMTServ;
  
  /** ensemble des attributs fournis dans le constructeur */
  protected $attributes;

  /**
   * Initialise la classe de connexion à partir d'un fichier ini
   * Lance une exception en cas d'erreur
   * @param mixed $driverConf  chemin complet du fichier ini ou tableau de configuration
   */
  public function __construct($driverConf)
  {
    $strDriver        = "mysql";
    $strDSN           = $strDriver.":";
    $strUser          = "nologin";
    $strPwd           = "nopwd";
    $this->dbEncoding = "utf8";
    $this->dbSchema   = "public";
    $tabDSN           = array();
    $strOptions       = "";
    $this->attributes = array();
    $tabAlkPdoAttrib  = array();
    
    if( is_array($driverConf) ) {
      $this->driverName = 
      $strDriver        = ( array_key_exists("db_driver", $driverConf)         ? $driverConf["db_driver"]         : $strDriver        );
      $strDSN           = $strDriver.":";
      $tabDSN           = ( array_key_exists("dsn", $driverConf)               ? $driverConf["dsn"]               : $tabDSN           );
      $strUser          = ( array_key_exists("db_user", $driverConf)           ? $driverConf["db_user"]           : $strUser          );
      $strPwd           = ( array_key_exists("db_password", $driverConf)       ? $driverConf["db_password"]       : $strPwd           );
      $this->dbEncoding = ( array_key_exists("db_encoding", $driverConf)       ? $driverConf["db_encoding"]       : $this->dbEncoding );
      $this->dbSchema   = ( array_key_exists("db_schema", $driverConf)         ? $driverConf["db_schema"]         : $this->dbSchema   );
      $strOptions       = ( array_key_exists("db_options", $driverConf)        ? $driverConf["db_options"]        : $strOptions       );
      $this->attributes = ( array_key_exists("db_attributes", $driverConf)     ? $driverConf["db_attributes"]     : $this->attributes );
      $tabAlkPdoAttrib  = ( array_key_exists("alkpdo_attributes", $driverConf) ? $driverConf["alkpdo_attributes"] : $tabAlkPdoAttrib  );
    }
    if( is_array($tabDSN) ) {
      foreach($tabDSN as $key => $value ) {
        $strDSN .= $key."=".$value.";";
      }
    }
    if( is_array($tabAlkPdoAttrib) ) {
      foreach($tabAlkPdoAttrib as $property => $value ) {
        if( property_exists($this, $property) ) {
          $this->$property = $value;
        }
      }
    }

    parent::__construct($strDSN, $strUser, $strPwd, $strOptions);
    
    $this->deltaGMT        = "-1"; // Paris-France par défaut
    $this->deltaGMTServ    = "-1"; // Paris-France par défaut

    switch( $this->driverName ) {
      case self::DRIVER_PGSQL:  $this->plateform = new AlkDbPlateformPgsql(); break;
      case self::DRIVER_ORACLE: $this->plateform = new AlkDbPlateformOracle(); break;
      case self::DRIVER_MYSQL:  
      default:                  $this->plateform = new AlkDbPlateformMysql(); break;
    }
  }
  
  /**
   * Retourne une référence sur la plateforme utilisée
   * @return AlkDbPlateform
   */
  public function getPlateform()
  {
    return $this->plateform;
  }
  
  /**
   * Fixe les attributs de la connexion qui ont été envoyés dans le constructeur
   * la méthode setAttribute ne peut être appelée dans le constructeur.
   */
  public function initAttributes()
  {
    foreach($this->attributes as $k => $v ) {
      if( $k != "ATTR_STATEMENT_CLASS" ) {
        $this->setAttribute(constant("PDO::".$k), $v);
      } else {
        $this->setAttribute(constant("PDO::".$k), array("AlkDsPDO", array($this)));
      }
    }
    
    // applique l'encodage sélectionné par défaut
    switch( $this->driverName ) {
    	case self::DRIVER_PGSQL:  
    		$this->exec("SET NAMES '".$this->dbEncoding."';"); 
    		$this->exec("SET search_path TO ".$this->dbSchema.";");
    		break;
    	case self::DRIVER_ORACLE: /** @todo*/ break;
    	case self::DRIVER_MYSQL:  /** @nothingtodo */ 
    	default:                  break;
    }
  }
  
  /**
   * Mémorise la différence en heure entre l'heure locale du navigateur et GMT
   * Retourne une référence sur l'objet lui-même 
   * @param iDeltaGMT      entier, décalage en heure du client / GMT, <0 à l'est, >0 à l'ouest
   * @param iDeltaGMTServ  entier, décalage en heure du serveur / GMT, <0 à l'est, >0 à l'ouest
   * @return AlkDbPDO
   */
  public function setDeltaGMT($iDeltaGMT, $iDeltaGMTServ=-1)
  {
    $this->plateform->setDeltaGMT($iDeltaGMT, $iDeltaGMTServ);
    return $this;
  }
 
  /**
   * Retourne le schéma courant
   * @return string
   */
  public function getSchema()
  {
    return $this->dbSchema;
  }
  
  /**
   * Mémorise le nom du schema utilisé pour effectuer les requetes qui vont suivre
   * Retourne une référence sur l'objet lui-même
   * @param string $strDbSchema  Nom du schema utilisé
   * @return AlkDbPDO
   */
  public function setSchema($strDbSchema) 
  {
    $this->dbSchema = $strDbSchema;
    return $this;
  }
  
  /**
   * Fixe le schéma sur la connexion ouverte
   * Retourne une référence sur l'objet lui-même
   * @param string $strDbSchema  Nom du schema à utiliser, =vide par défaut pour utiliser le schéma courant
   * @return AlkDbPDO
   */
  public function setDbSchema($strDbSchema="") 
  {
    if( $strDbSchema != "" ) {
      $this->setSchema($strDbSchema);
    }
    $strSql = "";
    switch( $this->driverName ) {
      case self::DRIVER_PGSQL: $strSql = "set search_path to ".$this->dbSchema; break;
    }
    if( $strSql != "" ) {
      $this->exec($strSql);
    }
    return $this;
  }
  
  /**
   * Retourne le nom du driver de la connexion
   * @return string
   */
  public function getDriverName()
  {
    return $this->driverName;
  }
  
  /**
   * Retourne un AlkDsPDO vide pour éviter de retourner un objet null
   */
  protected function getEmptyDs()
  {
    return $this->getDs($this->plateform->getEmptyQuery());
  }
  
  /**
   * Retourne le résultat de la requete strSql 
   * @param string $strSql         requête sql
   * @param array  $tabParams      Tableau associatif contenant les variables de la requête
   * @param int    $iFirst         Indice de début de pagination, =0 par défaut
   * @param int    $iLast          Indice de fin de pagination, =-1 par défaut pour aucune pagination
   * @param bool   $bErr           =true par défaut pour alerter en cas d'erreur, =false pour accepter l'erreur et continuer le déroulement du programme
   * @param int    $iCacheExpire   =0 par défaut pour ne pas utiliser le cache, >0 pour mémoriser le résutat de la requête en cache avec un délai d'expiration de iCacheExpire secondes
   * @param string $strCacheName   =alkanet par défaut, permet de regrouper les éléments mémorisés pour faciliter la libération du cache
   * @param int    $fetchMode      = PDO::ATTR_DEFAULT_FETCH_MODE par défaut, correspond au 1er paramètre de setFetchMode()
   * @param mixed  $fetchModeParam =null par défaut, correspond au 2e paramètre de setFetchMode()
   * @param array  $fetchCtorArgs  =array vide par défaut, correspond au 3e paramètre de setFetchMode()
   * @return PDOStatement
   */
  public function getDs($strSql, $tabParams=array(), $iFirst=0, $iLast=-1, $bErr=true, $iCacheExpire=0, $strCacheName="alkanet", 
                        $fetchMode=PDO::FETCH_ASSOC, $fetchModeParam=null, $fetchCtorArgs=array())
  {
//     echo "<br>$strSql<br>";
    $dsPDO = null;
    if( !$bErr ) {
      ob_start();
    }
    $strSqlBase = $strSql;
    $this->applyParamFilters($strSql, $iFirst, $iLast);
    $bPagination    = $this->plateform->applySqlPaginationLimits($strSql, $iFirst, $iLast);
    $bTotalRowCount = $this->plateform->applySqlRowCountWithoutPagination($strSql);
    
    if( $iCacheExpire>0 && $strCacheName!="" ) {
      $dsPDO = AlkFactory::memCacheGetData($strCacheName, $strSql);
    }
    if( !is_object($dsPDO) ) {
      AlkErrorLog::startContext(__CLASS__, __METHOD__, __FILE__, __LINE__);
      try {
        // Attention : on ne peut plus changer la requête lorsque celle-ci est passée au pdostatement
        $strSqlRowCount = $this->plateform->getSqlRowCountWithoutPagination($strSqlBase);
//         echo "<br>$strSql<br>";
        $dsPDO = $this->prepare($strSql);
        $dsPDO->setCache(false, $iCacheExpire, $strCacheName);
        switch( $fetchMode ) {
          case PDO::FETCH_COLUMN: 
          case PDO::FETCH_INTO  : $dsPDO->setFetchMode($fetchMode, $fetchModeParam); break;
          case PDO::FETCH_CLASS : 
          case PDO::FETCH_OBJ   : $dsPDO->setFetchMode($fetchMode, $fetchModeParam, $fetchCtorArgs); break;
          default               : $dsPDO->setFetchMode($fetchMode); break;
        }
        $dsPDO->executeQuery($tabParams, $bPagination, $bTotalRowCount, $strSqlRowCount, $bErr);
      } catch(PDOException $e) {
        $dsPDO = $this->getDsNull();
        throw new AlkException("Erreur exécution de requête : ".$e->getMessage(), __CLASS__, __METHOD__, ( $bErr ? E_USER_ERROR : 0 ), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
      }
      AlkErrorLog::endContext("Exécution de la requête : ".$strSql, "Alkanet.PDO.Debug");
    } else {
      // obligation de se positionner au début puisque la mise en cache a lieu en fin de parcours
      $dsPDO->moveFirst();
    }
    
    if( !$bErr ) {
      ob_end_clean();
    }
    
    return $dsPDO;
  }
  
  /**
   * Récupère les paramètres de pagination ajax
   * et met à jour la requête de base ainsi que les indices de pagination
   * Tous les paramètres sont passés en référence
   * Retourne true si des changements ont été appliqués, false sinon
   * @param string $strSql  requête de base
   * @param int    $idFirst indice début de pagination, =0 par défaut
   * @param int    $idLast  indice fin de pagination, =-1 par défaut pour aucune pagination
   * @return boolean
   */
  protected function applyParamFilters(&$strSql, &$idFirst, &$idLast)
  {
    $_columns = AlkFactory::getSQLParamsName("columns", "");
    if( $_columns == "" ) {
      return false;
    }
    
    $_limit         = AlkFactory::getSQLParamsName("limit",           -2);
    $_sortable      = AlkFactory::getSQLParamsName("sortable",   array());
    $_searchable    = AlkFactory::getSQLParamsName("searchable", array());
    $_start         = AlkFactory::getSQLParamsName("start",           -2);
    $_iColSort      = AlkFactory::getSQLParamsName("iColumSort", array());
    $_iColSorting   = AlkFactory::getSQLParamsName("iColumSorting", 0);
    $_sSortDir      = AlkFactory::getSQLParamsName("sSortDir",        array());
    $_nbColumns     = AlkFactory::getSQLParamsName("nbColumns",       "");
    $_sSearch       = AlkFactory::getSQLParamsName("sSearch",    array());
//    $_sSearchGlobal = AlkFactory::getSQLParamsName("sSearchGlobal",   "");
    $_sSearchGlobal = AlkFactory::getSQLParamsName("sSearch",   "");
      
    $tabWhere       = array();
    $tabWhereGlobal = array();
    $tabOrderBy     = array();

    $tabColumns = explode(",", $_columns);
    // filtre
    foreach($tabColumns as $index => $strColumName) {
      if( $_sSearchGlobal != "" && isset($_searchable[$index]) && $_searchable[$index]=="true" && $strColumName != "" ) {
//        $tabWhereGlobal[] = "coalesce(".$this->plateform->getLowerCase($this->plateform->getCast($strColumName, "text")).", '')";
        $tabWhereGlobal[] = "coalesce(".$this->plateform->getLowerCase($strColumName).", '')";
      }
      if( isset($_searchable[$index]) && $_searchable[$index]=="true" && $strColumName != "" && $_sSearch[$index] != "" ) {
//        $tabWhere[] = "coalesce(".$this->plateform->getLowerCase($this->plateform->getCast($strColumName, "text")).", '')".
        $tabWhere[] = "coalesce(".$this->plateform->getLowerCase($strColumName).", '')".
          " like '%".$this->analyseSql($this->plateform->getLowerCase($_sSearch[$index]))."%'";
      }
    }
    // order by 
    foreach($_iColSort as $i => $itemICol) {
      if( isset($_sortable[$itemICol]) && $_sortable[$itemICol] == "true" ) {
        $tabOrderBy[] = $tabColumns[$itemICol]." ".$_sSortDir[$i];
      }
    }

    // analyseSql()
    $strEndStart = 
      ( !empty($tabWhereGlobal) 
        ? " where ".$this->plateform->getConcat(implode(",", $tabWhereGlobal))." like '%".$this->analyseSql(strtolower($_sSearchGlobal))."%'" 
        : "" ).
      ( !empty($tabWhere)
        ? ( !empty($tabWhereGlobal) 
            ? " and "
            : " where " ).
          implode(" and ", $tabWhere)
        : "" ).
      ( !empty($tabOrderBy) 
        ? " order by ".implode(",", $tabOrderBy) 
        : "" );
      
    $strSql = "select foo.* from (".$strSql.") foo ".$strEndStart;
    $idFirst = $_start;
    $idLast  = $idFirst+$_limit-1;
    return true;
  }
  
  /**
   * Retourne le résultat de la requete strSql
   * Méthode qui assure la rétrocompatibilité
   * Le dataset contient des lignes de type AlkDrPDO
   * @see getDs
   * @deprecated since version 3.6
   */
  public function initDataset($strSql, $idFirst=0, $idLast=-1, $bErr=true, $iCacheExpire=0, $strCacheName="alkanet")
  {
    return $this->getDs($strSql, array(), $idFirst, $idLast, $bErr, $iCacheExpire, $strCacheName, PDO::FETCH_CLASS, "AlkDrPDO");
  }
  
  /**
   * Execute une requête et retourne la valeur du premier champ du premier enregistrement
   * Si une erreur se produit ou qu'aucune valeur n'est trouvée, retourne defaultValue
   * Cette fonction utilise le cache avec un temps d'expiration correspondant au temps de chargement de la page.
   * Temps fixé à 5s.
   * @param strSql        Requête SQL
   * @param defaultValue  valeur retournée si erreur ou si aucune information trouvée
   * @return mixed
   */
  public function getScalarSql($strSql, $defaultValue)
  {
    $oRes = $defaultValue;
    
    $oDs = $this->getDs($strSql);
    if( !is_null($oDs) ) {
      $oRes = $oDs->fetchColumn();
      if( $oRes === false ) {
        $oRes = $defaultValue;
      }
    } 
    
    return $oRes;
  }
  
  /**
   * Exécute la requête sql
   * Retourne un entier :
   *   >0  le nombre d'enregistrements traités par la requête.
   *   =0  si rien n'a été fait
   *   =-1 si une erreur est intervenue
   * @param string $strSql     requête sql
   * @param array  $tabParams  Tableau associatif contenant les variables de la requête
   * @param bool   $bErr       =true par défaut pour alerter en cas d'erreur, =false pour accepter l'erreur et continuer le déroulement du programme
   * @return int
   */
  public function execSql($strSql, $tabParams=array(), $bErr=true)
  {
    if( !$bErr ) {
      ob_start();
    }
    
    $nbRow = 0;
    AlkErrorLog::startContext(__CLASS__, __METHOD__, __FILE__, __LINE__);
    try {
      if( false && empty($tabParams) ) {
        $nbRow = $this->exec($strSql);
      } else {
        $stm = $this->prepare($strSql);
        if( $stm && $stm->execute($tabParams) ) {
          $nbRow = $stm->rowCount();
          $stm->closeCursor();
        }
      }
    } catch(PDOException $e) {
      $nbRow = -1;
      throw new AlkException("Erreur exécution de requête : ".$strSql.", ".$e->getMessage(), __CLASS__, __METHOD__, ( $bErr ? E_USER_ERROR : 0 ), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
    }
    AlkErrorLog::endContext("Exécution de la requête : ".$strSql, "Alkanet.PDO.Debug");
    
    if( !$bErr ) {
      ob_end_clean();
    }
    
    return $nbRow;
  }
  
  /**
   * Exécute la requête sql en appelant la méthode execSql avec tabParams=array()
   * Méthode qui assure la rétrocompatibilité
   * @deprecated since version 3.6
   * @see execSql
   * @return boolean (true si ok, false si erreur)
   */
  public function executeSql($strSql, $bErr=true)
  {
    return ( $this->execSql($strSql, array(), $bErr) < 0 ? false : true );
  }
 
  /**
   * Retourne la prochaine valeur de la séquence
   * Pour MYSQL, il est nécessaire de créer une table nommée SEQUENCE, 
   * qui possède un enregistrement avec les valeurs par défaut de chaque séquence qui correspondent aux colonnes de la table
   * @param string $strSequenceName  Nom de la séquence
   * @return int
   */
  public function getSeqId($strSequenceName)
  {
    $id = 10; // séquence par défaut
    switch( $this->driverName ) {
      case self::DRIVER_PGSQL:
        $strSql = "select nextval('".$strSequenceName."') as id_Next";
        $oDs = $this->query($strSql);
        $id = ( $oDs ? $oDs->fetchColumn() : $id );
        break;
      case self::DRIVER_ORACLE:
        $strSql = "select ".$strSequenceName.".nextval id_Next from dual";
        $oDs = $this->query($strSql);
        $id = ( $oDs ? $oDs->fetchColumn() : $id );
        break;
      case self::DRIVER_MYSQL:
      default:
        $strSql = "UPDATE SEQUENCE set ".$strSequenceName."=LAST_INSERT_ID(".$strSequenceName."+1)";
        $this->exec($strSql);
        $id = $this->lastInsertId();
        break;
    }
    return $id;
  }
  
  /**
   * Obtenir le prochain identifiant à inserer dans la table strTable
   * Retourne un entier : le prochain id
   * @param strTable    Nom de la table
   * @param strField    Nom du champ id
   * @param strSequence Nom de la sequence associée
   * @deprecated since version 3.6
   * @return int
   */
  public function getNextId($strTable, $strField, $strSequenceName="")
  {
    $id = 10; // séquence par défaut
    if( strToUpper($strTable) == "SEQUENCE" || $strSequenceName!="" ) {
      $strSequenceName = ( $strSequenceName != "" ? $strSequenceName : $strField);
      $id = $this->getSeqId($strSequenceName);
    } else {
      throw new AlkException("Vous devez utiliser la méthode \$dbConn->lastInsertId() pour récupérer la valeur de la clé primaire.", __CLASS__, __METHOD__, E_USER_ERROR, E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO");
      /*$strSql = "select max(".$strField.") as IDMAX from ".$strTable;
      $oDs = $this->query($strSql);
      $id = ( !is_null($oDs) ? $oDs->fetchColumn() : $id );
      $id++;*/
    }
    return $id;
  }
  
  /**
   * Remplace les caractères spéciaux d'un champ texte d'une requete SQL
   * Retourne une chaine obtenue après traitement sans les bornes quotes
   * Utiliser sinon la méthode quote qui elle intègre les bornes
   * @param string $strString   Valeur du champ texte d'une requete, sans les bornes quotes 
   * @param bool   $bHtmlVerif  true par défaut pour éviter les attaques de type XSS, false pour éviter le filtre.
   * @return string 
   */
  public function analyseSql($strString, $bHtmlVerif=true)
  {
    $strTmp = str_replace("\\", "\\\\", $strString);
    $strTmp = $this->plateform->convertCharactersCp1252($strTmp);
    /*if ( ALK_HTML_ENCODING!=($this->strDbEncoding == 'UTF8' ? 'UTF-8' : $this->strDbEncoding) ) {
      $strTmp = mb_convert_encoding($strTmp, $this->strDbEncoding, ALK_HTML_ENCODING);
    }*/
    if( $bHtmlVerif ) {
      $strTmp = htmlspecialchars($strTmp, ENT_COMPAT); //, ($this->strDbEncoding == 'UTF8' ? 'UTF-8' : $this->strDbEncoding));
    }
    // retire les quotes de début et de fin pour maintenir la rétro-compatibilité
    return substr($this->quote($strTmp), 1, -1);
  }
  
  /**
   * Retourne le code sql équivalent pour un clob : champ de type TEXT
   * @param string $strField    nom du champ clob
   * @param string $strValue    valeur du champ clob
   * @param bool   $bHtmlVerif  true par défaut pour éviter les attaques de type XSS, false pour éviter le filtre.
   * @deprecated since version 3.6
   * @return string
   */
  public function getCLob($strField, $strValue, $bHtmlVerif=true) 
  {
    return "'".$this->analyseSql($strValue, $bHtmlVerif)."'";
  }

  /************************************
   * Méthodes de traitement sgbd
   ************************************/

  /**
   * Retourne le numéro de version du SGBD
   * @return string
   */
  public function getSGBDVersion()
  {
    return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
  }
  
  /**
   * Désactive le mode autocommit et
   * initialise une transaction sur la connexion courante
   * Retourne true si ok, false sinon
   * @return bool
   */
  public function initTransaction()
  {
    if( !$this->inTransaction() ) {
      return $this->beginTransaction();
    }
    return false;
  }
  
  /**
   * Effectue un commit sur l'ensemble des requêtes exécutées sur la transaction en cours
   * Ferme ensuite la transaction
   * Retourne true si ok, false sinon
   * @return bool
   */
  public function commitTransaction()
  {
    if( $this->inTransaction() ) {
      return $this->commit();
    }
    return false;
  }

  /**
   * Effectue un rollback pour annuler l'ensemble des requêtes exécutées sur la transaction en cours
   * Ferme ensuite la transaction
   * Retourne true si ok, false sinon
   * @return bool
   */
  public function rollBackTransaction() 
  {
    $bRes = false;
    if( $this->inTransaction() ) {
      try {
        $bRes = $this->rollBack();
      } catch ( PDOException $e ) {
        $bRes = false;
        throw new AlkException("La tentative de rollBack() a échoué : ".$e->getMessage(), __CLASS__, __METHOD__, E_USER_WARNING, E_USER_WARNING, __FILE__, __LINE__, "Alkanet.PDO");  
      }
    }
    return $bRes;
  }

  /***********************************
   * Méthodes gestion de structures
   **********************************/
          
  /**
   * Construit la requête sql qui recopie la ligne d'une table vers une ligne d'une autre
   * Retourne la requête sql générée
   * @param strTableSrc     Nom de la table source
   * @param strTablsDest    Nom de la table destination
   * @param tabFieldPkSrc   tableau associatif : cle = nom du champ clé primaire de la table source, valeur = valeur de cette cle
   * @param tabFieldPkDest  tableau associatif : cle = nom du champ clé primaire de la table destination, valeur = valeur de cette cle
   * @param tabFieldsName   tableau associatif : cle = nom du champ destination (sans alias de table en début), 
   *                                             valeur = nom du champ source avec alias de table égale à "s." 
   *                                                    ou valeur spécifique
   *                                                    ou vide pour reprendre le même nom de colonne que la source
   * @return string
   */
  public function getSqlCopyRowFromTableToTable($strTableSrc, $strTableDest, $tabFieldPkSrc, $tabFieldPkDest, $tabFieldsName)
  {
    return $this->plateform->getSqlCopyRowFromTableToTable($strTableSrc, $strTableDest, $tabFieldPkSrc, $tabFieldPkDest, $tabFieldsName);
  }
  
  /**
   * Retourne le code SQL permettant de créer une table (uniquement les champs typés)
   * @param strTableName  nom de la table ou tableau
   * @param tabFields     tableau contenant les informations sur les champs à créer
   * @return string
   */
  public function getSqlCreateTable($strTableName, $tabDataFields, $tableInherit=null)
  {
    
    return $this->plateform->getSqlCreateTable($strTableName, $tabDataFields, $tableInherit);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer un ensemble de tables
   * @param tabData        tableau contenant les informations sur les tables à créer
   * @param tabInheritance tableau contenant les caractéristiques éventuelles d'héritage entre tables (valable en Postgrès)
   * @param dropMode       mode d'affichage de l'instruction préalable DROP TABLE : none=non visible, comment=visible en commentaire, drop=visible et exécutable 
   * @return array
   */
  public function getTabSqlCreateTable($tabData, $tabInheritance=array(), $dropMode="none")
  {
    return $this->plateform->getTabSqlCreateTable($tabData, $tabInheritance, $dropMode);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une table
   * @param strTableName  nom de la table ou tableau
   * @return string
   */
  public function getSqlDropTable($strTablename)
  {
    return $this->plateform->getSqlDropTable($strTablename);
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de tables
   * @param tabData  tableau contenant les informations sur les tables à supprimer
   * @return array
   */
  public function getTabSqlDropTable($tabData)
  {
    return $this->plateform->getTabSqlDropTable($tabData);
  }
  
  /**
   * Retourne le code Sql permettant d'ajouter une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @param strFieldList  liste des champs caractérisant la clé primaire
   * @return string 
   */
  public function getSqlAddPrimary($strTableName, $strPkName, $strFieldList)
  {
    return $this->plateform->getSqlAddPrimary($strTableName, $strPkName, $strFieldList);
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à créer
   * @return array
   */
  public function getTabSqlAddPrimary($tabData)
  {
    return $this->plateform->getTabSqlAddPrimary($tabData);
  }
  
  /**
   * Retourne le code Sql permettant de supprimer une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @return string
   */
  public function getSqlDropPrimary($strTableName, $strPkName)
  {
    return $this->plateform->getSqlDropPrimary($strTableName, $strPkName);
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à supprimer
   * @return array
   */
  public function getTabSqlDropPrimary($tabData)
  {
    return $this->plateform->getTabSqlDropPrimary($tabData);
  }
  
  /**
   * Retourne le code SQL permettant de créer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @param strFieldName   nom du champ
   * @return string
   */
  public function getSqlCreateIndex($strTableName, $strIndexName, $strFieldName)
  {
    return $this->plateform->getSqlCreateIndex($strTableName, $strIndexName, $strFieldName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlCreateIndex($tabData)
  {
    return $this->plateform->getTabSqlCreateIndex($tabData);
  }
  
 /**
   * Retourne le code SQL permettant de supprimer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @return string
   */
  public function getSqlDropIndex($strTableName, $strIndexName)
  {
    return $this->plateform->getSqlDropIndex($strTableName, $strIndexName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlDropIndex($tabData)
  {
    return $this->plateform->getTabSqlDropIndex($tabData);
  }

  /**
   * Retourne le code SQL permettant d'ajouter une clé étrangère à une table
   * @param strTableName   nom de la table locale
   * @param strFkName      nom de la clé étrangère
   * @param strFieldFk     nom du champ local
   * @param strTablePk     nom de la table cible 
   * @param strFieldPk     nom du champ cible
   * @param strOption      option complémentaire
   * @return string
   */
  public function getSqlAddConstraintForeignKey($strTableName, $strFkName, $strFieldFk, $strTablePk, $strFieldPk, $strOption="")
  {
    return $this->plateform->getSqlAddConstraintForeignKey($strTableName, $strFkName, $strFieldFk, $strTablePk, $strFieldPk, $strOption);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlAddConstraintForeignKey($tabData)
  {
    return $this->plateform->getTabSqlAddConstraintForeignKey($tabData);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une clé étrangère à une table
   * @param strTableName   nom de la table locale
   * @param strFkName      nom de la clé étrangère
   * @return string
   */
  public function getSqlDropConstraintForeignKey($strTableName, $strFkName)
  {
    return $this->plateform->getSqlDropConstraintForeignKey($strTableName, $strFkName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropConstraintForeignKey($tabData)
  {
    return $this->plateform->getTabSqlDropConstraintForeignKey($tabData);
  }

  /**
   * Retourne le code SQL permettant d'ajouter une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @param strFieldList   liste des champs caractérisant l'unicité
   * @return string
   */
  public function getSqlAddConstraintUnique($strTableName, $strUqName, $strFieldList)
  {
    return $this->plateform->getSqlAddConstraintUnique($strTableName, $strUqName, $strFieldList);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlAddConstraintUnique($tabData)
  {
    return $this->plateform->getTabSqlAddConstraintUnique($tabData);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @return string
   */
  public function getSqlDropConstraintUnique($strTableName, $strUqName)
  {
    return $this->plateform->getSqlDropConstraintUnique($strTableName, $strUqName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlDropConstraintUnique($tabData)
  {
    return $this->plateform->getTabSqlDropConstraintUnique($tabData);
  }

  /**
   * Retourne le code SQL permettant de créer une vue
   * @param strViewName   nom de la vue
   * @param strSql        code sql de la vue
   */
  public function getSqlCreateView($strViewName, $strSql)
  {
    return $this->plateform->getSqlCreateView($strViewName, $strSql);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer des vues
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlCreateView($tabData, $dropMode="none")
  {
    return $this->plateform->getTabSqlCreateView($tabData, $dropMode);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une vue
   * @param strViewName   nom de la vue
   * @return string
   */
  public function getSqlDropView($strViewName)
  {
    return $this->plateform->getSqlDropView($strViewName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer une vue
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropView($tabData)
  {
    return $this->plateform->getTabSqlDropView($tabData);
  }
  
  /**
   * Retourne le code SQL permettant de créer une séquence
   * @param strSeqName   nom de la séquence
   * @param iStart       indice de début de la séquence
   */  
  public function getSqlCreateSequence($strSeqName, $iStart)
  {
    return $this->plateform->getSqlCreateSequence($strSeqName, $iStart);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à créer
   * @return array
   */
  public function getTabSqlCreateSequence($tabData)
  {
    return $this->plateform->getTabSqlCreateSequence($tabData);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une séquence
   * @param strSeqName   nom de la séquence
   * @return string
   */  
  public function getSqlDropSequence($strSeqName)
  {
    return $this->plateform->getSqlDropSequence($strSeqName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à supprimer
   * @return array
   */  
  public function getTabSqlDropSequence($tabData)
  {
    return $this->plateform->getTabSqlDropSequence($tabData);
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de modifier la structure de tables
   * @param tabData  tableau contenant les informations sur les tables à modifier
   * @return array
   */  
  public function getTabSqlAlterTable($tabData)
  {
    return $this->plateform->getTabSqlAlterTable($tabData);
  }

  /**
   * Retourne la requete sql correspondant à un alter table modify column
   * Retourn vrai si ok, faux sinon
   *
   * @param tableName           Nom de la table
   * @param columnName          Nom actuel de la colonne
   * @param new_columnName      Nouveau nom de la colonne
   * @param new_columnType      Nouveau type de la colonne
   * @param new_columnLength    Nouvelle longueur de la colonne
   * @param new_columnDefault   Nouvelle valeur par défaut de la colonne
   * @param new_columnNullable  Nouvel état nullable de la colonne (=0 : NOT NULL, =1 : NULL)
   * @return boolean
   */
  public function getSqlAlterTableUpdateColumn($strTableName, $strColumnName, $strNewColumnName, $strNewColumnType="", 
                                               $strNewColumnLength="", $strNewColumnDefault="", $iNewColumnNullable=-1)
  {
    return $this->plateform->getSqlAlterTableUpdateColumn($strTableName, $strColumnName, $strNewColumnName, $strNewColumnType, 
                                                          $strNewColumnLength, $strNewColumnDefault, $iNewColumnNullable);
  }
      
  /**
   * Effectue un alter table modify column
   * Retourn vrai si ok, faux sinon
   *
   * @param strTableName        Nom de la table
   * @param strColumnName       Nom actuel de la colonne
   * @param strNewColumnName    Nouveau nom de la colonne, =strColumnName si vide
   * @param strNewColumnType    Nouveau type de la colonne
   * @param strNewColumnLength  Nouvelle longueur de la colonne
   * @param strNewColumnDefault Nouvelle valeur par défaut de la colonne, = "" pour ne rien fait
   * @param iNewColumnNullable  Nouvel état nullable de la colonne (=0 : NOT NULL, =1 : NULL, =-1 no change)
   * @return boolean
   */
  public function doAlterTableUpdateColumn($strTableName, $strColumnName, $strNewColumnName, $strNewColumnType="", 
                                                    $strNewColumnLength="", $strNewColumnDefault="", $iNewColumnNullable=-1)
  {
    $strSql = $this->getSqlAlterTableUpdateColumn($strTableName, $strColumnName, $strNewColumnName, $strNewColumnType, 
                                                  $strNewColumnLength, $strNewColumnDefault, $iNewColumnNullable);
    return ( $this->execSql($strSql) >= 0 ? true : false );
  }

    
  /**
   * Retourne la requête sql effectuant un alter table add column
   * Retourne une chaine vide si erreur
   *
   * @param strTableName     Nom de la table
   * @param strColumnName    Nom de la colonne
   * @param strColumnType    Nouveau type de la colonne
   * @param strColumnLength  Nouvelle longueur de la colonne
   * @param strColumnDefault Nouvelle valeur par défaut de la colonne
   * @param iColumnNullable  Nouvel état nullable de la colonne (=0 : NOT NULL, =1 : NULL, =-1 no change)
   * @return string
   */
  public function getSqlAlterTableAddColumn($strTableName, $strColumnName, $strColumnType="", 
                                            $strColumnLength="", $strColumnDefault="", $iColumnNullable=-1)
  {
    return $this->plateform->getSqlAlterTableAddColumn($strTableName, $strColumnName, $strColumnType, 
                                                       $strColumnLength, $strColumnDefault, $iColumnNullable);
  }
  
  /**
   * Effectue un alter table add column
   * Retourn vrai si ok, faux sinon
   *
   * @param strTableName     Nom de la table
   * @param strColumnName    Nom de la colonne
   * @param strColumnType    Nouveau type de la colonne
   * @param strColumnLength  Nouvelle longueur de la colonne
   * @param strColumnDefault Nouvelle valeur par défaut de la colonne
   * @param iColumnNullable  Nouvel état nullable de la colonne (=0 : NOT NULL, =1 : NULL, =-1 no change)
   * @return boolean
   */
  public function doAlterTableAddColumn($strTableName, $strColumnName, $strColumnType="", 
                                                 $strColumnLength="", $strColumnDefault="", $iColumnNullable=-1)
  {
    $strSql = $this->getSqlAlterTableAddColumn($strTableName, $strColumnName, $strColumnType, 
                                               $strColumnLength, $strColumnDefault, $iColumnNullable);
    return ( $this->execSql($strSql) >= 0 ? true : false );
  }
  
  /**
   * Retourne la requête sql effectuant un alter table drop column
   * Retourne une chaine vide si erreur
   *
   * @param strTableName       Nom de la table
   * @param strColumnName      Nom de la colonne
   * @return string
   */
  public function getSqlAlterTableDropColumn($strTableName, $strColumnName)
  {
    return $this->plateform->getSqlAlterTableDropColumn($strTableName, $strColumnName);
  }

  /**
   * Effectue un alter table drop column
   * Retourn vrai si ok, faux sinon
   *
   * @param strTableName       Nom de la table
   * @param strColumnName      Nom de la colonne
   * @return boolean
   */
  public function doAlterTableDropColumn($strTableName, $strColumnName)
  {
    $strSql = $this->getSqlAlterTableDropColumn($strTableName, $strColumnName);
    return ( $this->execSql($strSql) >= 0 ? true : false );
  }
  
  /***********************************
   * Méthodes fonctions sql avancées
   **********************************/

  /**
   * Retourne l'expression SQL qui correspond à la fonction cast
   * @param string $strValue Nom du champ ou expression sql à traiter
   * @param string $strType  Type du champ ou expression sql à traiter
   * @return string
   */
  public function getCast($strValue, $strType)
  {
    return $this->plateform->getCast($strValue, $strType);
  }
  
  /**
   *  Retourne le code sql des instructions "show tables" et "show tables like "
   * 
   * @param strLikeTable    Si non vide permet de faire un show tables like 
   * @return string SQL
   */
  public function getShowTables($strLikeTable="", $bOnlyTables=false)
  {
    return $this->plateform->getShowTables($strLikeTable, $bOnlyTables);
  }

  /**
   * Retourne la description des colonnes d'une table
   * @param strTableName    Nom de la table
   * @return dataset
   */
  public function getSqlTableColumns($strTableName)
  {
    return $this->plateform->getSqlTableColumns($strTableName);
  }

  /**
   * Retourne une chaine de comparaison dans une requete SQL
   * Retourne une chaine : l'expression SQL associ�e � la comparaison
   * @param strField   Nom du champ dont la valeur est � tester
   * @param strCompare Opérateur de comparaison
   * @param strValeur  Valeur à comparer
   * @param strCaseOk  Valeur retournée si comparaison vraie
   * @param strCaseNok Valeur retournée si comparaison fausse
   * @return string
   */
  public function compareSql($strField, $strCompare, $strValue, $strCaseOK, $strCaseNok)
  {
    return $this->plateform->compareSql($strField, $strCompare, $strValue, $strCaseOK, $strCaseNok);
  }

  /**
   * Retourne le code SQL permettant de récupérer un numéro de séquence
   * Ce code sql peut-être intégrer dans une requête de type insert
   * @param string  $strSequenceName  nom de la séquence
   * @param boolean $bUpdateSeq       =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @return string
   */
  public function getSqlNextSeqId($strSequenceName, $bUpdateSeq=true)
  {
    return $this->plateform->getSqlNextSeqId($strSequenceName, $bUpdateSeq);
  }
  
    /**
   * Retourne le code SQL permettant de récupérer un numéro de séquence
   * Ce code sql peut-être intégrer dans une requête de type insert
   * @param string  $strSequenceName  nom de la séquence
   * @param boolean $bUpdateSeq       =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @return string
   */
  public function getSqlSeqId($strSequenceName, $bUpdateSeq=true)
  {
    return $this->plateform->getSqlSeqId($strSequenceName, $bUpdateSeq);
  }
  
  /**
   * Obtenir le prochain identifiant à inserer dans la table strTable
   * Retourne un entier : le prochain id
   * @param strTable    Nom de la table
   * @param strField    Nom du champ id
   * @param strSequence Nom de la sequence associée
   * @param bUpdateSeq  =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @deprecated since version 3.6 use getSqlSeqId()
   * @return int
   */
  public function getStrNextId($strTable, $strField, $strSequence="", $bUpdateSeq=true)
  {
    return $this->plateform->getStrNextId($strTable, $strField, $strSequence, $bUpdateSeq);
  }

  /**
   * Retourne l'expression SQL qui fournit la concatenation récursive sur une colonne
   * @param string $strField      Colonne sur laquelle s'effectue la concaténation groupée
   * @param string $strSeparator  Chaine SQL donnant le séparateur
   * @param string $strOrder      Ordre de lecture des données (Mysql)
   * @param bool   $bDistinct     Indique si sélection des éléments distincts seulement
   * @param string $strFrom       PGSQL : requete de sélection des valeurs 
   * @param string $bNullTest     PGSQL : effectue un test de nullité sur le champ ou non, test effectué par défaut=true
   * @return string
   */
  public function getGroupConcat($strField, $strSeparator="','", $strOrder="", $bDistinct=false, $strFrom="", $bNullTest=true)
  {
    return $this->plateform->getGroupConcat($strField, $strSeparator, $strOrder, $bDistinct, $strFrom, $bNullTest);
  }
  
  /************************************
   * Méthodes de traitement de chaine
   ************************************/
   
  /**
   * Retourne l'expression SQL qui fournit la concatenation d'un nombre indéfinit de paramètres
   * @return string
   */
  public function getConcat()
  {
    return call_user_func_array(array($this->plateform, "getConcat"), func_get_args());
  }
  
  /**
   * Retourne l'expression SQL qui fournit une sous-chaine
   * @param sstring $strField Nom du champ ou expression sql à traiter
   * @param int     $iPos     Position de départ (premier caractère = 0)
   * @param int     $iLength  Longueur de la sous-chaine (facultatif), =-1 par défaut pour signifier la fin de chaine
   * @return string
   */
  public function getSubstring($strField, $iPos, $iLength=-1)
  {
    return $this->plateform->getSubstring($strField, $iPos, $iLength);
  }

  /**
   * Retourne l'expression SQL qui transforme en minuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getLowerCase($strField)
  {
    return $this->plateform->getLowerCase($strField);
  }
  
  /**
   * Retourne l'expression SQL qui transforme en majuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getUpperCase($strField)
  {
    return $this->plateform->getUpperCase($strField);
  }
  
  /**
   * Retourne l'expression sql permettant de faire une comparaison de chaines sans tenir compte des caractères accentués
   * Ne fonctionne réellement que sous Oracle. Pour les autres, une simple comparaison en minuscules est réalisée.
   * @param string $strField Nom du champ de la table
   * @param string $strOp    Operateur de test SQL : like, =
   * @param string $strVal   Chaine de comparaison qui doit etre traitee par ora_analyseSQL auparavant
   * @deprecated since version 3.6
   * @return string
   */
  public function getStrConvert2ASCII7($strField, $strOp, $strVal)
  {
    return $this->plateform->getStrConvert2ASCII7($strField, $strOp, $strVal);
  }
  
  /**
   * Retourne la requête d'ajout de version dans la base Alkanet
   * @param string $moduleName   Nom du module alkanet
   * @param string $version      numéro de version de la forme M.m.c
   * @param int    $moduleLevel  ordonnancement du module
   * @param string $description  Description de la version
   * @return string
   */
  public function getSqlAddVersion($moduleName, $version, $moduleLevel, $description)
  {
    return $this->plateform->getSqlAddVersion($this->analyseSql($moduleName), $version, $moduleLevel, $this->analyseSql($description));
  }
  
  /************************************
   * Méthodes de traitement de date
   ************************************/
  
  /**
   * Retourne l'expression SQL qui fournit la date-heure système en tenant compte du fuseau horaire du serveur et du client
   * @return string
   */
  public function getDateCur()
  {
    return $this->plateform->getDateCur();
  }

  /**
   * Retourne le code SQL de la fonction de formatage de date à partir d'une chaine (typée date au format fourni ou expression sql)
   * @param string $strFormat   Format de la date passée en paramètre (bToDate=true) ou format de la date souhaitée (bToDate=false)
   * @param string $strDate     Valeur de la date équivalente au format (bToDate=true) ou expression sql à formater (bToDate=false)
   * @param bool   $bToDate     Identifie l'expression à retourner : 
   *                              = true  : (par défaut) l'expression retournée est de type date (équivalent to_date())
   *                              = false : l'expression retournée est de type char (équivalent to_char())
   * @param bool   $bCastToInt  =true  pour caster la transformation en entier si bToDate=false
   *                            =false pour laisser to_char() dans son type par défaut, si bToDate=false
   * @note Format d'entrée : 
   *       - SS    : secondes
   *       - MI    : Minute
   *       - HH    : Heure du jour
   *       - D     : Numéro du jour dans la semaine
   *       - DAY   : Nom du jour
   *       - DD    : Numéro du jour dans le mois
   *       - DDD   : Numéro du jour dans l'année
   *       - IW    : Numéro de la semaine dans l'année (Norme iso)
   *       - WW    : Numéro de la semaine dans l'année
   *       - MM    : Numéro du mois 
   *       - MONTH : Nom du mois
   *       - YYYY  : année sur 4 chiffres
   *       - YY    : année sur 2 chiffres
   * @return string
   */
  public function getDateFormat($strFormat, $strDate, $bToDate=true, $bCastToInt=false)
  {
    return $this->plateform->getDateFormat($strFormat, $strDate, $bToDate, $bCastToInt);
  }

 /**
   * Retourne le code SQL de la fonction de formatage de date à partir d'un timestamp unix
   * @param string $strFormat   Format de la date souhaitée (bToDate=false), non utilisé avec bToDate=true
   * @param string $iTimestamp  timestamp unix de la date à formater (to_char) ou à transformer (to_date)
   * @param bool   $bToDate     Identifie l'expression à retourner : 
   *                              = true  : (par défaut) l'expression retournée est de type date (équivalent to_date())
   *                              = false : l'expression retournée est de type char (équivalent to_char())
   * @note Format d'entrée : 
   *       - SS    : secondes
   *       - MI    : Minute
   *       - HH    : Heure du jour
   *       - D     : Numéro du jour dans la semaine
   *       - DAY   : Nom du jour
   *       - DD    : Numéro du jour dans le mois
   *       - DDD   : Numéro du jour dans l'année
   *       - IW    : Numéro de la semaine dans l'année (Norme iso)
   *       - WW    : Numéro de la semaine dans l'année
   *       - MM    : Numéro du mois 
   *       - MONTH : Nom du mois
   *       - YYYY  : année sur 4 chiffres
   *       - YY    : année sur 2 chiffres
   * @return string
   */
  public function getDateFromTimestamp($strFormat, $iTimestamp, $bToDate=true)
  {
    return $this->plateform->getDateFromTimestamp($strFormat, $iTimestamp, $bToDate);
  }
  
  /**
   * Retourne l'expression sql qui donne le nombre de jours qui sépare les 2 dates
   * Retourne le résultat de $strDateField1-$strDateField2
   * @param string $strDateField1    Nom du champ ou expression sql de type date à traiter contenant la première date
   * @param string $strDateField2    Nom du champ ou expression sql de type date à traiter contenant la seconde date
   * @return string
   */
  public function getDateDiff($strDateField1, $strDateField2)
  {
    return $this->plateform->getDateDiff($strDateField1, $strDateField2);
  }
  
    /**
   * Retourne l'expression SQL qui permet d'additionner des intervalles de temps à une date
   * @param string $strDateField Nom du champ ou expression sql à traiter
   * @param string $iNb          Nombre d'intervalles à ajouter ou expression sql
   * @param char   $strInterval  Type d'intervalle : Y=année, M=mois, D=jour, H=heure
   * @return string
   */
  public function getDateAdd($strDateField, $iNb, $strInterval)
  {
    return $this->plateform->getDateAdd($strDateField, $iNb, $strInterval);
  }
  
  /**
   * Retourne l'expressino fournissant le timestamp unix correspondant à l'expression de type date passée en paramètre
   * @param string $dateField  expression de type date
   * @return string
   */
  public function getUnixTimestamp($dateField)
  {
    return $this->plateform->getUnixTimestamp($dateField);
  }
  
  /**
   * Retourne l'expression sql qui donne le nombre de jour entre deux dates
   * @param string strDateFrom   Valeur de la date supérieure
   * @param string strDateTo     Valeur de la date inférieure 
   * @deprecated since version 3.6
   * @param string
   */
  public function getNbDaysBetween($strDateFrom, $strDateTo)
  {
    return $this->getDateDiff($strDateFrom, $strDateTo);
  }

  
  /**
   * Méthodes historiques
   */

  /**
   * Retourne une chaine correspondant à une liste dédoublonnée dont les valeurs sont séparées par une virgule
   * @param string $strListId  liste d'identifiants séparés par une virgule
   * @return string
   */
  public function delDoublon($strListId)
  {
    return implode(",", array_unique(explode(",", $strListId)));
  }
  
  /**
   * Décrémente de 1 le rang d'informations qui répondent au filtre strWhereSelect
   * @param string $strTableName    Nom de la table
   * @param string $strFieldRank    Nom du champ rang
   * @param string $strWhereUpdate  Filtre SQL pour modifier le rang
   * @param string $strWhereSelect  Filtre SQL pour retrouver le rang de l'information à supprimer
   */
  public function updateRankBeforeDel($strTableName, $strFieldRank, $strWhereUpdate, $strWhereSelect)
  {
    $iRank = pow(2, 31);
    $strSql = "select ".$strFieldRank." from ".$strTableName." where ".$strWhereSelect;
    $iRank = $this->getScalarSql($strSql, $iRank);
    
    $strSql = "update ".$strTableName." set ".
      $strFieldRank."=".$strFieldRank."-1".
      " where ".$strWhereUpdate.
      "   and ".$strFieldRank.">".$iRank;
    $this->execSql($strSql);
  }

  /**
   * Met a jour le champ rang d'une table en fonction des parametres
   * @param string $strTableName   Nom de la table
   * @param string $strFieldRank   Nom du champ rang
   * @param int    $iNewRank       Indice du nouveau rang
   * @param bool   $bAdd           =true si ajout, false si suppression
   * @param string $strWhere       Condition supplementaire pour la selection du rang
   */
  public function updateRank($strTableName, $strFieldRank, $iNewRank, $bAdd, $strWhere)
  {
    $bExist = true;
    $strSign = "+";
    $strComp = ">=";
    if( $bAdd == false ) { 
      $strSign = "-"; 
      $strComp=">"; 
    }
    if( $bAdd == true ) {
      $strSql = "select ".$strFieldRank.
        " from ".$strTableName.
        " where ".$strFieldRank."=".$iNewRank.
        ( $strWhere != "" ? " and ".$strWhere : "" );
      $iRank = $this->getScalarSql($strSql, "---");
      $bExist = ( $iRank == "---" ? false : $bExist );
    }
    if( $bExist == true ) {
      $strSql = "update ".$strTableName." set ".
        $strFieldRank."=".$strFieldRank.$strSign."1".
        " where ".$strFieldRank.$strComp.$iNewRank.
        ( $strWhere != "" ? " and ".$strWhere : "" );      
      $this->execSql($strSql);
    }
  }
  
  /**
   * Retourne l'indice de rang suivant
   * @param string $strTableName   Nom de la table
   * @param string $strFieldRank   Nom du champ rang
   * @param string $strWhere       Condition supplementaire pour la selection du rang
   * @return int
   */
  public function getNextRank($strTableName, $strFieldRank, $strWhere)
  {
    $iRank = 1;
    $strSql = "select ".$this->compareSql("max(".$strFieldRank.")", "is", "NULL", "1", "max(".$strFieldRank.")+1")." as MAX_RG".
      " from ".$strTableName.
      ( $strWhere != "" ? " where ".$strWhere : "" );
    return $this->getScalarSql($strSql, $iRank);  
  }

  /**  
   * Permute les rangs de 2 occurences
   * @param string $strTableName   Nom de la table
   * @param string $strFieldRank   Nom du champ rang
   * @param int    $iRank          Indice du rang actuel de la donnée
   * @param int    $iDelta         entier : +1 pour descendre, -1 pour monter
   * @param string $strWhereGroup  Condition pour filtrer les données d'un même ensemble pour lequel le rang est utilisé (pas de and avant et après l'expression)
   * @param string $strWhereData   Condition complémentaire à strWhereGroup pour retrouver le rang de l'information à modifier (pas de and avant et après l'expression)
   *   */
  public function switchRank($strTableName, $strFieldRank, $iRank, $iDelta, $strWhereGroup, $strWhereData)
  {
    $iNewRank = $iRank + $iDelta;
    if( $iNewRank < 1 ) {
      return;
    }
 
    $bInTrans = $this->beginTransaction();
    if( $bInTrans ) {
      // met à jour le rang de la donnée permutée   
      $strSql = "update ".$strTableName.
        " set ".$strFieldRank."=".$iRank.
        " where ".$strWhereGroup." and ".$strFieldRank."=".$iNewRank;
      $this->execSql($strSql);

      // met à jour le rang de la donnée sélectionnée
      $strSql = "update ".$strTableName.
        " set ".$strFieldRank."=".$iNewRank.
        " where ".$strWhereGroup." and ".$strWhereData;
      $this->execSql($strSql);
      $this->commit();
    }
  }

} 
