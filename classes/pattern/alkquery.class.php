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

require_once(ALK_ALKANET_ROOT_PATH."classes/pattern/alkobject.class.php");
require_once(ALK_ALKANET_ROOT_PATH."classes/pattern/alkfactory.class.php");

/**
 * @package Alkante_Class_Pattern
 * 
 * @class AlkQuery
 * @brief Classe abstraite racine des classes regroupant toutes les requètes au SGBD
 */
abstract class AlkQuery extends AlkObject
{
  /** Classe de connexion à la base */
  public $dbConn;

  /** références vers les query nécessaires à l'indexation */
  protected $oQuerySearch;
  protected $oQuerySearchAction;

  private static $oSQuerySearch;
  private static $oSQuerySearchAction;
  
  protected static $oSearchSolr = null;

  /**
   *  Constructeur par défaut
   *
   * @param dbConn    objet contenant la connexion ouverte vers la base
   */
  public function __construct(&$dbConn)
  {
    parent::__construct();

    $this->dbConn =& $dbConn;

    $this->oQuerySearch = null;
    $this->oQuerySearchAction = null;

    if( !defined("ALK_B_QUERY_SEARCH_INSTANCIATE") && 
        defined("ALK_B_ATYPE_SEARCH") && ALK_B_ATYPE_SEARCH==true ) {
      define("ALK_B_QUERY_SEARCH_INSTANCIATE", true); 
      self::$oSQuerySearch = AlkFactory::getQuery(ALK_ATYPE_ID_SEARCH);
      self::$oSQuerySearchAction = AlkFactory::getQueryAction(ALK_ATYPE_ID_SEARCH);
    }
    
    if( defined("ALK_B_QUERY_SEARCH_INSTANCIATE") ) {
      $this->oQuerySearch = self::$oSQuerySearch;
      $this->oQuerySearchAction = self::$oSQuerySearchAction;
    }
  }

  /**
   *  Destructeur par défaut
   */
  public function __destruct() { }

  public function initSearcherSolr()
  {
    $oNull = null;
    if ( defined("ALK_SEARCH_SOLR") && ALK_SEARCH==ALK_SEARCH_SOLR ){
      if ( !is_null(self::$oSearchSolr) ) 
        return self::$oSearchSolr;
      if ( file_exists(ALK_ALKANET_ROOT_PATH."libconf/classes/alksearcher_solr.class.php") ){
        require_once(ALK_ALKANET_ROOT_PATH."libconf/classes/alksearcher_solr.class.php");
        self::$oSearchSolr = new AlkSearcherSolrSpecific($this, $this->dbConn);
        return self::$oSearchSolr;
      } else {
        require_once(ALK_ALKANET_ROOT_PATH."classes/pattern/alksearcher_solr.class.php");
        self::$oSearchSolr = new AlkSearcherSolr($this, $this->dbConn);
        return self::$oSearchSolr;
      }
    }
    return $oNull;
  }
  
  /**
   *  Execute puis retourne le résultat de la requete strSql dans un dataSet
   *
   * @param strSql  Requete sql à exécuter
   * @param iFirst  Indice de début pour la pagination
   * @param iLast   Indice de fin pour la pagination
   * @param bErr    true si gestion des erreurs, false pour passer sous silence l'erreur
   * @return Retourne un dataSet
   */
  public function getDs($strSql, $iFirst=0, $iLast=-1, $bErr=true)
  {
    return $this->dbConn->InitDataset($strSql, $iFirst, $iLast, $bErr);
  }
  
  /**
   *  Execute la requete strSql
   *
   * @param strSql  Requete sql à exécuter
   * @param bErr    true si gestion des erreurs, false pour passer sous silence l'erreur
   * @return boolean : exécution réussie
   */
  public function executeSql($strSql, $bErr=true)
  {
    return $this->dbConn->executeSql($strSql, $bErr);
  }
  
  /**
   *  Traduit le champ en une liste de champs multilingues
   * 
   * @param strChamp    Radical des champs multilingue
   * @param strAlias    Radical des alias donnés champs multilingue
   * @param bInList     Indique que la liste obtenue est à intégrer dans une liste existante (séparateur initial)
   * 
   * @return string  : chaine entrant dans une énumération de champs SQL
   */
  public function getMultilingueList($strChamp, $strAlias="", $bInList=false)
  {
    $strList = "";
    foreach ($this->tabLangue as $key => $tabLg){
      $strList  .= (!$bInList && $strList=="" ? "" : ",").$strChamp.$tabLg["bdd"].($strAlias!="" ? " as ".$strAlias.$tabLg["bdd"] : "");
    }
    return $strList;
  }
  
  /**
   *  Traduit le tableau de valeurs (textes) en une liste de champs multilingues
   * 
   * @param tabValues    Tableau des valeurs multilingues
   * @param bInList     Indique que la liste obtenue est à intégrer dans une liste existante (séparateur initial)
   * 
   * @return string  : chaine entrant dans une énumération de champs SQL
   */
  public function getMultilingueListValues($tabValues, $bInList=false)
  {
    $strList = "";
    foreach ($this->tabLangue as $key => $tabLg){
      if ( array_key_exists($key, $tabValues))
        $strList  .= (!$bInList && $strList=="" ? "" : ",")."'".$this->dbConn->AnalyseSql($tabValues[$key])."'";
      else
        $strList  .= (!$bInList && $strList=="" ? "" : ",")."NULL";
    }
    return $strList;
  }
  
  /**
   *  Traduit le champ en champs multilingues et effectue l'opération demandée (strOp) sur le tableau de valeurs
   *        Le résultat retourné sera du type donné par strGlue : calcul binaire ('and', 'or', ..) 
   *        ou liste (',')
   * 
   * @param strChamp    Radical des champs multilingue
   * @param tabValues   Tableau des valeurs multilingues
   * @param strGlue     Opérateur à appliquer entre les résultats
   * @param strOp       Opérateur à appliquer entre le champ et la valeur ('=' par défaut)
   * @param bInList     Indique que la liste obtenue est à intégrer dans une liste existante (séparateur initial)
   * 
   * @return string  : chaine entrant dans une énumération de champs d'SQL
   */
  public function getMultilingueOperation($strChamp, $tabValues, $strGlue, $strOp="=", $bInList=false)
  {
    $strList = "";
    foreach ($this->tabLangue as $key => $tabLg){
      if ( array_key_exists($key, $tabValues))
        $strList  .= (!$bInList && $strList=="" ? "" : " ".$strGlue." ").$strChamp.$tabLg["bdd"].$strOp."'".$this->dbConn->AnalyseSql($tabValues[$key])."'";
    }
    return $strList;
  }

  /**
   *  Retourne la partie affectation d'une requete update
   * 
   * @param tabValue   Tableau contenant les infos (champ, valeur, type) de l'enregistrement
   * @param tabIgnore  Liste des champs de tabValues à ignorer dans cette requête
   * @param strGlue    Chaine de liaison (", " par défaut)
   * @return string SQL
   */
  protected function getPartUpdateSql($tabValue, $tabIgnore=array(), $strGlue=", ", $dateFormat="DD/MM/YYYY", $timeFormat="HH:MI")
  {
    $tabIgnore = array_map("mb_strtoupper", $tabIgnore);
    $strSql = "";
    foreach($tabValue as $strField => $tabVal) {
      if ( in_array(mb_strtoupper($strField), $tabIgnore) ) continue;
      $bFound = false;
      foreach ($tabIgnore as $field_name){
        if ( preg_match("!^".$field_name."\[[^]]*\]!ui", $strField) )
          $bFound = true;
      }
      if ( $bFound ) continue;
      $idType = $tabVal[0];
      $strValue = $tabVal[1];
      if( $strValue != ALK_FIELD_NOT_VIEW ) {
        switch( $idType )  {
        case ALK_SQL_TEXT:
          $strSql .= $strGlue.$strField."='".$this->dbConn->analyseSql($strValue)."'";
          break;
        
        case ALK_SQL_HTML:
          $strSql .= $strGlue.$strField."='".$this->dbConn->analyseSql($strValue, false)."'";
          break;
          
        case ALK_SQL_TEXT_ENCODE:
          // l'éditeur ajoute systématiquement les tag <p></p> autour du contenu validé
          $strValue = mb_ereg_replace("<p>", "", $strValue);
          $strValue = mb_ereg_replace("</p>", "", $strValue);
          $strValue = AlkRequest::decodeValue($strValue);
          $strSql .= $strGlue.$strField."='".$this->dbConn->analyseSql($strValue)."'";
          break;

        case ALK_SQL_HTML_ENCODE:
          // l'éditeur ajoute systématiquement les tag <p></p> autour du contenu validé
          $strValue = mb_ereg_replace("<p>", "", $strValue);
          $strValue = mb_ereg_replace("</p>", "", $strValue);
          $strValue = AlkRequest::decodeValue($strValue);
          $strSql .= $strGlue.$strField."='".$this->dbConn->analyseSql($strValue, false)."'";
          break;
          
        case ALK_SQL_TEXT_RAWURL_ENCODE:
         $strValue = utf8_encode(rawurldecode($strValue));
         $strSql .= $strGlue.$strField."='".$this->dbConn->analyseSql($strValue, false)."'";
         break;
            
        case ALK_SQL_NUMBER:
          if( $strValue == "" ) {
            $strSql .= $strGlue.$strField."=null";
          } else {
            $strSql .= $strGlue.$strField."=".mb_ereg_replace(",", ".", $strValue);
          }
          break;
          
        case ALK_SQL_DATE:
        case ALK_SQL_TIME:
        case ALK_SQL_DATETIME:
          if( $strValue == "" ) {
            $strSql .= $strGlue.$strField."=null";
          } elseif( $strValue == $this->dbConn->getDateCur() ) {
            $strSql .= $strGlue.$strField."=".$strValue;
          } else {
            if( $idType == ALK_SQL_DATE ) {
              $strSql .= $strGlue.$strField."=".$this->dbConn->GetDateFormat($dateFormat, "'".$strValue."'");
            } elseif( $idType == ALK_SQL_TIME) {
              $strSql .= $strGlue.$strField."=".$this->dbConn->GetDateFormat($timeFormat, "'".$strValue."'");
            } else {
              $strSql .= $strGlue.$strField."=".$this->dbConn->GetDateFormat($dateFormat." ".$timeFormat, "'".$strValue."'");
            }
          }
          break;
          
        case ALK_SQL_DATECUR:
          $strSql .= $strGlue.$strField."=".$this->dbConn->getDateCur();
          break;
          
        case ALK_SQL_EXPR:
          $strSql .= $strGlue.$strField."=".$strValue;
          break;
        }
      }
    }
    if( $strSql != "" )
      return mb_ereg_replace("^".$strGlue, "", $strSql);
    return "";
  }

  /**
   *  Retourne la partie affectation d'une requete insert
   * 
   * @param tabValue   Tableau contenant les infos (champ, valeur, type) de l'enregistrement
   * @param tabIgnore  Liste des champs de tabValues à ignorer dans cette requête
   * @return string SQL
   */
  protected function getPartInsertSql($tabValue, $tabIgnore=array(), $dateFormat="DD/MM/YYYY", $timeFormat="HH:MI")
  {    
    $tabIgnore = array_map("mb_strtoupper", $tabIgnore);
    $strListField = "";
    $strListValues = "";
    foreach($tabValue as $strField => $tabVal) {    
      if ( in_array(mb_strtoupper($strField), $tabIgnore) ) continue;
      $bFound = false;
      foreach ($tabIgnore as $field_name){
        if ( preg_match("!^".$field_name."\[[^]]*\]!ui", $strField) )
          $bFound = true;
      }
      if ( $bFound ) continue;
      $idType = $tabVal[0];
      $strValue = $tabVal[1];
      if( $strValue != ALK_FIELD_NOT_VIEW ) {
        switch( $idType ) {
        case ALK_SQL_TEXT:
          $strListField .= ", ".$strField;
          $strListValues .= ", '".$this->dbConn->analyseSql($strValue)."'";
          break;

        case ALK_SQL_HTML:
          $strListField .= ", ".$strField;
          $strListValues .= ", '".$this->dbConn->analyseSql($strValue, false)."'";
          break;

        case ALK_SQL_TEXT_ENCODE:
          // l'éditeur ajoute systématiquement les tag <p></p> autour du contenu validé
          $strListField .= ", ".$strField;
          $strValue = mb_ereg_replace("<p>", "", $strValue);
          $strValue = mb_ereg_replace("</p>", "", $strValue);
          $strValue = AlkRequest::decodeValue($strValue);
          $strListValues .= ", '".$this->dbConn->analyseSql($strValue)."'";
          break;

        case ALK_SQL_HTML_ENCODE:
          // l'éditeur ajoute systématiquement les tag <p></p> autour du contenu validé
          $strListField .= ", ".$strField;
          $strValue = mb_ereg_replace("<p>", "", $strValue);
          $strValue = mb_ereg_replace("</p>", "", $strValue);
          $strValue = AlkRequest::decodeValue($strValue);
          $strListValues .= ", '".$this->dbConn->analyseSql($strValue, false)."'";
          break;
          
        case ALK_SQL_TEXT_RAWURL_ENCODE:
         $strListField .= ", ".$strField;
         $strValue = utf8_encode(rawurldecode($strValue));
         $strListValues .= ", '".$this->dbConn->analyseSql($strValue, false)."'";
         break;

        case ALK_SQL_NUMBER:
          $strListField .= ", ".$strField;
          if( $strValue == "" )
            $strListValues .= ", null";
          else
            $strListValues .= ", ".mb_ereg_replace(",", ".", $strValue);
          break;

        case ALK_SQL_DATE:
        case ALK_SQL_TIME:
        case ALK_SQL_DATETIME:
          $strListField .= ", ".$strField;
          if( $strValue == "" ) {
            $strListValues .= ", null";
          } elseif( $strValue == $this->dbConn->getDateCur() ) {
            $strListValues .= ", ".$strValue;
          } else {
            if( $idType == ALK_SQL_DATE ) {
              $strListValues .= ", ".$this->dbConn->GetDateFormat($dateFormat, "'".$strValue."'");
            } elseif( $idType == ALK_SQL_TIME ) {
              $strListValues .= ", ".$this->dbConn->GetDateFormat($timeFormat, "'".$strValue."'");
            } else {
              $strListValues .= ", ".$this->dbConn->GetDateFormat($dateFormat." ".$timeFormat, "'".$strValue."'");
            }
          }
          break;
          
        case ALK_SQL_DATECUR:
          $strListField .= ", ".$strField;
          $strListValues .= ", ".$this->dbConn->getDateCur();
          break;

        case ALK_SQL_EXPR:
          $strListField .= ", ".$strField;
          $strListValues .= ", ".$strValue;
          break;
        }
      }
    }
    if( $strListField != "" && $strListValues != "")
      return "(".mb_substr($strListField, 1).") values (".mb_substr($strListValues, 1).")";
    return "";
  }

  /**
   *  Récupère les paramètres d'une application dans un dataset
   * @param appli_id     Identifiant de l'application
   * @param param_field  Cible sur un paramétre donné par son nom
   * @param bExactField  Correspondance exacte de param_field avec le nom si vrai, like param_field% sinon
   * @return dataset
   */
  function getDsAppliParam($appli_id, $param_field="", $bExactField=true)
  {
    $strSql = "select * from SIT_APPLI_PARAM where APPLI_ID=".$appli_id.
              ($param_field!="" ? " and PARAM_FIELD".($bExactField ? "=" : " like ")."'".$this->dbConn->analyseSql($param_field).($bExactField ? "" : "%")."'" : "");
    return $this->dbConn->initDataset($strSql);
  }
  
  /**
   *  Récupère les paramètres d'une application dans un tableau
   * @param appli_id     Identifiant de l'application
   * @param param_field  Cible sur un paramétre donné par son nom
   * @param bExactField  Correspondance exacte de param_field avec le nom si vrai, like param_field% sinon
   * @return dataset
   */
  function getTabAppliParam($appli_id, $param_field="", $bExactField=true)
  {
    $tabParam = array();
    $dsParam = $this->getDsAppliParam($appli_id, $param_field, $bExactField);
    while ( $drParam = $dsParam->fetch() ){
      $param_field = $drParam["PARAM_FIELD"];
      $param_id    = $drParam["PARAM_ID"];
      $param_type  = $drParam["PARAM_TYPE"];
      $strField = "PARAM_VTEXT";
      switch ($param_type) {
        case "0" : $strField = "PARAM_VTEXT"; break;
        case "1" : $strField = "PARAM_VINT";  break;
        case "2" : $strField = "PARAM_VDATE"; break;
      }
      $param_value = $drParam[$strField];
      $tabParam[$param_field]["PARAM_FIELD"] = $param_field;
      $tabParam[$param_field]["PARAM_ID"]    = $param_id;
      $tabParam[$param_field]["PARAM_VALUE"] = $param_value;
      $tabParam[$param_field]["PARAM_TYPE"]  = $param_type;
    }
    return $tabParam;
  }
    
   
  /**
   *  Définit les paramètres associés à appli_id
   *        Les paramètres sont lus dans quatre tableaux passés en post
   *        ("PARAM_ID", "PARAM_FIELD", "PARAM_TYPE", "PARAM_VALUE") 
   *
   * @param appli_id  Identifiant de l'application
   */
  function setAppliParam($appli_id)
  {
    $tabParamId    = AlkRequest::_POST("PARAM_ID", array());
    $tabParamField = AlkRequest::_POST("PARAM_FIELD", array());
    $tabParamType  = AlkRequest::_POST("PARAM_TYPE", array());
    $tabParamValue = AlkRequest::_POST("PARAM_VALUE", array());
    
    foreach ($tabParamField as $iField=>$param_field){
      $param_id    = (array_key_exists($iField, $tabParamId)    ? $tabParamId[$iField]    : -1);
      $param_value = (array_key_exists($iField, $tabParamValue) ? $tabParamValue[$iField] : "");
      $param_type  = (array_key_exists($iField, $tabParamType)  ? $tabParamType[$iField]  : "0");
      
      $strField = "PARAM_VTEXT";
      switch($param_type) {
      case "0": 
        $strField = "PARAM_VTEXT";
        $param_value = "'".$this->dbConn->analyseSql($param_value)."'";
        break;
      case "1": 
        $strField = "PARAM_VINT"; 
        $param_value = ( is_numeric($param_value) ? $param_value : 0 ); 
        break;
      case "2": 
        $strField = "PARAM_VDATE"; 
        $param_value = $this->dbConn->getDateFormat("DD/MM/YYYY", "'".$this->dbConn->analyseSql($param_value)."'"); 
        break;
      }
      
      if ($param_id==-1){
        $param_id = $this->dbConn->GetNextId("SIT_APPLI_PARAM", "PARAM_ID", "SEQ_SIT_APPLI_PARAM");
        $strSql = "insert into SIT_APPLI_PARAM (PARAM_ID, APPLI_ID, PARAM_FIELD, PARAM_TYPE,".$strField.")".
                  " values (".$param_id.
                  ",  ".$appli_id.
                  ", '".$this->dbConn->analyseSql($param_field)."'".
                  ", ".$this->dbConn->analyseSql($param_type).
                  ",  ".$param_value.")";
      }
      else {
        $strSql = "update SIT_APPLI_PARAM set ".$strField."=".$param_value.
                  " where PARAM_ID=".$param_id." and APPLI_ID=".$appli_id;
      }
      $this->dbConn->ExecuteSql($strSql);
    }
  }
  
  /**
   * Ajout d'une entrée dans la table donnée. Retourne true si ok, false sinon
   * La valeur de clé primaire est mise à jour sur l'attribut du formulaire associé
   * Retourne 0 si ko, un nombre ou une suite de nombres séparés par une virgule correspondant aux valeurs de clés primaires 
   * @param oForm       Référence sur le formulaire associé
   * @param tabQuery    Tableau de description des champs
   * @param strTable    nom de la table
   * @param tabIgnore   tableau contenant la liste des champs à ignorer, =array() par défaut
   * @param tabRang     tableau contenant les informations de rang pour ordonner l'information à ajouter, =array() par défaut
   * @param tabArbo     tableau contenant les informations de niveau, arbre, racine et pere pour gérer la hiérarchie en arbre, =array() par défaut
   * @return int
   */
  function addWithTabQuery(&$oForm, $tabQuery, $strTable, $tabIgnore=array(), $tabRang=array(), $tabArbo=array())
  {
    $tabPk = $tabQuery["pk"];
    $tabValue = $tabQuery["field"];
    
    $strDataId = "";
    // Initialisation des clés primaires
    foreach ($tabPk as $field=>$tabDesc){
      $lowerField = mb_strtolower($field);
      
      $property = $oForm->getProperty($lowerField);
      if (!is_null($property) && $property->value!=$property->defaultValue)
        $tabPk[$field][1] = $property->value;
        
      if ($tabDesc[0]==ALK_SQL_NUMBER && $tabPk[$field][1]==-1){
        $tabPk[$field][1] = $this->dbConn->getNextId($strTable, $field, "SEQ_".$strTable);
        
        $property = $oForm->getProperty($lowerField);
        if (!is_null($property))
          $property->value = $tabPk[$field][1];
      }
      $strDataId .= ($strDataId!="" ? "," : "").$tabPk[$field][1];
    }
    
    $tabValues = array_merge($tabPk, $tabValue);
    
    if ( !empty($tabArbo) ){
      $this->setHierarchyForQuery($tabValues, $strTable, $tabArbo);
    }    
    if ( !empty($tabRang) ){
      $this->setRangForQuery($tabValues, $strTable, $tabRang);
    }
    
  $strSql = "insert into ".$strTable.$this->getPartInsertSql($tabValues, $tabIgnore);
    if ( $this->executeSql($strSql) ){
      $this->indexData($strTable, $strDataId);
      return $strDataId;
    }
    return 0;
  }
  
  /**
   *  Mise à jour d'une entrée dans la table donnée
   * 
   * @param tabQuery    Tableau de description des champs
   * @param strTable    nom de la table
   */
  function updateWithTabQuery(&$oForm, $tabQuery, $strTable, $tabIgnore=array(), $tabRang=array(), $tabArbo=array())
  {
    $tabPk = $tabQuery["pk"];
    $tabValue = $tabQuery["field"];
    
    $tabValues = array_merge($tabPk, $tabValue);
    
    if ( !empty($tabArbo) ){
      $this->setHierarchyForQuery($tabValues, $strTable, $tabArbo);
    }    
    
    $strDataId = "";
    // Initialisation des clés primaires
    foreach ($tabPk as $field=>$tabDesc){
      $strDataId .= ($strDataId!="" ? "," : "").$tabDesc[1];
    }
    
    $strSet   = $this->getPartUpdateSql($tabValue, $tabIgnore);
    $strWhere = $this->getPartUpdateSql($tabPk, $tabIgnore, " and ");
    $strSql = "update ".$strTable." set ".$strSet." where ".$strWhere;    
    if ( $this->executeSql($strSql) ){
      $this->indexData($strTable, $strDataId);
      return true;
    }
    return false;
  }
  
  /**
   *  Suppression d'une entrée dans la table donnée
   * 
   * @param oFormDataPk   Objet ou tableau de FormData contenant l'identifiant de la donnée à supprimer
   * @param strTable    nom de la table
   * @param strWhere   Conditions supplémentaire (à commencer par and)
   */
  function delData($oFormDataPk, $strTable, $strWhere="")
  { 
    if ( !is_array($oFormDataPk) ){
      $oFormDataPk = array($oFormDataPk);
    }
    $strWherePk = "";
    foreach ($oFormDataPk  as $oFormData){
      $strWherePk .= ($strWherePk=="" ? "" : " and ").mb_strtoupper($oFormData->name)."=".$oFormData->value;
      $this->indexData($strTable, $oFormData->value, true);
    }
    $strSql = "delete from ".$strTable." where ".$strWherePk." ".$strWhere;
    
    return $this->executeSql($strSql);
  }
  
  /**
   *  Défini les propriétés d'arborescence d'une requête
   * 
   * @param & tabValues tableau des valeurs de la requête
   * @param strTable    nom de la table
   * @param tabArbo    tableau de définition de l'arborescence. Clés attendues : "field_id", "field_pere", "field_niveau", "field_racine", "field_arbre"
   */
  protected function setHierarchyForQuery(&$tabValues, $strTable, $tabArbo)
  {
    $tabKeys = array("field_id", "field_pere", "field_niveau", "field_racine", "field_arbre");
    $keys = array_keys($tabArbo);
    $tabDiff = array_diff($tabKeys, $keys);
    if ( !empty($tabDiff) ){
      $this->triggerError("class ".__CLASS__."::".__FUNCTION__."<br>Le tableau de description des propriétés d'arborescence devrait contenir les clés suivantes : ".implode(", ", $tabKeys), E_USER_ERROR);
    } 
    $field_id   = strtoupper($tabArbo["field_id"]);
    $field_pere = strtoupper($tabArbo["field_pere"]);
    if ( array_key_exists($field_id, $tabValues) && array_key_exists($field_pere, $tabValues) ){
      $id   = $tabValues[$field_id][1];
      $pere = $tabValues[$field_pere][1];
      
      $field_niveau       = strtoupper($tabArbo["field_niveau"]);
      $field_racine       = strtoupper($tabArbo["field_racine"]);
      $field_arbre        = strtoupper($tabArbo["field_arbre"]);
      // niveau auquel, le champ racine prend la valeur de la clé primaire, sinon prend la valeur racine du parent, =1 par défaut
      $iNivRacine   = ( isset($tabArbo["iNivRacine"]) 
                        ? $tabArbo["iNivRacine"]
                        : 1 );
      
      $strSql = "select ".$field_niveau.", ".$field_arbre.
        ( $field_racine != ""       ? ", ".$field_racine : "" ).
        " from ".$strTable.
        " where ".$tabArbo["field_id"]."=".$pere;
      $dsArbo = $this->getDs($strSql);
      if( $drArbo = $dsArbo->fetch() ) {
        $arbre = "";
        $niveau = $drArbo[$field_niveau];
        $arbre  = $drArbo[$field_arbre];
        $racine = ( $field_racine=="" 
                    ? "0" 
                    : $drArbo[$field_racine]);
        
        $niveau++;
        $arbre .= ($arbre == "" ? "-" : "" ).$id."-";
        $racine = ( $niveau == $iNivRacine ? $id : $racine );
        
        $tabValues[$field_niveau] = array(ALK_SQL_NUMBER, $niveau);
        if ( $field_racine!="" )
          $tabValues[$field_racine] = array(ALK_SQL_NUMBER, $racine);
        $tabValues[$field_arbre]  = array(ALK_SQL_TEXT, $arbre);
      }else{
        $tabValues[$field_niveau];
        $tabValues[$field_arbre] = array(ALK_SQL_TEXT, "-".$pere."-".$id."-");
      }
    }    
  }
  
  /**
   *  Met à jour le rang de la donnée
   * 
   * @param & tabValues tableau des valeurs de la requête
   * @param strTable    nom de la table
   * @param tabRang     tableau de définition des propriétés de rang. Clés attendues : "field_pere", "field_rang"
   */
  protected function setRangForQuery(&$tabValues, $strTable, $tabRang)
  {
    $tabKeys = array("field_pere", "field_rang"); 
    $keys = array_keys($tabRang);
    $tabDiff = array_diff($tabKeys, $keys);
    if ( !empty($tabDiff) ){
      $this->triggerError("class ".__CLASS__."::".__FUNCTION__."<br>Le tableau de description des propriétés de mise à jour des rang devrait contenir les clés suivantes : ".implode(", ", $tabKeys), E_USER_ERROR);
    } 
    $field_rang = mb_strtoupper($tabRang["field_rang"]);
    $field_pere = mb_strtoupper($tabRang["field_pere"]);
    if ( array_key_exists($field_rang, $tabValues) && array_key_exists($field_pere, $tabValues) ){
      
      $pere = $tabValues[$field_pere][1];
      $rang = $tabValues[$field_rang][1];
      
      if ( $rang!="0" )
        $this->dbConn->updateRank($strTable, $field_rang, $rang, true, $field_pere."=".$pere);
      unset($tabValues[$field_rang]);
    }
  }
    
  /**
   *  Retourne un tableau des parties de requete permettant d'obtenir la liste des pièces jointes
   * 
   * @param tableName     Nom de la table source de la donnée permettant d'identifier le type de données trait
   * @param strAlias      Alias de la table source de la donnée permettant d'identifier le type de données trait
   * @param strFieldPk    Champ de clé primaire de la table source de la donnée permettant d'identifier le type de données trait
   *
   * @return array : dont les clés sont : "select", "from", "join"
   */
  protected function getTabPj($tableName, $strAlias, $strFieldPk)
  {
    $tabPj["select"]  = " pj.PJ_ID, pj.PJ_FILE as filename,".
      "  pj.PJ_DATECREA as pj_date_crea, pj.PJ_TAILLE, pj.PJ_NAME as filename_aff, pj.PJ_LANG, pj.PJ_RANG ";
    //$tabPj["from"]    = " (select * from DATA_PJ where TABLE_NAME='".$tableName."') pj ";
    $tabPj["from"]    = " from ".$tableName." pj ";
    $tabPj["join"]    = " (".$strAlias.".".$strFieldPk."=pj.DATA_ID) ";
    return $tabPj;
  }
  
  /**
   *  Ajoute une pièce jointe à une information donnée
   *  Retourne un int, 0 pour ko, >0 pour ok correspondant à pj_id
   * 
   * @param tableName     Nom de la table où la pièce jointe doit être enregistrée
   * @param data_id       Identifiant de la donnée traitée
   * @param champName     Nom du champ de formaulaire contenant la valeur à uploader
   * @param agent_id      Agent effectuant l'ajout
   * @param strPath       Chemin racine des répertoires
   * @param strDirUpload  Chemin d'accès au répertoire upload
   * @param lg_id         Langue utilisée
   * @param bUploadCtrl   Indique que l'on vient d'un controle de type upload et non d'un controle de type file
   * @param strFileName   chaine vide par défaut pour effectuer l'upload
   *                      sinon, l'upload est déjà effectué et la variable contient le chemin et nom du fichier à enregistrer en base
   * @param bVersionning  (default false) True si on gère le versionning des fichiers dans upload
   * @return int
   */
  function AddPj($tableName, $data_id, $champName, $agent_id, $strPath, $strDirUpload, $lg_id, $bUploadCtrl=true, $strFileName="", $bVersionning=false)
  {
    $bMaj = false;
    //Cas du data_id de type varchar encadré avec des cotes
    //on supprime les cotes pour le nom du fichier joint
    $data_id_clean = ( mb_substr($data_id,0,1)=="'" ? mb_substr($data_id,1,mb_strlen($data_id)-2) : $data_id );

    //upload du fichier
    $newFileName = "";
    $oldFilePath = "";
    if( $strFileName == "" ) {
      $bFtp = false;
      if( ALK_B_UPLOAD_FTP == true ) {
        $strFileNamePost = AlkRequest::_POST(($bUploadCtrl ? "files_" : "").$champName, "");
        if( $strFileNamePost != "" ) {
          // retire le préfixe : [agent_id]_, ajoute celui prévu et vérifie le nom
          $bFtp = true;
          $oldFilePath = ALK_ROOT_PATH.ALK_UPLOAD_FTP_PATH.$strFileNamePost;
          $strFileName = verifyFileName(mb_substr($strFileNamePost, mb_strlen("".$agent_id."")+1));
  
        }
      }
      if( !$bFtp ) {
        $strFileName = doUpload(($bUploadCtrl ? "file_" : "").$champName, $data_id_clean."_", $strDirUpload);
        $oldFilePath = $strPath.$strDirUpload.$strFileName;
        $strFileName = mb_ereg_replace("^".$data_id_clean."_", "", $strFileName);
      }
    } else {
      // renomme le fichier avec les identifiants data_id et pj_id
      $oldFilePath = $strPath.$strDirUpload.$strFileName;
    }
    
    // si echec de récupération du nom de fichier sort
    if( !(is_string($strFileName) && $strFileName!="") ) 
      return 0;
    
    $pj_id = -1;
    $bUpdatePj = false;
    
    // récupération de l'identifiant de pièce jointe en gestion de version
    if ( $bVersionning ){
      $strSql = "select PJ_ID from ".$tableName.
                " where PJ_CHAMP='".$this->dbConn->AnalyseSql($champName)."'" .
                " and DATA_ID=".$data_id.
                " and PJ_NAME='".$this->dbConn->AnalyseSql($strFileName)."'";
      $dsExists = $this->dbConn->initDataset($strSql);
      if ( $drExists = $dsExists->fetch() ){
        $pj_id = $drExists["PJ_ID"];
        $bUpdatePj = true;
      }
    }
    if ( !$bUpdatePj )
      $pj_id = $this->dbConn->GetNextId($tableName, "PJ_ID", "SEQ_DATA_PJ");
    
    $prefixe = $data_id_clean."_".$pj_id."_";
    
    
    // Versionning : récupération du dernier numéro de version déposé sur le serveur
    if( is_string($strFileName) && $strFileName!="" ) {
      if ( $bVersionning ){
        $iVersion = 1;
        while ( file_exists($strPath.$strDirUpload."v".$iVersion."_".$prefixe.$strFileName) ) $iVersion++;
        $prefixe = "v".$iVersion."_".$prefixe;
      }
    }
    
    $strOldFileName = $strFileName;
    // renommage du fichier défini par oldFilePath vers le nouveau nom du fichier avec les préfixes
    if ( $oldFilePath!="" ){
      $bRes = verifyFileName($prefixe.$strFileName) && @rename($oldFilePath, $strPath.$strDirUpload.$prefixe.$strFileName); 
      if( !$bRes ) {
        $strFileName = false;
      } else {
        $strFileName = $prefixe.$strFileName;  
      }
    } else if ( is_string($strFileName) && $strFileName!="" ){
      $strFileName = $prefixe.$strFileName;  
    }
    
    
    if( is_string($strFileName) && $strFileName!="" ) {
      // création des images redim en fonction du tableau tabImageSize de app_conf_alkanet
      if ( function_exists("resizeImage") ){
        resizeImage($strFileName, $strPath.$strDirUpload);
      }
      
      // initialise l'IPTC manager
      // ne récupère les métadonnées IPTC que pour les champs avec un code IPTC
      $bGetIptc = false;
      // tableau de définition des champs détaillés des pièces jointes
      if ( isset($GLOBALS["tabPJDetails"]) && is_array($GLOBALS["tabPJDetails"]) && !empty($GLOBALS["tabPJDetails"]) ) {
        $iptcManager = AlkFactory::getIptc();
        if ( $iptcManager->setFile($strPath.$strDirUpload.$strFileName) > 0 ) {
          $bGetIptc = true;
        }
      }
      // métadonnées ffmpeg
      $bGetWith_ffmpeg = false;
      if ( isFileMultimedia($strFileName) ) {
        $tabPJMpegMetadata = array(
          "title"       => array("field" => "pj_title"),
          "desc"        => array("field" => "pj_desc"),
          "copyright"   => array("field" => "pj_copyright"),
          "auteur"      => array("field" => "pj_auteur"),
          "duration"    => array("field" => "pj_duration"),
          "bitrate"     => array("field" => "pj_bitrate"),
        );
        $tabMpegMetadata = getMpegMetadata($strPath.$strDirUpload.$strFileName);
        if ( !empty($tabMpegMetadata) ) {
          foreach ( $tabPJMpegMetadata as $name => $tabField ) {
            if ( array_key_exists($name, $tabMpegMetadata) ) {
              $tabPJMpegMetadata[$name] = array_merge($tabMpegMetadata[$name], $tabField);
            } else {
              unset($tabPJMpegMetadata[$name]);
            }
          }
          $bGetWith_ffmpeg = true;
        }
      }
      
      // taille du fichier : Attention, cette fonction plante si le fichier > 2Go
      $iSize = @filesize($strPath.$strDirUpload.$strFileName);

      //Récupère le rang le plus élévé parmi les PJ associées à la même data_id
      $strSqlPJRang = " select max(PJ_RANG) from ".$tableName." where DATA_ID = ".$data_id." AND PJ_CHAMP = '".$this->dbConn->AnalyseSql($champName)."' ";
      $pjRang = $this->dbConn->getScalarSql($strSqlPJRang, -1);
      $pjRang++;
      
      //Récupération de PJ_BROUILLON ( NULL si on est sur la PJ liée au fichier PUBLIEE, sinon vaut l'id de la PJ liée au brouillon)
      $pjBrouillon = "NULL";
              
      //TODO : Définition de pj_iv
      $pjIV = "''";
              
      if ( !$bUpdatePj ){
        // enregistre dans la base
        $strSql = "insert into ".$tableName." ".
            "(PJ_ID, PJ_BROUILLON, PJ_CHAMP, DATA_ID, PJ_NAME, PJ_FILE, PJ_TYPE, PJ_TAILLE, PJ_LANG, PJ_DATECREA, PJ_AGENTCREA, PJ_RANG, PJ_IV ";
        if ( $bGetIptc ) {  // traitement des métadonnées IPTC
          foreach ( $GLOBALS["tabPJDetails"] as $tabFieldInfo ) {
              if ( $tabFieldInfo["field"] != "" && $tabFieldInfo["code_iptc"] != "" ) {
              $strSql.= ", ".strtoupper($tabFieldInfo["field"]);
            }
          }
        } else if ( $bGetWith_ffmpeg ) {
          foreach ( $tabPJMpegMetadata as $tabFieldInfo ) {
              if ( $tabFieldInfo["field"] != "" ) {
              $strSql.= ", ".strtoupper($tabFieldInfo["field"]);
            }
          }
        }
        $strSql.= ") values (".
                  $pj_id.
            ", ". $pjBrouillon.
            ", '".$this->dbConn->AnalyseSql($champName)."'".
            ", ". $data_id.
            ", '".$this->dbConn->AnalyseSql(mb_ereg_replace("^".$prefixe, "", $strFileName))."'".
            ", '".$this->dbConn->AnalyseSql($strFileName)."'".
            ", ". getTypeFile($strFileName).
            ", ". $iSize.
            ", ". $lg_id.
            ", ". $this->dbConn->GetDateCur().
            ", ". $agent_id.
            ", ". $pjRang.
            ", ". $pjIV;
        if ( $bGetIptc ) {
          foreach ( $GLOBALS["tabPJDetails"] as $tabFieldInfo ) {
             if ( $tabFieldInfo["field"] != "" && $tabFieldInfo["code_iptc"] != "" ) {
              $value = $iptcManager->getByCode($tabFieldInfo["code_iptc"]);
              $value = ( is_string($value) ? $value : ( isset($tabFieldInfo["value"]) ? $tabFieldInfo["value"] : "" ) );
             switch ( $tabFieldInfo["type"] ) {
                case "date10" :
                  $value = ( $value != "" ? $this->dbConn->getDateFormat("YYYYMMDD", "'".$value."'") : "NULL" );
                break;
                case "int" :
                  null;
                break;
                case "text" :
                default :
                  $value = "'".$this->dbConn->AnalyseSql($value)."'";
                break;
              }
              $strSql.= ", ".$value;
            }
          }
        } else if ( $bGetWith_ffmpeg ) {
          foreach ( $tabPJMpegMetadata as $tabFieldInfo ) {
             if ( $tabFieldInfo["field"] != "" ) {
              $value = ( isset($tabFieldInfo["value"]) ? $tabFieldInfo["value"] : "" );
             switch ( $tabFieldInfo["type"] ) {
                case "date10" :
                  $value = ( $value != "" ? $this->dbConn->getDateFormat("YYYYMMDD", "'".$value."'") : "NULL" );
                break;
                case "int" :
                case "float" :
                  null;
                break;
                case "text" :
                default :
                  $value = "'".$this->dbConn->AnalyseSql($value)."'";
                break;
              }
              $strSql.= ", ".$value;
            }
          }
        }
        $strSql.= ")";
      } else {
        //mise à jour des propriété du fichier sous sa nouvelle version
        // @TODO MTO : à vérifier pour prise en compte des métadonnées IPTC
        $strSql = "update ".$tableName." set ".
            "  PJ_FILE='".$this->dbConn->AnalyseSql($strFileName)."'".
            ", PJ_TYPE=". getTypeFile($strFileName).
            ", PJ_TAILLE=". $iSize.
            ", PJ_DATECREA=". $this->dbConn->GetDateCur();
        if ( $bGetIptc ) {
          foreach ( $GLOBALS["tabPJDetails"] as $tabFieldInfo ) {
            if ( $tabFieldInfo["field"] != "" && $tabFieldInfo["code_iptc"] != "" ) {
              $value = $iptcManager->getByCode($tabFieldInfo["code_iptc"]);
              $value = ( is_string($value) ? $value : ( isset($tabFieldInfo["value"]) ? $tabFieldInfo["value"] : "" ) );
              switch ( $tabFieldInfo["type"] ) {
                case "date10" :
                  $value = $this->dbConn->getDateFormat("YYYYMMDD", "'".$value."'");
                break;
                case "int" :
                  null;
                break;
                case "text" :
                default :
                  $value = "'".$this->dbConn->AnalyseSql($value)."'";
                break;
              }
              $strSql.= ", ".strtoupper($tabFieldInfo["field"])."=".$value;
            }
          }
        } else if ( $bGetWith_ffmpeg ) {
          foreach ( $tabPJMpegMetadata as $tabFieldInfo ) {
            if ( $tabFieldInfo["field"] != "" ) {
              $value = ( isset($tabFieldInfo["value"]) ? $tabFieldInfo["value"] : "" );
              switch ( $tabFieldInfo["type"] ) {
                case "date10" :
                  $value = $this->dbConn->getDateFormat("YYYYMMDD", "'".$value."'");
                break;
                case "int" :
                case "float" :
                  null;
                break;
                case "text" :
                default :
                  $value = "'".$this->dbConn->AnalyseSql($value)."'";
                break;
              }
              $strSql.= ", ".strtoupper($tabFieldInfo["field"])."=".$value;
            }
          }
        }
       $strSql.= " where PJ_CHAMP='".$this->dbConn->AnalyseSql($champName)."'" .
            " and DATA_ID=".$data_id.
            " and PJ_NAME='".$this->dbConn->AnalyseSql($strOldFileName)."'";
      }
      $bMaj = $this->executeSql($strSql, false);
    }
    
    $oSearchSolr = $this->initSearcherSolr();
    if ( !is_null($oSearchSolr) ){
      if ( $data_id_clean != -1 ) {
        // index les pièces-jointes associées à une donnée uniquement
        $oSearchSolr->AddPj($pj_id, $tableName, $data_id, $champName, $agent_id, $strPath, $strDirUpload, $strFileName, $strOldFileName, $lg_id, $bUploadCtrl, $bVersionning);
      }
    }
    return ( $bMaj ? $pj_id : 0 );
   }
  
  /**
   * Modifie les métadonnées d'une pièce jointe
   * Essaie de sauvegarder les métadonnées IPTC correspondant dans le fichier
   * préconditions : $tableName est le nom d'une table existante en base de données, $pj_id est l'identifiant d'une pièce jointe existante dans cette table
   * postconditions : les métadonnées sont modifiées pour la pièce jointe, elles sont stockées au format IPTC dans le fichier si cela est possible
   * @param tableName       Nom de la table où la pièce jointe doit être modifiée
   * @param pj_id           tableau des identifiants des la pièces jointes à mettre à jour
   * @param dirUpload       Nom du répertoire d'upload du fichier (optionnel, utile pour l'écriture des métadonnées IPTC dans le fichier)
   * @param tabFields       tableau contenant les informations des champs en plus de leurs valeurs, les champs en readOnly ne sont pas modifiés
   */
function UpdatePjDetails($tableName, $pj_id, $dirUpload="", $tabFields=array())
  {
    $bMaj = true;
    
    if ( is_array($tabFields) && !empty($tabFields) ) {
    
      $tabIptc = array();
      
      // construit la requête de mise à jour
      $strSql = "update ".$tableName." set ";
      foreach ( $tabFields as $tabFieldInfo ) {
        if ( !$tabFieldInfo["readOnly"] ) {
          $value = ( isset($tabFieldInfo["value"]) ? $tabFieldInfo["value"] : "" );
          switch ( $tabFieldInfo["type"] ) {
            case "date10" :
              if ($value!=""){
                $tabDate = explode("/", $value);
                $tabIptc[$tabFieldInfo["code_iptc"]] = $tabDate[2].$tabDate[1].$tabDate[0];                
              } else {
                $tabIptc[$tabFieldInfo["code_iptc"]] = "";
              }
              $value = $this->dbConn->getDateFormat("DD/MM/YYYY", ( $value != "" ? "'".$value."'" : "null" ));
            break;
            case "int" :
               $tabIptc[$tabFieldInfo["code_iptc"]] = $value;
            break;
            case "text" :
            default :
              $tabIptc[$tabFieldInfo["code_iptc"]] = $value;
              $value = "'".$this->dbConn->AnalyseSql($value)."'";
            break;
          }
          $strSql.= strtoupper($tabFieldInfo["field"])."=".$value.", ";
        }
      }
      $strSql = substr($strSql, 0, -2)." where PJ_ID in (".implode(", ", $pj_id).")";
      
      $bMaj = $this->executeSql($strSql, false);
      
      // sauvegarde les métadonnées IPTC
      if ( $dirUpload && !empty($tabIptc) ) {
        // récupère le nom du fichier
        $strSql = "select PJ_FILE from ".$tableName." where PJ_ID in (".implode(", ", $pj_id).")";
        
        $iptcManager = AlkFactory::getIptc();
        $dsPJ = $this->dbConn->initDataSet($strSql);
        while ( $drPJ = $dsPJ->fetch() ) {
          $pj_file = $drPJ["PJ_FILE"];
          
          if ( $iptcManager->setFile(ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD.$dirUpload."/".$pj_file) > 0 ) {
            $iptcManager->setByCode($tabIptc);
          }
        }
      }
      
      // réindexe les pièces-jointes
      $oSearchSolr = $this->initSearcherSolr();
      if ( !is_null($oSearchSolr) ){
        $strSql = "select PJ_ID, DATA_ID from ".$tableName." where PJ_ID in (".implode(", ", $pj_id).")";
        $dsPJ = $this->dbConn->initDataSet($strSql);
        while ( $drPJ = $dsPJ->fetch() ) {
          $_pj_id  = $drPJ["PJ_ID"];
          $data_id = $drPJ["DATA_ID"];
          $oSearchSolr->updatePj($_pj_id, $data_id);
        }
      }
    }
    
    return $bMaj;
  }
  
  /**
   * Effectue la suppression d'une pièce jointe
   * 
   * @param pj_id         Identifiant de la pièce jointe
   * @param strFileName   Nom de la pièce jointe
   * @param strPath       Chemin racine des répertoires
   * @param strDirUpload  Chemin d'accès au répertoire upload
   * @param bVersionning  (default false) True si on gère le versionning des fichiers dans upload
   * @return boolean : true OK (pièce jointe supprimée de la table de référencement)
   *                   false KO
   */
  function DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload, $bVersionning=false)
  {
    $oSearchSolr = $this->initSearcherSolr();
    
    $strFileRadical = getFileRadical($strFileName);
    $strFileRadical = ( $strFileRadical == "" 
                        ? $strFileName
                        : $strFileRadical."*" );
	
    //On diminue le rang de toutes les Pj ayant un rang supérieur à la pj que l'on va supprimer et étant associées au même data_id
    $strSqlRangPjToDel = " select PJ_RANG, DATA_ID, PJ_CHAMP from ".$tableName." where PJ_ID=".$pj_id;
    $oDsPJRang = $this->dbConn->initDataSet($strSqlRangPjToDel);
    $data_id_pj_del = -1;
    $pj_rang_del = -1;
    $pj_champ_del = "";
    if( $oDsPJRang != null && $oDrPJRang = $oDsPJRang->fetch() ){
      $pj_rang_del = $oDrPJRang["PJ_RANG"];
      $data_id_pj_del = $oDrPJRang["DATA_ID"];
      $pj_champ_del = $oDrPJRang["PJ_CHAMP"];
    }
    
    if( $data_id_pj_del != -1 && $pj_rang_del > -1 && $pj_champ_del != "" ){
      $strSqlUpdateRang = " update ".$tableName." set PJ_RANG = PJ_RANG -1 where PJ_RANG > ".$pj_rang_del." and DATA_ID = ".$data_id_pj_del." AND PJ_CHAMP = '".$pj_champ_del."' ";
      $this->executeSql($strSqlUpdateRang, false);
    }
    
    // suppression dans la base de la piece jointe numero $i
    $strSql = "delete from ".$tableName." where PJ_ID=".$pj_id;
    $iRes = $this->executeSql($strSql, false);
    
    // suppression du fichier + déclinaisons
    delFile($strPath.$strDirUpload.$strFileRadical);
    
    // suppression de toutes les versions du fichier, si versionning activé
    $matches = array();
    if ( $bVersionning && preg_match("!^v(\d+)_(\d+_".$pj_id."_)(.+)$!", $strFileName, $matches) ){
      $iVersionCur = $matches[1];
      $prefixe = $matches[2];
      $strRadicalName = $matches[3];
      $iVersion = $iVersionCur-1;
      while (  file_exists($strPath.$strDirUpload."v".$iVersion."_".$prefixe.$strRadicalName) && $iVersion>0 ){
        delFile($strPath.$strDirUpload."v".$iVersion."_".$prefixe.$strRadicalName);
        $iVersion--;
      }
    }
    
    //suppression de l'indexation de ce fichier dans moteur de recherche
    if ( !is_null($oSearchSolr) ){
      $oSearchSolr->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload, $bVersionning);
    }
    
    return ($iRes == 1);
  }
  
  /**
   * Supprime la dernière version d'une pièce jointe et la remplace par la version précédente si existe sinon supprime la PJ
   * 
   * @param strPath       Chemin racine des répertoires
   * @param strDirUpload  Chemin d'accès au répertoire upload
   * @return boolean : mise à jour effectuée
   */
  function DelVersionPj($tableName, $strPath, $strDirUpload)
  {    
    $bMaj = false;
    
    $strFileName = AlkRequest::getToken("filename");
    $pj_id       = AlkRequest::getToken("pj_id", "-1");
    
    if ( preg_match("!^v(\d+)_(\d+_".$pj_id."_)(.+)$!", $strFileName, $matches) ){
      $iVersionCur = $matches[1];
      $prefixe = $matches[2];
      $strRadicalName = $matches[3];
      $iVersion = $iVersionCur-1;
      
      // recherche du fichier de version précédente
      while (  !file_exists($strPath.$strDirUpload."v".$iVersion."_".$prefixe.$strRadicalName) && $iVersion>0 ){
        $iVersion--;
      }
      
      if ( $iVersion==0 ){// suppression de la pj
        $strSql = "delete from ".$tableName." where PJ_ID=".$pj_id;
        $this->executeSql($strSql);
    
        // suppression du fichier
        delFile($strPath.$strDirUpload.$strFileName);
      } 
      else {// remplacement par la précédente version
        $newFileName = "v".$iVersion."_".$prefixe.$strRadicalName;
        // taille du fichier : Attention, cette fonction plante si le fichier > 2Go
        $iSize = @filesize($strPath.$strDirUpload.$newFileName);
        $dateCrea = date("d/m/Y H:i", filemtime($strPath.$strDirUpload.$newFileName));
        $strSql = "update ".$tableName." set" .
                  "  PJ_FILE='".$this->dbConn->AnalyseSql($newFileName)."'".
                  ", PJ_TYPE=". getTypeFile($newFileName).
                  ", PJ_TAILLE=". $iSize.
                  ", PJ_DATECREA=". $this->dbConn->getDateFormat("DD/MM/YYYY HH:MI", "'".$dateCrea."'").
                  " where PJ_ID=".$pj_id;
                  
        $this->executeSql($strSql);
        // suppression du fichier
        delFile($strPath.$strDirUpload.$strFileName);
      }
      $bMaj = true;
    }
    return $bMaj;
  }
  
  /**
   *  Supprime une ou des pièces jointes à une information donnée
   * 
   * @param tableName     Type de la donnée de référence
   * @param data_id       Identifiant de la donnée de référence ou liste d'identifiants séparés par une virgule ou requête de sélection du/des data_id
   * @param strPath       Chemin racine des répertoires
   * @param strDirUpload  Chemin d'accès au répertoire upload
   * @param strField      Champ associé au PJ à supprimer
   * @param bVersionning  (default false) True si on gère le versionning des fichiers dans upload
   * @return boolean : true OK (toutes les pièces jointes supprimées de la table de référencement)
   *                   false KO
   */
  function DelPjByDataId($tableName, $data_id, $strPath, $strDirUpload, $strField="", $bVersionning=false)
  { 
    $bRes = true;
    $dsPj = $this->getDsListePJ($tableName, $data_id, $strField);
    while ( $drPj = $dsPj->fetch() ){
      $pj_id = $drPj["ID"];
      $strFileName = $drPj["FILENAME"];
      $bRes &= $this->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload, $bVersionning, $strField);
      $bRes &= $this->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload."visuel/", $bVersionning, $strField);
      $bRes &= $this->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload."visuel/".ALK_ROOT_UPLOAD_DECLINAISONS, $bVersionning, $strField);
      $bRes &= $this->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload."pj/", $bVersionning, $strField);
      $bRes &= $this->DelPj($pj_id, $strFileName, $tableName, $strPath, $strDirUpload."pj/".ALK_ROOT_UPLOAD_DECLINAISONS, $bVersionning, $strField);
    }
    return $bRes;
  }
  
  /**
   *  retourne la liste des pièces jointes enregistrées pour une information donnée
   * 
   * @param tableName   Nom de la table source de la donnée permettant d'identifier le type de données trait
   * @param data_id     Identifiant de la donnée traitée ou liste d'identifiants séparés par une virgule ou requête de sélection du/des data_id
   * @param champName   Nom du champ de formulaire listant les pièces jointes
   * @return dataset    Liste des pièces jointes d'une donnée
   */
  function getDsListePJ($tableName, $data_id, $champName="", $alias_file="", $order_by="")
  {
    $strSql = "select DATA_ID, PJ_ID as id, PJ_FILE as ".($alias_file=="" ? "filename" : $alias_file."_FILE").
      ", ag.AGENT_MAIL as auteur_mail" .
      ", PJ_NAME as ".($alias_file=="" ? "filename_aff" : $alias_file).
      ",".$this->dbConn->GetDateFormat("DD/MM/YYYY, HH:MI", "PJ_DATECREA", false)." as date_crea, " .
      "PJ_DATECREA, PJ_TAILLE, PJ_DURATION, PJ_BITRATE, PJ_LANG as lg_id, PJ_CHAMP, PJ_RANG";
    
    // tableau de définition des champs détaillés des pièces jointes
    if ( isset($GLOBALS["tabPJDetails"]) && is_array($GLOBALS["tabPJDetails"]) && !empty($GLOBALS["tabPJDetails"]) ) {
      foreach ( $GLOBALS["tabPJDetails"] as $tabFieldInfo ) {
         if ( $tabFieldInfo["field"] != "" ) {
          switch ( $tabFieldInfo["type"] ) {
            case "date10" :
              $field = $this->dbConn->getDateFormat("DD/MM/YYYY", $tabFieldInfo["field"], false)." as ".$tabFieldInfo["field"];
            break;
            default :
              $field = $tabFieldInfo["field"];
            break;
          }
          $strSql.= ", ".$field;
        }
      }
    }
    
    $strSql.= " from ".$tableName." pj " .
      " inner join SIT_AGENT ag on (ag.AGENT_ID=pj.PJ_AGENTCREA)".
      " where DATA_ID in (".$data_id.") ".
      ($champName!="" ? "and PJ_CHAMP='".$this->dbConn->AnalyseSql($champName)."'" : "").
      ($order_by!="" ? " order by ".$order_by : " order by PJ_RANG ASC, PJ_DATECREA");
    return $this->getDs($strSql);
  }
  

  /**
   *  Retourne la fiche du fichier joint avec les droits de l'agent connecté
   *
   * @param data_id Identifiant de la donnée
   * @param pj_id  Identifiant du fichier joint
   * @return Retourne un dataSet
   */
  function getDsPjForDownload($tableName, $agent_id, $pj_id="-1", $data_id="-1")
  {
    $iDroit = ($pj_id==-1 && $data_id==-1 ? 0 : 1);
    $strSql = "select PJ_ID, PJ_FILE as filename, ".$iDroit." as droit_id, PJ_NAME as filename_aff".
      " from ".$tableName." ".
      " where";
    $strGlue = "";
    if ( $data_id!=-1 ){
      $strSql .= $strGlue." DATA_ID in (".$data_id.") ";
      $strGlue = " and ";
    }
    if( $pj_id!="-1" ) {
      $strSql .= $strGlue." PJ_ID=".$pj_id;
      $strGlue = " and ";
    }

    return $this->getDs($strSql);
  }
  

  /**
   *  Enregistre le compteur de visite sur le fichier
   *
   * @param pj_id    Identifiant du fichier
   * @param agent_id Identifiant de l'agent
   */
  function LogDownloadPj($pj_id, $agent_id)
  {
    // attention : risque de doublon
    $strSql = "delete from DATA_PJDOWNLOAD where PJ_ID=".$pj_id.
      " and AGENT_ID=".$agent_id." and DOWNLOAD_DATE=".$this->dbConn->GetDateCur();
    $this->executeSql($strSql);

    $strSql = "insert into DATA_PJDOWNLOAD (PJ_ID, AGENT_ID, DOWNLOAD_DATE) values (".$pj_id.
      ", ".$agent_id.
      ", ".$this->dbConn->GetDateCur().
      ")";
    $this->executeSql($strSql);
  }
  
  /**
   * Change le rang des pièces jointes soeurs de la pièce jointe déplacé puis celui de la pièce jointe que l'on déplace
   * @param string tableName      Nom de la table source de la donnée permettant d'identifier le type de données trait
   * @param int    $data_id       Identifiant de la donnée qui possède la pièce jointe qui a été déplacé
   * @param int    $pj_id         Identifiant de la pièce jointe qui a été déplacé
   * @param int    $pj_rang_start Indice du rang de la pièce jointe avant déplacement
   * @param int    $pj_rang_end   Indice du rang de la pièce jointe après déplacement
   * @param string $pj_champ      Nom du type de la pj dans la table
   */
  public function updatePJRang($tableName, $data_id, $pj_id, $pj_rang_start, $pj_rang_end, $pj_champ){
        
    $strSql = "";
    
    $bOK = $this->dbConn->initTransaction();
    $tabSql = array();
    
    if($pj_rang_start < $pj_rang_end){
      //on diminue le rang de toutes les pages dont la page parent est $data_id et PJ_RANG <= $pj_rang_end et PJ_RANG > $pj_rang_start
      $tabSql[] = " UPDATE ".$tableName."  SET PJ_RANG = PJ_RANG - 1 WHERE DATA_ID = ".$data_id." AND PJ_ID <> ".$pj_id." AND PJ_RANG <= ".$pj_rang_end." AND PJ_RANG > ".$pj_rang_start." AND PJ_CHAMP = '".$pj_champ."' ";
    }else {
      //on augmente le rang de toutes les pièce jointes dont la donnée est $data_id et PJ_RANG >= $pj_rang_end et PJ_RANG < $pj_rang_start
      $tabSql[] = " UPDATE ".$tableName."  SET PJ_RANG = PJ_RANG + 1 WHERE DATA_ID = ".$data_id." AND PJ_ID <> ".$pj_id." AND PJ_RANG > ".$pj_rang_end." AND PJ_RANG < ".$pj_rang_start." AND PJ_CHAMP = '".$pj_champ."' ";
    }
    
    if($pj_rang_start < $pj_rang_end){
      $tabSql[] = " UPDATE ".$tableName."  SET PJ_RANG = ".$pj_rang_end." WHERE PJ_ID = ".$pj_id." ";
    }else{
      $tabSql[] = " UPDATE ".$tableName."  SET PJ_RANG = ".$pj_rang_end."+1 WHERE PJ_ID = ".$pj_id." ";
    }
    foreach($tabSql as $strSql){
      $bOK = $bOK && $this->dbConn->executeSql($strSql);
    }
    ($bOK ? $this->dbConn->commitTransaction() : $this->dbConn->rollBackTransaction() );
  }
  
  /**
   * 
   *
   * @param iMode       1=ajout, 2=modif, 3=suppr
   * @param strTable    Nom de la table où collecter les informations à indexer
   * @param tabFields   Tableau contenant la liste des champs de la table à analyser
   * @param tabFiles    Tableau contenant la liste des fichiers associés à l'information
   * @param strFieldPk  Nom du champ clé primaire
   * @param idFieldPk   Valeur de la clé primaire ou liste de valeurs séparées par une virgule
   * @param atype_id    Type de l'application contenant l'information à indexer
   * @param appli_id    Identifiant de l'application contenant l'information à indexer
   * @param datatype_id Type de l'information à indexer
   */
  protected function setIndex($iMode, $strTable, $tabFields, $tabFiles, $strFieldPk, $idFieldPk, $atype_id, $appli_id, $datatype_id)
  {
    if( !(defined("ALK_B_ATYPE_SEARCH") && ALK_B_ATYPE_SEARCH==true) ||
        $appli_id == -1 ||
        !($this->oQuerySearch!=null && $this->oQuerySearchAction!=null) )
      return;

    // suppression avant insertion
    $this->oQuerySearchAction->delData($atype_id, $appli_id, $datatype_id, $idFieldPk);
    
    if( $iMode == 3 ) {
      // mode suppression, on quitte maintenant
      return;
    }

    require_once(ALK_ALKANET_ROOT_PATH."classes/indexer/alkwordindexer.class.php");
    $oWordIndexer = new AlkWordIndexer("fr");

    $strDoc = "";
    $strSql = "select * from ".$strTable." where ".$strFieldPk." in (".$idFieldPk.")";
    $oDsData = $this->GetDs($strSql);
    while( $oDrData = $oDsData->GetRowIter() ) {
      $strDoc = "";
      //récupération des données
      $data_id = $oDrData[$strFieldPk];

      //texte à indexer
      for($j=0; $j < count($tabFields); $j++) {
        $strDoc .= $oDrData[$tabFields[$j]]." ";
      }

      //traitements des mots
      $strDoc = mb_strtolower($strDoc);
      $strDoc = mb_ereg_replace('/ [0-9]* /', " ", $strDoc);
      $tabCount = null;
      $tabDoc = null;
      $tabDoc = $oWordIndexer->indexText($strDoc, "fr");
      $tabDoc = array_map(array(&$oWordIndexer, "replaceSpecialChar"), $tabDoc);
      $tabCount = array_count_values($tabDoc);

      //traitement mots par mots
      foreach ($tabCount as $strWord => $weight) {
        $oDsWord = $this->oQuerySearch->GetWordId($strWord);    
        if( $oDsWord->isEndOfFile() ) {
          $word_id = $this->oQuerySearchAction->insertWord($strWord);
        } else {
          $drWord = $oDsWord->GetRow();
          $word_id = $drWord["WORD_ID"];
        }

        //si datarow_existe pour ce document
        $oDsDatarow = $this->oQuerySearch->GetDatarowByIds($data_id, $atype_id, $appli_id, $datatype_id);
        if( $oDsDatarow->isEndOfFile() ) {
          $datarow_id = $this->oQuerySearchAction->insertDatarow($data_id, $atype_id, $appli_id, $datatype_id);
        } else { 
          $drDatarow = $oDsDatarow->GetRow();
          $datarow_id = $drDatarow["DATAROW_ID"];  
        }

        $DsDatarows = $this->oQuerySearch->GetDatarowsById($datarow_id, $weight);
        if( $DsDatarows->isEndOfFile() ) {
          $this->oQuerySearchAction->insertSearch($word_id,$datarow_id,$weight,1);
        } else {
          $words_id = "";
          while ( $DrDatarows = $DsDatarows->GetRowIter() ) {
            $words_id .= $DrDatarows["WORDS_ID"];  
          }

          if( mb_ereg_replace("-".$word_id."-", "", $words_id) ) {
            //calcul de la taille 
            $intLongeur = mb_strlen($words_id);
            $a = floor($intLongeur /4000); 
            $r = ($intLongeur % 4000);
            if($r>0)$a++; 
            for($i=1; $i<=$a; $i++){
              $index_id = $i;
              $words = mb_substr($words_id, 0, 4000);
              $words_id = mb_substr($words_id, 4001, mb_strlen($words_id)-1);

              $this->oQuerySearchAction->updateSearch($word_id, $datarow_id, $weight, $index_id);
            }
          }
        }
      }
    }
  }
  
  /**
   * Retourne le datatype_id (SIT) en fonction du nom d'une table, de l'identifiant d'une donnée et/ou de l'identifiant d'une pièce jointe
   * Fonction actuellement implémentée que pour les datatype de type IEDIT
   * @param strTableName    nom de la table (obligatoire)
   * @param data_id         identifiant de la donnée (obligatoire)
   * @param pj_id           identifiant de la pièce jointe (optionnel)
   */
  public function getSitDataTypeId($strTableName="", $data_id=-1, $pj_id=-1)
  {
    $sit_datatyp_id = -1;
    
    if ( $strTableName == "" || $data_id == -1 ) return $sit_datatyp_id;
    
    switch ( strtoupper($strTableName) ) {
      case "IEDIT_CLASSIFICATION" :
        $strSql = "select DATATYPE_ID from IEDIT_DATA where DATA_ID=".$data_id;
        $oDs = $this->dbConn->initDataset($strSql);
        if ( $oDr = $oDs->fetch() ) {
          $iEdit_datatype_id = $oDr["DATATYPE_ID"];
          $satype_abrev = "";
          if ( defined("ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id)) ) {
            eval("\$satype_abrev = ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id).";");
            if ( defined("ALK_SIT_DATATYPE_".strtoupper($satype_abrev)."_CAT") ) {
              eval("\$sit_datatyp_id = ALK_SIT_DATATYPE_".strtoupper($satype_abrev)."_CAT;");
            }
          }
        }
      break;
      case "IEDIT_DATA" :
        $strSql = "select DATATYPE_ID from IEDIT_DATA where DATA_ID=".$data_id;
        $oDs = $this->dbConn->initDataset($strSql);
        if ( $oDr = $oDs->fetch() ) {
          $iEdit_datatype_id = $oDr["DATATYPE_ID"];
          $satype_abrev = "";
          if ( defined("ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id)) ) {
            eval("\$satype_abrev = ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id).";");
            if ( defined("ALK_SIT_DATATYPE_".strtoupper($satype_abrev)) ) {
              eval("\$sit_datatyp_id = ALK_SIT_DATATYPE_".strtoupper($satype_abrev).";");
            }
          }
        }
      break;
      case "IEDIT_PJ" :
        $strSql = "select DATATYPE_ID from IEDIT_DATA where DATA_ID=".$data_id;
        $oDs = $this->dbConn->initDataset($strSql);
        if ( $oDr = $oDs->fetch() ) {
          $iEdit_datatype_id = $oDr["DATATYPE_ID"];
          $satype_abrev = "";
          if ( defined("ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id)) ) {
            eval("\$satype_abrev = ALK_SATYPE_ABREV_".strtoupper($iEdit_datatype_id).";");
            if ( $pj_id == -1 ) {
              if ( defined("ALK_SIT_DATATYPE_".strtoupper($satype_abrev)) ) {
                eval("\$sit_datatyp_id = ALK_SIT_DATATYPE_".strtoupper($satype_abrev).";");
              }
            } else {
              eval("\$sit_datatyp_id = ALK_SIT_DATATYPE_".strtoupper($satype_abrev)."_PJ;");
            }
          }
        }
      break;
    }
    
    return $sit_datatyp_id;
  }
  
  /**
   *  Retourne la liste des datatypes utilisés pour une fonctionnalité donnée
   * 
   * @param strFoncType  Nom de la fonctionnalite (Ex: APPLI_GEOLOC, APPLI_MDATA, APPLI_SEARCH, APPLI_STAT)
   * @param atype_id     Identifiant ou liste d'identifiants type application, (-1 par défaut, non pris en compte)
   * @return Retourne un dataSet
   */
  public function getDsListeDataTypeID($strFoncType, $atype_id="-1")
  {
    $strSql = "select d.*, ad.ATYPE_ID".
      " from SIT_ATYPE_DATATYPE ad".
      "   inner join SIT_DATATYPE d on ad.DATATYPE_ID=d.DATATYPE_ID".
      " where ".$strFoncType."=1".
      ($atype_id != "-1" ? " and ATYPE_ID in (".$atype_id.")" : "" ).
      " order by ad.ATYPE_ID, d.DATATYPE_INTITULE";
    return $this->getDs($strSql);
  }

  /**
   *  Indexe ou Désindexe une donnée identifiée par sa table dans la base et son identifiant
   * @param strTable    Table contenant la donnée
   * @param data_id     Identifiant de la donnée
   * @param bDelete     Désindexation de la donnée si true
   * @param language    Langue utilisée (fr, en)
   */
  public function indexData($strTable, $data_id, $bDelete=false, $language="fr")
  {
    if ( !(defined("ALK_SEARCH_SOLR") && ALK_SEARCH == ALK_SEARCH_SOLR) ){
      if ( !(defined("ALK_ATYPE_ID_SEARCH") && defined("ALK_B_ATYPE_SEARCH") && ALK_B_ATYPE_SEARCH==true) ) 
      return; 
    } 
        
    $strSelect = "select *".
      " from SIT_DATATYPE dt" .
      " where APPLI_SEARCH=1 and TABLE_REF='".$this->dbConn->analyseSql($strTable)."';";
          
    $dsDatatype = $this->dbConn->initDataset($strSelect);
    if ( $dsDatatype->isEndOfFile() ) return;

    $this->indexDataByDatatype($dsDatatype, $data_id, $bDelete);
  } 
  
  /**
   *  Indexe ou Désindexe la ou les données identifiées répondant au(x) type(s) de données
   *        Affiche dans un tableau les étapes et états du travail
   * @param dsDatatype      Types de données à indexer
   * @param one_data_id     Identifiant d'une donnée à indexer
   * @param bDelete         Désindexation de la donnée si true
   * @param bEcho           Affichage des logs si true
   * @param language        Langue utilisée (fr, en)
   */
  public function indexDataByDatatype($dsDatatype, $one_data_id="-1", $bDelete=false, $bEcho=false, $language="fr")
  {
    if ( !(defined("ALK_SEARCH_SOLR") && ALK_SEARCH == ALK_SEARCH_SOLR) ){
      if ( !(defined("ALK_ATYPE_ID_SEARCH") && defined("ALK_B_ATYPE_SEARCH") && ALK_B_ATYPE_SEARCH==true) ) 
      return; 
    } 
    $oSearchSolr = $this->initSearcherSolr();
    //suppression de l'indexation de ce fichier dans moteur de recherche
    if ( !is_null($oSearchSolr) ){
      $bIndex = $oSearchSolr->indexDataByDatatype($dsDatatype, $one_data_id, $bDelete, $bEcho, $language);
      // si contenu indexé, on sort, sinon on indexe avec l'autre méthode
      if ( $bIndex ) return;
    }
    
    
    $querySearch = AlkFactory::getQuery(ALK_ATYPE_ID_SEARCH);
    $querySearchAction = AlkFactory::getQueryAction(ALK_ATYPE_ID_SEARCH);
    $oIndexer = AlkFactory::getWordIndexer($language);

    $tabFields = array("DATA_ID", "APPLI_ID", "ATYPE_ID", "DATA_TEXT");
    $colspan = count($tabFields)+3;
    
    $nbWordsTOT = $nbWordsADD = $nbWordsDEL = $nbData = $nbDataOK = $nbDataNOK = $nbDataNOT = $nbDataNEW = $nbDataUDT = $nbDataDEL = 0;
    
    if ($bEcho) echo "<table border='1' cellspacing='0'>";
    while ( $drDatatype = $dsDatatype->fetch() ){
      $datatype_id    = $drDatatype["DATATYPE_ID"];
      $datatype_intitule
                      = $drDatatype["DATATYPE_INTITULE"];
      $table_name     = $drDatatype["TABLE_REF"];
      $table_alias    = $drDatatype["TABLE_ALIAS"];
      $primary_key    = $drDatatype["FIELD_PK"];
      $tabFieldsText  = explode("|", $drDatatype["FIELDS_TEXT"]);
      $strWhere       = $drDatatype["SELECTING_WHERE"];
      $tabAppliId     = explode("::", str_replace("__ALIAS__", $table_alias, str_replace("__TABLE__", $table_name, $drDatatype["SELECT_APPLI_ID"])));
      $tabAtypeId     = explode("::", str_replace("__ALIAS__", $table_alias, str_replace("__TABLE__", $table_name, $drDatatype["SELECT_ATYPE_ID"])));
              
      $fieldAppliId = "APPLI_ID";
      $tableAppliId = "";
      if ( empty($tabAppliId) )
        $fieldAppliId = "-1";
      if ( count($tabAppliId)>=1 && $tabAppliId[0]!="" )
        $fieldAppliId = $tabAppliId[0];
      if ( count($tabAppliId)>=2 )
        $tableAppliId = $tabAppliId[1];
        
      $fieldAtypeId = "ATYPE_ID";
      $tableAtypeId = "";
      if ( empty($tabAtypeId) )
        $fieldAtypeId = "-1";
      if ( count($tabAtypeId)>=1 && $tabAtypeId[0]!="" )
        $fieldAtypeId = $tabAtypeId[0];
      if ( count($tabAtypeId)>=2 )
        $tableAtypeId = $tabAtypeId[1];
        
      $strFieldsText = "";
      $strEval = "return \$this->dbConn->getConcat(";
      $strGlue = "";
      foreach ( $tabFieldsText as $strField ){
        $strEval .= $strGlue."\"' '\", \$this->dbConn->compareSql(\"".$strField."\", 'is', 'null', \"''\", \"".$strField."\")";
        $strGlue = ", ";
      }
      $strEval .= ");";
      $strFieldsText .= eval($strEval);
      
      if ( $one_data_id!="-1" )
        $strWhere .= ($strWhere!="" ? " and " : "").$primary_key."=".$one_data_id;
      
      $strSql = "select ".$primary_key." as DATA_ID" .
                ", ".$fieldAppliId." as APPLI_ID".
                ", ".$fieldAtypeId." as ATYPE_ID".
                ", ".$strFieldsText." as DATA_TEXT" .
                " from ".$table_name." ".$table_alias.
                " ".$tableAppliId .
                " ".$tableAtypeId .
                ($strWhere!="" ? " where ".$strWhere : "");
                
      $dsData = $this->dbConn->initDataset($strSql, 0, -1, false);
      if ($bEcho) {
        echo "<tr><td colspan='".$colspan."' style='color:blue'><b>".$datatype_intitule." (datatype_id=".$datatype_id.")"."</b></td></tr>";
        echo "<tr><td colspan='".$colspan."'><i>".$strSql."</i></td></tr>";
        echo "<tr>";
        foreach ( $tabFields as $strField ){
          echo "<td><u>".$strField."</u></td>";
        }
        echo "<td><b>Etat Indexation</b></td><td><b>DATAROW_ID</b></td><td><b>Résultat Indexation</b></td></tr>";
      }
      if ( $dsData->isEndOfFile() ){
        if ( $bEcho ) echo "<td colspan='".$colspan."' style='color:green' align='center'>"._t("Aucune donnée")."</td></tr>";
        continue;
      }
      $nbData += $dsData->getCountTotDr();
      
      while ( $drData = $dsData->getRowIter() ){
        $data_id = $atype_id = $appli_id = -1;
        if ($bEcho) echo "<tr>";
        foreach ( $tabFields as $strField ){
          $field = mb_strtolower($strField);
          $$field = $drData[$strField];
          if ($bEcho) echo "<td>".(trim($$field)=="" ? "&nbsp;" : $$field)."</td>";
        }
        
        //si datarow existe pour cette donnée
        $oDsDatarow = $querySearch->GetDatarowByIds($data_id, $atype_id, $appli_id, $datatype_id);
        if ( $oDsDatarow->isEndOfFile() && !$bDelete ) {
          $datarow_id = $querySearchAction->insertDatarow($data_id, $atype_id, $appli_id, $datatype_id);
          $strMsg = "Nouveau";
          $nbDataNEW++;
        } else if ($bDelete) {
          if ( $oDsDatarow->isEndOfFile() ){
            if ( $bEcho ) echo "<td colspan='3' style='color:purple'>Aucune action</td></tr>";
            $nbDataNOT++;
            continue;
          }
          
          $nbDataDEL++;
          $drDatarow = $oDsDatarow->GetRow();
          $datarow_id = $drDatarow["DATAROW_ID"];
          $nbWordsDelete = $querySearchAction->deleteSearch($datarow_id);
          $nbWordsDEL += $nbWordsDelete;
          $strMsg = "Suppression";
        }
        else {
          $nbDataUDT++;
          $drDatarow = $oDsDatarow->GetRow();
          $datarow_id = $drDatarow["DATAROW_ID"];
          $datarow_id = $querySearchAction->updateDatarow($datarow_id, $data_id, $atype_id, $appli_id, $datatype_id);
          $strMsg = "Réindexation";
        }    
        
        if (is_array($datarow_id)){
          $nbDataNOK++;
          if ($bEcho) echo "<td colspan='3' style='color:red'><b>ERREUR</b> : pour datarow_id=".$datarow_id[0]." : ".$datarow_id[1].")</td></tr>";
          continue;
        }
        else if ($bEcho) {
          echo "<td>".$strMsg."</td>";
          echo "<td>".$datarow_id."</td>";
        }
        if ( !$bDelete ){
          //traitements des mots
          $data_text = mb_strtolower($data_text);
          $data_text = preg_replace('/[0-9]/usi', " ", $data_text);
          $tabText = $oIndexer->indexText($data_text);
          $tabText = array_map("replaceSpecialChar", $tabText);
          $tabCount = array_count_values($tabText);
          //traitement mots par mots
          $nbInsert = 0;
          foreach ($tabCount as $strWord => $weight) {
            $oDsWord = $querySearch->GetWordId($strWord);
            if ($oDsWord->isEndOfFile()) {
              $word_id = $querySearchAction->insertWord($strWord);
              $nbInsert++;
            } 
            else {
              $drWord = $oDsWord->GetRow();
              $word_id = $drWord["WORD_ID"];
            }
            $DsDatarows = $querySearch->GetDatarowsById($datarow_id, $weight);
            if ($DsDatarows->isEndOfFile()) {
              $querySearchAction->insertSearch($word_id, $datarow_id, $weight, 1);
            } 
            else {
              $words_id = "";
              while ($DrDatarows = $DsDatarows->fetch()) {
                $words_id .= $DrDatarows["WORDS_ID"];
              }
              if (str_replace("-".$word_id."-", "", $words_id)) {
                //calcul de la taille 
                $intLongeur = strlen($words_id);
                $a = floor($intLongeur / 4000);
                $r = ($intLongeur % 4000);
                if ($r > 0)
                  $a ++;
                for ($i = 1; $i <= $a; $i ++) {
                  $index_id = $i;
                  $words = substr($words_id, 0, 4000);
                  $words_id = substr($words_id, 4001, strlen($words_id) - 1);
                  $querySearchAction->updateSearch($word_id, $datarow_id, $weight, $index_id);
                }
              }
            }
          }
          $nbWordsTOT += count($tabCount);
          $nbWordsADD += $nbInsert;
          if ($bEcho) echo "<td>OK<br/>".count($tabCount)." mot(s) indexé(s)<br/>".$nbInsert." mot(s) nouveau(x)</td></tr>";
        }
        else {
          if ($bEcho) echo "<td>OK<br/>".$nbWordsDelete." mot(s) désindexé(s)</td></tr>";
        } 
      }
      if ($bEcho) echo "<tr><td colspan='".$colspan."' height='10'></td></tr>";      
    }
    if ($bEcho){
      echo "<tr><td colspan='".$colspan."' height='10'></td></tr>";  
      echo "<tr style='color:red'><td colspan='".ceil($colspan/2)."'>";
      echo "<b>Nombre de données traitées : ".$nbData;
      echo "</b><br/>Dont <ul>";
      echo "<li>Données nouvelles : ".$nbDataNEW."</li>";
      echo "<li>Données réindexées : ".$nbDataUDT."</li>";
      echo "<li>Données supprimées : ".$nbDataDEL."</li>";
      echo "</ul>";
      echo "</td>";      
      
      echo "<td colspan='".floor($colspan/2)."'>";
      echo "<b>Nombre de mot traités : ".$nbWordsTOT;
      echo "</b><br/>Dont Mots nouveaux : ".$nbWordsADD;
      echo "<br/>Et avec, Mots supprimés : ".$nbWordsDEL."";
      echo "</td></tr>";    
      echo "</table>";
    }
  }  

  /**
   * Recopie les données d'une table d'association bloc_id, data_id ou cat_id
   * en fonction du nom de la table et de la liste des champs fournis en paramètre.
   * La liste des champs ne doit pas faire apparaître BLOC_ID. Les champs doivent être en majuscules et séparés par une virgule.
   * Les éventuels problèmes de doublon doivent être gérés avant l'appel de cette fonction 
   * 
   * @param bloc_id      identifiant du bloc à recopier
   * @param bloc_id_new  identifiant du nouveau bloc
   * @param strTableName nom de la table concernée (association (bloc, cat) ou (bloc, data))
   * @param strFieldList liste des noms de champs en majuscules séparés par une virgule, ne doit pas contenir le champ BLOC_ID
   */
  public function copyGEditBlocData($bloc_id, $bloc_id_new, $strTableName, $strFieldList)
  {
    
    if( $strTableName != "" ) {
      $strSql = "insert into ".$strTableName." (BLOC_ID, ".$strFieldList.")".
        " select ".$bloc_id_new.", ".$strFieldList.
        " from ".$strTableName.
        " where BLOC_ID=".$bloc_id;
    }
    $this->dbConn->executeSql($strSql);
  }

  /**
   * Retourne le label et la visibilité d'un champ donné par le role et une valeur de droit dans une table de gestion de droits (cf. module droits)
   * @param table_prefix    Préfixe des tables de gestion des droits ([table_prefix]_CHAMP et [table_prefix]_CHAMP_DROIT)
   * @param field_table     Nom de la table associée au champ (dans [table_prefix]_CHAMP)
   * @param field_name      Nom du champ (dans [table_prefix]_CHAMP)
   * @param role_id         Identifiant de la fiche de droit=role
   * @param droit_id        Valeur du droit recherché
   * @return dataset        {CHAMP_LABEL, B_VISIBLE(0,1)}
   */
  public static function getFieldVisibility($table_prefix, $field_table, $field_name, $role_id=-1, $droit_id=-1)
  {
    $dbConn = AlkFactory::getDbConn();
    $strSql = " select champ.CHAMP_LABEL, ".
              $dbConn->compareSql("champ.CHAMP_ID", " in ", 
                                  "((" .
                                  "select CHAMP_ID from ".$table_prefix."_CHAMP_DROIT ".
                                    " where " .($role_id!=-1 ? " ROLE_ID=".$role_id : "1=1").
                                    " and ".($droit_id!=-1 ? "DROIT_ID >0 and (DROIT_ID & ".$droit_id.")=".$droit_id : "1=1")."" .
                                  ") union (" .
                                  "select chpdroit.CHAMP_ID" .
                                    " from ".$table_prefix."_ROLE role" .
                                    " inner join ".$table_prefix."_CHAMP_DROIT chpdroit on (chpdroit.ROLE_ID=role.ROLE_ID)" .
                                    " where chpdroit.ROLE_ID=".$role_id." and role.AGENT_ID is null" .
                                    " and chpdroit.CHAMP_ID=champ.CHAMP_ID" .
                                    " and ".($droit_id!=-1 ? "DROIT_ID >0 and (DROIT_ID & ".$droit_id.")=".$droit_id : "1=1")."".
                                  "))", 
                                  "1", 
                                  "0").
              " as B_VISIBLE from ".$table_prefix."_CHAMP champ".
              " where ".$dbConn->getLowerCase("champ.CHAMP_TABLE")."=".$dbConn->getLowerCase("'".$dbConn->analyseSql($field_table)."'").
              " and ".$dbConn->getLowerCase("champ.CHAMP_CHAMP")."=".$dbConn->getLowerCase("'".$dbConn->analyseSql($field_name)."'");
    return $dbConn->initDataset($strSql);
  }
  
  public function getDsTagList($bSelectable, $field_name, $lg_id, $tabParams, $separator, $filters=array(), $bOnlyUsed=false)
  {
    $from_table = "SIT_TAG";
    $strWhere = "";
    foreach ($filters as $field=>$value){
      if ( $field=="from_table" ) {
        $from_table = $value;
        continue; 
      }
      if ( $field=="lg_id" ) continue;
      if ( $field=="field_value" ) {
        if ($bSelectable ) continue;
        if ( $from_table=="SIT_TAG" ) $field = "TAG_INTITULE";
        $value = explode($separator, $value);
      }
      $field = mb_strtoupper($field);
      switch (gettype($value)){
        case "string" :
          if ( $field=="INTITULE" ){
            if ( $from_table=="SIT_TAG" ) $field = "TAG_".$field;
            
          }
          $strWhere .= " and ".$field." like '%".$this->dbConn->analyseSql($value)."%'";
        break;
        case "integer" :
          $strWhere .= " and ".$field."=".$value;
        break;
        case "array" :
          if ( !empty($value) ){
            $value = array_map(array($this->dbConn, "analyseSql"), $value);
            $strWhere .= " and ".$field." in ('".implode("', '", $value)."')";
          }
        break;
      }
    }
    
    $strSql = "";
    switch ($from_table){
      case "SIT_TAG" :
        $field_name = $field_name.$this->tabLangue[$lg_id]["bdd"];
        $strSql = "select TAG_INTITULE" .
                  " from SIT_TAG tag" .
                  //(!$bSelectable ? " left join ".$tabParams["table_name"]." used on (used.".$field_name." like".$this->dbConn->getConcat("'%".$separator."'", "TAG_INTITULE", "'".$separator."%'").")" : "") .
                  " where TAG_LGID=".$lg_id.
                  //(!$bSelectable ? " and used.".$tabParams["pk_field"]."=".$tabParams["pk_id"] : "").
                  //(!$bSelectable && $bOnlyUsed ? " and used.".$field_name." is not null" : "").
                  $strWhere.
                  " order by TAG_INTITULE";
      break;
    }    
    return $this->dbConn->initDataset($strSql);
  }
  
  public function addTag($lg_id, $tag_intitule)
  {
    $strSql = "insert into SIT_TAG (TAG_LGID, TAG_INTITULE) values (".$lg_id.", '".$this->dbConn->analyseSql($tag_intitule)."')";
    return $this->dbConn->executeSql($strSql, false);
  }
  
  public function deleteTag($lg_id, $tag_intitule){
    $strSql = "delete from SIT_TAG  where TAG_INTITULE = '".$this->dbConn->analyseSql($tag_intitule)."' and TAG_LGID = ".$lg_id;
    return $this->dbConn->executeSql($strSql, false);
  }
  
  public function updateTag($old_tag, $new_tag, $lg_id){
    $strSql = " update SIT_TAG set TAG_INTITULE='".$this->dbConn->analyseSql($new_tag).
              "' where TAG_INTITULE='".$this->dbConn->analyseSql($old_tag).
              "' and TAG_LGID = ".$lg_id;
    return $this->dbConn->executeSql($strSql, false);
  }
  
    /**
   * Retourne la partie order by de la requête en fonction du bloc_typeassoc
   * @param bloc_typeassoc type d'association (cf fonction getDsListDataByBlocId pour le détail)
   * @param lg                langue sélectionnée
   * @param bloc_ordre        liste de nombres séparés par une virgule. 1 nombre correspond à une puissance de deux et à un champ
   * @param strAppliTypeAbrev abreviation de l'application contenant l'information
   * @return string
   */
  protected function getSqlOrderByGEditBlocTypeAssoc($bloc_typeassoc, $strLg, $bloc_ordre, $strAppliTypeAbrev="", $strPrefixe="d.")
  {
    $tabOrdre = explode(",", $bloc_ordre);
    
    $tabFields = array("_".TASSOC_NOUVEAUTE       => "DATA_NEW desc",
                       "_".TASSOC_CATEGORIE       => "CAT_INTITULE".$strLg,
                       "_".TASSOC_INTITULE        => ( $strAppliTypeAbrev != "FAQS" ? "DATA_TITRE".$strLg : "DATA_DESC".$strLg),
                       "_".TASSOC_DATEPUB_DESC    => "DATA_DATEPDEB desc, DATA_DATEPFIN desc",
                       "_".TASSOC_DATEPUB         => "DATA_DATEPDEB, DATA_DATEPFIN",
                       "_".TASSOC_DATEINFO_DESC   => "DATA_DATEDEB desc, DATA_DATEFIN desc",
                       "_".TASSOC_DATEINFO        => "DATA_DATEDEB, DATA_DATEFIN",
                       "_".TASSOC_DATEMODIF_DESC  => "DATA_DATEMAJ desc",
                       "_".TASSOC_DATEMODIF       => "DATA_DATEMAJ",
                       "_".TASSOC_DATARANG_DESC   => $strPrefixe."DATA_RANG desc",
                       "_".TASSOC_DATARANG        => $strPrefixe."DATA_RANG");
    
    $strSqlOrder = "";   
    $strGlu = "";      

    foreach($tabOrdre as $iBitOn) {
      if ( isset($tabFields["_".$iBitOn]) ){
        $strSqlOrder .= $strGlu.$tabFields["_".$iBitOn];
        $strGlu = ", ";
      }
    }
    return $strSqlOrder;
  }
  
  /**
   * Retourne la partie where en fonction du bloc_typeassoc
   * 
   * @param bloc_typeassoc     type d'association (cf fonction getDsListDataByBlocId pour le détail)
   * @param bloc_datedeb   Contient la date de début pour un filtre éventuel en fonction de la valeur de bloc_typeassoc
   * @param bloc_datefin   Contient la date de début pour un filtre éventuel en fonction de la valeur de bloc_typeassoc 
   * @param strPrefixe         prefixe du champ
   * @return string 
   */
  protected function getSqlWhereByGEditBlocTypeAssoc($bloc_typeassoc, $bloc_datedeb, $bloc_datefin, $strPrefixe="d.")
  {
    $bloc_datedeb = ( $bloc_datedeb != "" 
                      ? $this->dbConn->getDateFormat("DD/MM/YYYY HH:MI", "'".$bloc_datedeb." 00:00'", true)
                      : $this->dbConn->getDateFormat("DD/MM/YYYY HH:MI", "'".date("d/m/Y H:i")."'", true) );
    
    $bloc_datefin = ( $bloc_datefin != "" 
                      ? $this->dbConn->getDateFormat("DD/MM/YYYY HH:MI", "'".$bloc_datefin." 23:59'", true)
                      : $this->dbConn->getDateFormat("DD/MM/YYYY HH:MI", "'31/12/2035 00:00'", true) );

    $strSqlWhere = "";
    
    // filtre par bloc-appli / bloc-cat / bloc-data
    /*if( ($bloc_typeassoc & TASSOC_BYAPPLI) != TASSOC_BYAPPLI && 
        ($bloc_typeassoc & TASSOC_BYCATEG) != TASSOC_BYCATEG && 
        ($bloc_typeassoc & TASSOC_BYDATA ) != TASSOC_BYDATA ) {
      return "false";
    }*/
    
    // filtre de publication à faire en premier
    if( ($bloc_typeassoc & TASSOC_ENVALIDATION) == TASSOC_ENVALIDATION ) { // 2^26 information en cours de validation
      $strSqlWhere = $strPrefixe."DATA_VALIDEPUB=2";
    } 
    elseif( ($bloc_typeassoc & TASSOC_AVALIDER) == TASSOC_AVALIDER ) { // 2^5 information non validée
      $strSqlWhere = $strPrefixe."DATA_VALIDEPUB=0";
    }
    elseif( ($bloc_typeassoc& TASSOC_TOUTETATPUB) == TASSOC_TOUTETATPUB ) { //2^11 toute information (en cours, publiée ou à valider)
      // rien à faire
    }
    else { // information publiée
      $strSqlWhere = $strPrefixe."DATA_VALIDEPUB=1";  
    }
    
    if( ($bloc_typeassoc & TASSOC_SYNDIC) == TASSOC_SYNDIC ) { // 2^25 information syndiquée
      $strSqlWhere .= " and ".$strPrefixe."DATA_VALIDESYND=1";
    }
    
    if( ($bloc_typeassoc & TASSOC_ARCHIVE) == TASSOC_ARCHIVE ) { // 2^3 information passées, fonction de la valeur du bit 4
      $strSqlWhere .=  ( ($bloc_typeassoc & TASSOC_PUBLIE) != TASSOC_PUBLIE 
                         ? " and ".$strPrefixe."DATA_DATEPFIN<".$this->dbConn->getDateCur()
                         : "" );
    } 
    if( ($bloc_typeassoc & TASSOC_PUBLIE) == TASSOC_PUBLIE ) { // 2^4 information publiées en cours, fonction de la valeur du bit 3
      $strSqlWhere .=  " and (".$strPrefixe."DATA_DATEPDEB <= ".$this->dbConn->getDateCur()." or ".$strPrefixe."DATA_DATEPDEB is null)".
        ( ($bloc_typeassoc & TASSOC_ARCHIVE) != TASSOC_ARCHIVE 
          ? " and (".$strPrefixe."DATA_DATEPFIN >= ".$this->dbConn->getDateCur()." or ".$strPrefixe."DATA_DATEPFIN is null)"
          : "" );
    }

    // filtre à la une
    if( ($bloc_typeassoc & TASSOC_ALAUNE) == TASSOC_ALAUNE ) { // 2^2
      $strSqlWhere .= " and ".$strPrefixe."DATA_NEW=1".
        ( defined("ALK_B_IEDIT_DUREEALAUNE") && ALK_B_IEDIT_DUREEALAUNE==true
          ? " and (".$strPrefixe."DATA_NEW_DATEFIN is null or ".$strPrefixe."DATA_NEW_DATEFIN>=".$this->dbConn->getDateCur().")".
            " and (".$strPrefixe."DATA_NEW_DATEDEB is null or ".$strPrefixe."DATA_NEW_DATEDEB<=".$this->dbConn->getDateCur().")"
          : "" );
      $strSqlWhere .=  " and (".$strPrefixe."DATA_DATEPDEB <= ".$this->dbConn->getDateCur()." or ".$strPrefixe."DATA_DATEPDEB is null)".
          " and (".$strPrefixe."DATA_DATEPFIN >= ".$this->dbConn->getDateCur()." or ".$strPrefixe."DATA_DATEPFIN is null)";
         
    } 
    
    // filtre par date (un seul bit pris en compte par les 22, 23, 24, 27, 28 et 29
    if( ($bloc_typeassoc & TASSOC_INTERVPUB) == TASSOC_INTERVPUB ) { // 2^22 filtre début fin / date de publication
      // t0 = bloc_datedeb, t1=bloc_datefin
      $strT0 = $bloc_datedeb;
      $strT1 = $bloc_datefin;
      
      $strSqlWhere .=  " and (".
          "    (".$strPrefixe."DATA_DATEPDEB is null     and ".$strPrefixe."DATA_DATEPFIN is null)".
          " or (".$strPrefixe."DATA_DATEPDEB is null     and ".$strPrefixe."DATA_DATEPFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEPDEB<=".$strT1." and ".$strPrefixe."DATA_DATEPFIN is null)".
          // deb et fin != null
          " or (".$strPrefixe."DATA_DATEPDEB<=".$strT0." and ".$strPrefixe."DATA_DATEPFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEPDEB>=".$strT0." and ".$strPrefixe."DATA_DATEPDEB<=".$strT1.")".
          ")";
    } 
    elseif( ($bloc_typeassoc & TASSOC_INTERVINFO) == TASSOC_INTERVINFO ) { // 2^23 filtre début fin / date info
      // t0 = bloc_datedeb, t1=bloc_datefin
      $strT0 = $bloc_datedeb;
      $strT1 = $bloc_datefin;
      
      $strSqlWhere .=  " and (".
          "    (".$strPrefixe."DATA_DATEDEB is null     and ".$strPrefixe."DATA_DATEFIN is null)".
          " or (".$strPrefixe."DATA_DATEDEB is null     and ".$strPrefixe."DATA_DATEFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEDEB<=".$strT1." and ".$strPrefixe."DATA_DATEFIN is null)".
          // deb et fin != null
          " or (".$strPrefixe."DATA_DATEDEB<=".$strT0." and ".$strPrefixe."DATA_DATEFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEDEB>=".$strT0." and ".$strPrefixe."DATA_DATEDEB<=".$strT1.")".
          ")";
    } 
    elseif( ($bloc_typeassoc & TASSOC_INTERVMODIF) == TASSOC_INTERVMODIF ) { // 2^24 filtre début fin / date maj
      // t0 = bloc_datedeb, t1=bloc_datefin
      $strT0 = $bloc_datedeb;
      $strT1 = $bloc_datefin;
    
      $strSqlWhere .=  " and ".$strPrefixe."DATA_DATEMAJ>=".$strT0." and ".$strPrefixe."DATA_DATEMAJ<=".$strT1;
    } 
    elseif( ($bloc_typeassoc & TASSOC_30J_PUBLIE) == TASSOC_30J_PUBLIE ) { // 2^27 filtre 30 derniers jours / date pub
      // t0 = J-30, t1=J
      $strT0 = $this->dbConn->getDateAdd($this->dbConn->getDateCur(), "-30", "D");
      $strT1 = $this->dbConn->getDateCur();
      
      $strSqlWhere .=  " and (".
          "    (".$strPrefixe."DATA_DATEPDEB is null     and ".$strPrefixe."DATA_DATEPFIN is null)".
          " or (".$strPrefixe."DATA_DATEPDEB is null     and ".$strPrefixe."DATA_DATEPFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEPDEB<=".$strT1." and ".$strPrefixe."DATA_DATEPFIN is null)".
          // deb et fin != null
          " or (".$strPrefixe."DATA_DATEPDEB<=".$strT0." and ".$strPrefixe."DATA_DATEPFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEPDEB>=".$strT0." and ".$strPrefixe."DATA_DATEPDEB<=".$strT1.")".
          ")";
    } 
    elseif( ($bloc_typeassoc & TASSOC_30J_INFO) == TASSOC_30J_INFO ) { // 2^28 filtre 30 derniers jours / date info
      // t0 = J-30, t1=J
      $strT0 = $this->dbConn->getDateAdd($this->dbConn->getDateCur(), "-30", "D");
      $strT1 = $this->dbConn->getDateCur();
      
      $strSqlWhere .=  " and (".
          "    (".$strPrefixe."DATA_DATEDEB is null     and ".$strPrefixe."DATA_DATEFIN is null)".
          " or (".$strPrefixe."DATA_DATEDEB is null     and ".$strPrefixe."DATA_DATEFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEDEB<=".$strT1." and ".$strPrefixe."DATA_DATEFIN is null)".
          // deb et fin != null
          " or (".$strPrefixe."DATA_DATEDEB<=".$strT0." and ".$strPrefixe."DATA_DATEFIN>=".$strT0.")".
          " or (".$strPrefixe."DATA_DATEDEB>=".$strT0." and ".$strPrefixe."DATA_DATEDEB<=".$strT1.")".
          ")";
    } 
    elseif( ($bloc_typeassoc & TASSOC_30J_MODIF) == TASSOC_30J_MODIF ) { // 2^29 filtre 30 derniers jours / date maj
      $strT0 = $this->dbConn->getDateAdd($this->dbConn->getDateCur(), "-30", "D");
      
      $strSqlWhere .=  " and ".$strPrefixe."DATA_DATEMAJ >= ".$strT0;
    }
    
    return $strSqlWhere;
  }
  
  /**
  * formate et retourne une chaîne pour les logs de tâches planifiées
  */
  public function formatCronLog($strLog, $rank=0)
  {
    $strSpace = "";
    for ( $i=0; $i<$rank; $i++ ) {
      $strSpace.= "  ";
    }
    return "[".date("d/m/Y H:i:s")."] ".$strSpace.$strLog."\n";
  }
}


?>