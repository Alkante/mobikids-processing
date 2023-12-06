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

require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkIntDbPlateform.php");

/**
 * @package Alkanet_Class_Pattern
 * 
 * @class AlkDbPlateform
 * @brief Classe de base de l'implémentation de l'interface AlkIntDbPlateform
 */
abstract class AlkDbPlateform implements AlkIntDbPlateform
{
  /** différence en heure entre heure locale du navigateur et GMT, <0 à l'est, >0 à l'ouest */
  protected $deltaGMT;

  /** différence en heure entre heure locale du serveur et GMT, <0 à l'est, >0 à l'ouest */
  protected $deltaGMTServ;
  
  /** liste des remplacements d'encodage cp1252 (windows) à faire */
  protected static $safeEncoding = null;
  
  /**
   * Mémorise la différence en heure entre l'heure locale du navigateur et GMT
   * Retourne une référence sur l'objet lui-même 
   * @param iDeltaGMT      entier, décalage en heure du client / GMT, <0 à l'est, >0 à l'ouest
   * @param iDeltaGMTServ  entier, décalage en heure du serveur / GMT, <0 à l'est, >0 à l'ouest
   */
  public function setDeltaGMT($iDeltaGMT, $iDeltaGMTServ=-1)
  {
    $this->deltaGMT     = $iDeltaGMT;
    $this->deltaGMTServ = $iDeltaGMTServ;
  } 

  /*******************************
   * Méthodes gestion de requêtes
   *******************************/
  
  /**
   * Retourne une requête SQL neutre qui fonctionne constamment et retourne un résultat vide
   * @return string
   */
  abstract public function getEmptyQuery();
  
  /**
   * Applique à la requête passée en paramètre, les modifications adéquates pour permettre la pagination
   * Les 3 paramètres sont passés en référence
   * Retourne true si la méthode a appliqué des changements, false sinon
   * @param string $strSql requête sql de base
   * @param int    $iFirst indice de début de pagination, =0 par défaut
   * @param int    $iLast  indice de fin de pagination, =-1 par défaut pour aucune pagination
   * @return boolean
   */
  abstract public function applySqlPaginationLimits(&$strSql, $iFirst=0, $iLast=-1);
  
  /**
   * Applique à la requête passée en paramètre, les modifications adéquates pour calculer le nombre total d'éléments sans la pagination
   * Retourne true si la méthode a appliqué des changements, false sinon
   * @param string $strSql  requête sql de base
   * @return boolean
   */
  abstract public function applySqlRowCountWithoutPagination(&$strSql);
  
  /**
   * Retourne la requête SQL qui permet de récupérer le nombre de total de ligne sans pagination
   * @param string $strSql  requête sql de base
   * @return string
   */
  abstract public function getSqlRowCountWithoutPagination($strSql);
  
  /***********************************
   * Méthodes gestion de structures
   **********************************/
  
  /**
   * Retourne la requête d'ajout de version dans la base Alkanet
   * @param string $moduleName   Nom du module alkanet (chaine non vérifiée)
   * @param string $version      numéro de version de la forme M.m.c
   * @param int    $moduleLevel  ordonnancement du module
   * @param string $description  Description de la version (chaine non vérifiée)
   * @return string
   */
  public function getSqlAddVersion($moduleName, $version, $moduleLevel, $description)
  {
    $moduleLevel = ( is_numeric($moduleLevel) ? floor($moduleLevel*1) : 0 );
    
    $tabVersion = explode(".", $version);
    $iNb = count($tabVersion);
    $versionNumber = 0;
    for($i=0; $i<$iNb; $i++) {
      $versionNumber += $tabVersion[$i]*pow(10, ($iNb-$i-1)*3);
    }
    
    if( $versionNumber == 0 || $moduleName=="" || $moduleLevel==0) {
      return "";
    }
    
    return "insert into SIT_VERSION (vers_name, vers_version, vers_number, vers_date, vers_level, vers_desc)".
      " values ('".$moduleName."', '".$version."', ".$versionNumber.", ".$this->getDateCur().", ".$moduleLevel.", '".$description."')";
  }

  /**
   * Construit et retourne le script sql correspondant à la représentation tableau alkanet de la structure de la base de donnée
   * Retourne un tableau avec une ligne par instruction sql
   * @param array $alkSql  tableau alkanet de la structure de la base de donnée
   * @return array
   */
  public function getDatabaseStructure($alkSql)
  {
    if( !is_array($alkSql) || empty($alkSql) ) {
      return array("-- pas de structure valide fournie.");
    }
    $validType = 
      array("table"      => "getTabSqlCreateTable",              // création table
            "droptable"  => "getTabSqlDropConstraintForeignKey", // suppression table
            "altertable" => "getTabSqlAlterTable", // modification table
            "view"       => "getTabSqlCreateView",               // création vue
            "dropview"   => "getTabSqlDropView",                 // suppression vue
            "pk"         => "getTabSqlAddPrimary",               // ajout clé primaire
            "droppk"     => "getTabSqlDropPrimary",              // suppression clé primaire
            "fk"         => "getTabSqlAddConstraintForeignKey",  // création contrainte clé étrangère
            "dropfk"     => "getTabSqlDropConstraintForeignKey", // suppression contrainte clé étrangère
            "uq"         => "getTabSqlAddConstraintUnique",      // ajout contrainte unicité
            "dropuq"     => "getTabSqlDropConstraintUnique",     // suppression contrainte unicité
            "seq"        => "getTabSqlCreateSequence",           // ajout séquence
            "dropseq"    => "getTabSqlDropSequence",             // suppression séquence
            "idx"        => "getTabSqlCreateIndex",              // création index
            "dropidx"    => "getTabSqlDropIndex",                // suppression index
            "ins"        => "_none_ins"                          // requête sql classique directement utilisable
        );
    
    // premier niveau : les requêtes sont ordonnancées dans l'ordre d'exécution
    $sql = array();
    foreach($alkSql as $types) {
      // second niveau : exécution d'un type de requête : création table, création index...
      if( !is_array($types) || empty($types) ) {
        continue;
      }
      foreach($types as $type => $data) {
        if( !is_array($data) ) { 
          continue; 
        }
        $type = strtolower($type);
        if( array_key_exists($type, $validType) ) {
          $sql = ( $type != "ins"
                   ? array_merge($sql, call_user_func(array($this, $validType[$type]),$data))
                   : array_merge($sql, $data) );
        }else{
          trigger_error( 'getDatabaseStructure : le type "'.$type.'" n\'est pas valide '.  "<pre>".print_r($data, true."</pre>"), E_USER_WARNING);
        }
      }
    }
    return $sql;
  }
  
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
  abstract public function getSqlCopyRowFromTableToTable($strTableSrc, $strTableDest, $tabFieldPkSrc, $tabFieldPkDest, $tabFieldsName);
  
  /**
   * Retourne le code SQL permettant de créer une table (uniquement les champs typés)
   * @param strTableName  nom de la table ou tableau
   * @param tabFields     tableau contenant les informations sur les champs à créer
   * @return string
   */
  abstract public function getSqlCreateTable($strTableName, $tabDataFields, $tableInherit=null);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer un ensemble de tables
   * @param tabData        tableau contenant les informations sur les tables à créer
   * @param tabInheritance tableau contenant les caractéristiques éventuelles d'héritage entre tables (valable en Postgrès)
   * @param dropMode       mode d'affichage de l'instruction préalable DROP TABLE : none=non visible, comment=visible en commentaire, drop=visible et exécutable 
   * @return array
   */
  public function getTabSqlCreateTable($tabData, $tabInheritance=array(), $dropMode="none")
   {
    if ( !($this instanceof AlkDbPlateformPgsql) ){
      $tabInheritance = array();
    }
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataFields) {
      $_dropMode = $dropMode;
      $inherits = null;
      if ( isset($tabInheritance[$strTableName]) ){
        $inherits = $tabInheritance[$strTableName];
        if ( is_array($inherits) ){
          $tableInherit = $inherits["table"];
        } else {
          $tableInherit = $inherits;
        }
        if ( isset($tabData[$tableInherit]) ) {
          $tabDataFields = array_diff_key($tabDataFields, $tabData[$tableInherit]);
        }
        if ( isset($inherits["dropMode"]) ) {
          $_dropMode = $inherits["dropMode"];
        }
        else {
          $_dropMode = "none";
        }
      }
      if ( $_dropMode!="none" ){
        $drop = $this->getSqlDropTable($strTableName);
        $tabSql[] = ($_dropMode=="comment" ? "/*".$drop."*/" : $drop);
      }
      $tabSql[] = $this->getSqlCreateTable($strTableName, $tabDataFields, $inherits);
    }
    return $tabSql;
  }        
  
  /**
   * Retourne le code SQL permettant de supprimer une table
   * @param strTableName  nom de la table ou tableau
   * @return string
   */
  public function getSqlDropTable($strTablename)
  {
    return "drop table ".strtoupper($strTablename);
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de tables
   * @param tabData  tableau contenant les informations sur les tables à supprimer
   * @return array
   */
  public function getTabSqlDropTable($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName) {
      $tabSql[] = $this->getSqlDropTable($strTableName);
    }
    return $tabSql;
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
    return "alter table ".strtoupper($strTableName).
      " add constraint ".strtoupper($strPkName)." primary key (".strtoupper($strFieldList).")";
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à créer
   * @return array
   */
  public function getTabSqlAddPrimary($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataPk) {
      foreach($tabDataPk as $strPkName => $strFieldList) {
        $tabSql[] = $this->getSqlAddPrimary($strTableName, $strPkName, $strFieldList);
      }
    }
    return $tabSql;
  }
  
  /**
   * Retourne le code Sql permettant de supprimer une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @return string
   */
  abstract public function getSqlDropPrimary($strTableName, $strPkName);
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à supprimer
   * @return array
   */
  public function getTabSqlDropPrimary($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $strPkName) {
      $tabSql[] = $this->getSqlDropPrimary($strTableName, $strPkName);
    }
    return $tabSql;
  }
  
  /**
   * Retourne le code SQL permettant de créer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @param strFieldName   nom du champ
   * @return string
   */
  abstract public function getSqlCreateIndex($strTableName, $strIndexName, $strFieldName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlCreateIndex($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataIdx) {
      foreach($tabDataIdx as $strIndexName => $strFieldName) {
        $tabSql[] = $this->getSqlCreateIndex($strTableName, $strIndexName, $strFieldName);
      }
    }
    return $tabSql;
  }
  
 /**
   * Retourne le code SQL permettant de supprimer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @return string
   */
  abstract public function getSqlDropIndex($strTableName, $strIndexName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlDropIndex($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataIdx) {
      foreach($tabDataIdx as $strIndexName ) {
        $tabSql[] = $this->getSqlDropIndex($strTableName, $strIndexName);
      }
    }
    return $tabSql;
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
  abstract public function getSqlAddConstraintForeignKey($strTableName, $strFkName, $strFieldFk, $strTablePk, $strFieldPk, $strOption="");

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlAddConstraintForeignKey($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataFks) {
      foreach($tabDataFks as $strFkName => $tabDataFk) {
        if( isset($tabDataFk["ffk"]) && isset($tabDataFk["tpk"]) && isset($tabDataFk["fpk"]) && is_array($tabDataFk)){
          $tabSql[] = $this->getSqlAddConstraintForeignKey($strTableName, $strFkName, $tabDataFk["ffk"], $tabDataFk["tpk"], $tabDataFk["fpk"], 
                                                            ( array_key_exists("op", $tabDataFk) ? $tabDataFk["op"] : "") );
        }else{
          trigger_error( "échec : Fk table ".$strTableName." ".json_encode($tabDataFks), E_USER_WARNING);
        }
      }
    }
    return $tabSql;
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une clé étrangère à une table
   * @param strTableName   nom de la table locale
   * @param strFkName      nom de la clé étrangère
   * @return string
   */
  abstract public function getSqlDropConstraintForeignKey($strTableName, $strFkName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropConstraintForeignKey($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataFks) {
      foreach($tabDataFks as $strFkName ) {
        $tabSql[] = $this->getSqlDropConstraintForeignKey($strTableName, $strFkName);
      }
    }
    return $tabSql;
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
    return "alter table ".strtoupper($strTableName).
      " add constraint ".strtoupper($strUqName)." unique (".strtoupper($strFieldList).")";
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlAddConstraintUnique($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataUqs) {
      foreach($tabDataUqs as $strUqName => $strFieldList) {
        $tabSql[] = $this->getSqlAddConstraintUnique($strTableName, $strUqName, $strFieldList);
      }
    }
    return $tabSql;  
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @return string
   */
  abstract public function getSqlDropConstraintUnique($strTableName, $strUqName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlDropConstraintUnique($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strTableName => $tabDataUqs) {
      foreach($tabDataUqs as $strUqName) {
        $tabSql[] = $this->getSqlDropConstraintUnique($strTableName, $strUqName);
      }
    }
    return $tabSql;  
  }

  /**
   * Retourne le code SQL permettant de créer une vue
   * @param strViewName   nom de la vue
   * @param strSql        code sql de la vue
   */
  public function getSqlCreateView($strViewName, $strSql)
  {
    return "create or replace view ".strtoupper($strViewName)." ".$strSql;
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer des vues
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlCreateView($tabData, $dropMode="none")
  {
    $tabSql = array();
    foreach($tabData as $strViewName => $strSql) {
      if ( $dropMode!="none" ){
        $drop = $this->getSqlDropView($strViewName);
        $tabSql[] = ($dropMode=="comment" ? "/*".$drop."*/" : $drop);
      }
      $tabSql[] = $this->getSqlCreateView($strViewName, $strSql);
    }
    return $tabSql;
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une vue
   * @param strViewName   nom de la vue
   * @return string
   */
  public function getSqlDropView($strViewName)
  {
    return "drop view ".strtoupper($strViewName);
  }

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer une vue
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropView($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strViewName ) {
      $tabSql[] = $this->getSqlDropView($strViewName);
    }
    return $tabSql;
  }
  
  /**
   * Retourne le code SQL permettant de créer une séquence
   * @param strSeqName   nom de la séquence
   * @param iStart       indice de début de la séquence
   */  
  abstract public function getSqlCreateSequence($strSeqName, $iStart);

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à créer
   * @return array
   */
  public function getTabSqlCreateSequence($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strSeqName => $iStart) {
      $tabSql[] = $this->getSqlCreateSequence($strSeqName, $iStart);
    }
    return $tabSql;    
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une séquence
   * @param strSeqName   nom de la séquence
   * @return string
   */  
  abstract public function getSqlDropSequence($strSeqName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à supprimer
   * @return array
   */  
  public function getTabSqlDropSequence($tabData)
  {
    $tabSql = array();
    foreach($tabData as $strSeqName) {
      $tabSql[] = $this->getSqlDropSequence($strSeqName);
    }
    return $tabSql;    
  }
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de modifier la structure de tables
   * @param tabData  tableau contenant les informations sur les tables à modifier
   * @return array
   */  
  public function getTabSqlAlterTable($tabDataSrc)
  {
    $tabSql = array();
    
    foreach($tabDataSrc as $strTableName => $tabDatas) {
      foreach($tabDatas as $tabData) {
        if( isset($tabData["action"]) ) {
          $strError = PHP_EOL;
          if (!isset($tabData["column"]))
            $strError .= "Information column manquante".PHP_EOL;
          if ($tabData["action"] == "update" && !isset($tabData["newcolumn"]))
            $strError .= "Information newcolumn manquante".PHP_EOL;
          if ($tabData["action"] != "drop" && !isset($tabData["type"]))
            $strError .= "Information type manquante".PHP_EOL;
          if ($tabData["action"] != "drop" && !isset($tabData["length"]))
            $strError .= "Information length manquante".PHP_EOL;
          if ($tabData["action"] != "drop" && !isset($tabData["default"]))
            $strError .= "Information default manquante".PHP_EOL;
          if ($tabData["action"] != "drop" && !isset($tabData["nullable"]))
            $strError .= "Information nullable manquante".PHP_EOL;
          if ($strError!=PHP_EOL)
            trigger_error( "échec : alter table ".$strTableName." ".$tabData["action"]." ".json_encode($tabDatas).$strError, E_USER_WARNING);
//            throw new Exception("échec : alter table ".$strTableName." ".$tabData["action"]." ".json_encode($tabDatas).$strError);
            
          switch( $tabData["action"] ) {
            case "add":
              if( isset($tabData["column"]) && isset($tabData["type"]) && 
                  isset($tabData["length"]) && isset($tabData["default"]) && isset($tabData["nullable"]) ) {
                $tabSql[] = $this->getSqlAlterTableAddColumn($strTableName, $tabData["column"], $tabData["type"],
                                                              $tabData["length"],$tabData["default"], $tabData["nullable"]);
              }
              break;  
            case "update":
              if( isset($tabData["column"]) && isset($tabData["newcolumn"]) && isset($tabData["type"]) && 
                  isset($tabData["length"]) && isset($tabData["default"]) && isset($tabData["nullable"]) ) {
                $tabSql[] = $this->getSqlAlterTableUpdateColumn($strTableName, $tabData["column"], $tabData["newcolumn"], 
                                                                 $tabData["type"],$tabData["length"],$tabData["default"], $tabData["nullable"]);
              }
              break;
            case "drop": 
              if( isset($tabData["column"]) ) {
                $tabSql[] = $this->getSqlAlterTableDropColumn($strTableName, $tabData["column"]);
              }
              break; 
          }
        }
      }
    }
    return $tabSql;    
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
  abstract public function getSqlAlterTableUpdateColumn($strTableName, $strColumnName, $strNewColumnName, $strNewColumnType="", 
                                                        $strNewColumnLength="", $strNewColumnDefault="", $iNewColumnNullable=-1);
  

    
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
    if ( $strTableName=="" || $strColumnName=="" ) {
      throw new Exception('getSqlAlterTableAddColumn : $strTableName=="" || $strColumnName=="" ');
      return "";
    }
      
    $strType = $this->getColumnType($strColumnType, $strColumnLength);
    if ( $strType===false ) {
      throw new Exception('getSqlAlterTableAddColumn : "'.$strTableName.'" $strType('.$strType.')===false ');
      return "";
    }
      
    $strSql = "alter table ".strtoupper($strTableName).
      " add column ".strtoupper($strColumnName)." ".$strType.
      ( $strColumnDefault!="" 
        ? " default ".$strColumnDefault
        : "" ).
      ( $iColumnNullable!=-1 
        ? ( $iColumnNullable==0 
            ? " null" 
            : " not null" )
        : "" );
    
    return $strSql;
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
    if ( $strTableName=="" || $strColumnName=="" ) {
      return "";
    }
    return "alter table ".strtoupper($strTableName).
      " drop column ".strtoupper($strColumnName);
  }

  /***********************************
   * Méthodes fonctions sql avancées
   **********************************/
  
  /**
   * Traduit le type de colonne fourni dans la syntaxe du SGBD et y ajoute la longueur de champ si fournie
   * Retourne la traduction en cas de succès
   * Retourne false en cas d'erreur (longueur non fournie mais nécessaire, type incorrect, ...)
   *
   * @param columnType      Type de donnée de colonne
   * @param columnLength    Longueur max des données de la colonne
   * @return string, retourne FALSE en cas d'erreur
   */
  abstract protected function getColumnType($columnType, $columnLength="", $columnLength2="");
  
  /**
   * Retourne un tableau associatif fournissant la correspondances entre les types SGBD alkanet et ceux des SGBD
   * Les types Alkanet correspondent aux clés du tableau, les types SGBD correspondent aux valeurs
   * @return array 
   */
  abstract public function getSqlType();

    /**
   * Retourne l'expression SQL qui correspond à la fonction cast
   * @param string $strValue Nom du champ ou expression sql à traiter
   * @param string $strType  Type du champ ou expression sql à traiter
   * @return string
   */
  abstract public function getCast($strValue, $strType);
  
  /**
   *  Retourne le code sql des instructions "show tables" et "show tables like "
   * 
   * @param strLikeTable    Si non vide permet de faire un show tables like 
   * @return string SQL
   */
  abstract public function getShowTables($strLikeTable="", $bOnlyTables=false) ;

  /**
   * Retourne la description des colonnes d'une table
   * @param strTableName    Nom de la table
   * @return dataset
   */
  abstract public function getSqlTableColumns($strTableName);

  /**
   * Retourne une chaine de comparaison dans une requete SQL
   * Retourne une chaine : l'expression SQL associée à la comparaison
   * @param strField   Nom du champ dont la valeur est à tester
   * @param strCompare Opérateur de comparaison
   * @param strValeur  Valeur à comparer
   * @param strCaseOk  Valeur retournée si comparaison vraie
   * @param strCaseNok Valeur retournée si comparaison fausse
   * @return string
   */
  abstract public function compareSql($strField, $strCompare, $strValue, $strCaseOK, $strCaseNok);

  /**
   * Retourne le code SQL permettant de récupérer un numéro de séquence
   * Ce code sql peut-être intégrer dans une requête de type insert
   * @param string  $strSequenceName  nom de la séquence
   * @param boolean $bUpdateSeq       =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @return string
   */
  abstract public function getSqlNextSeqId($strSequenceName, $bUpdateSeq=true);
  
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
    return $this->getSqlNextSeqId($strSequenceName, $bUpdateSeq=true);
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
  abstract public function getGroupConcat($strField, $strSeparator="','", $strOrder="", $bDistinct=false, $strFrom="", $bNullTest=true);
  
  /************************************
   * Méthodes de traitement de chaine
   ************************************/
  
  /**
   * Retourne une chaine décodée sur les caractères CP-1252
   * Convertit les caractères CP-1252 vers des caractères interprétables en UTF-8 ou ISO-8859-1
   * @param string $strString  Chaine à traiter
   * @return string 
   */
  public function convertCharactersCp1252($strString) 
  {
    if( !isset(self::$safeEncoding) ) {
      $encodingTo = ( defined("ALK_HTML_ENCODING") ? ALK_HTML_ENCODING : "UTF-8" );
      self::$safeEncoding = array(
        mb_convert_encoding("\x80", $encodingTo, "cp1252") => "€",    
        mb_convert_encoding("\x81", $encodingTo, "cp1252") => " ",    
        mb_convert_encoding("\x82", $encodingTo, "cp1252") => "'", 
        mb_convert_encoding("\x83", $encodingTo, "cp1252") => 'f',
        mb_convert_encoding("\x84", $encodingTo, "cp1252") => '"',  
        mb_convert_encoding("\x85", $encodingTo, "cp1252") => "...",  
        mb_convert_encoding("\x86", $encodingTo, "cp1252") => "+", 
        mb_convert_encoding("\x87", $encodingTo, "cp1252") => "#",
        mb_convert_encoding("\x88", $encodingTo, "cp1252") => "^",  
        mb_convert_encoding("\x89", $encodingTo, "cp1252") => "0/00", 
        mb_convert_encoding("\x8A", $encodingTo, "cp1252") => "S", 
        mb_convert_encoding("\x8B", $encodingTo, "cp1252") => "<",
        mb_convert_encoding("\x8C", $encodingTo, "cp1252") => "OE", 
        mb_convert_encoding("\x8D", $encodingTo, "cp1252") => " ",    
        mb_convert_encoding("\x8E", $encodingTo, "cp1252") => "Z", 
        mb_convert_encoding("\x8F", $encodingTo, "cp1252") => " ",
        mb_convert_encoding("\x90", $encodingTo, "cp1252") => " ",  
        mb_convert_encoding("\x91", $encodingTo, "cp1252") => "`",    
        mb_convert_encoding("\x92", $encodingTo, "cp1252") => "'", 
        mb_convert_encoding("\x93", $encodingTo, "cp1252") => '"',
        mb_convert_encoding("\x94", $encodingTo, "cp1252") => '"',  
        mb_convert_encoding("\x95", $encodingTo, "cp1252") => "*",    
        mb_convert_encoding("\x96", $encodingTo, "cp1252") => "-", 
        mb_convert_encoding("\x97", $encodingTo, "cp1252") => "--",
        mb_convert_encoding("\x98", $encodingTo, "cp1252") => "~",  
        mb_convert_encoding("\x99", $encodingTo, "cp1252") => "(TM)", 
        mb_convert_encoding("\x9A", $encodingTo, "cp1252") => "s", 
        mb_convert_encoding("\x9B", $encodingTo, "cp1252") => ">",
        mb_convert_encoding("\x9C", $encodingTo, "cp1252") => "oe", 
        mb_convert_encoding("\x9D", $encodingTo, "cp1252") => " ",    
        mb_convert_encoding("\x9E", $encodingTo, "cp1252") => "z", 
        mb_convert_encoding("\x9F", $encodingTo, "cp1252") => "Y",
        "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
        "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
        "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
        "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
        "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
        "\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
        "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
        "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
        "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
        "\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
        "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
        "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
      );
    }
    
    return strtr($strString, self::$safeEncoding);
  }
  
  /**
   * Retourne l'expression SQL qui fournit la concatenation d'un nombre indéfinit de paramètres
   * @return string
   */
  abstract public function getConcat();
  
  /**
   * Retourne l'expression SQL qui fournit une sous-chaine
   * @param sstring $strField Nom du champ ou expression sql à traiter
   * @param int     $iPos     Position de départ (premier caractère = 0)
   * @param int     $iLength  Longueur de la sous-chaine (facultatif), =-1 par défaut pour signifier la fin de chaine
   * @return string
   */
  abstract public function getSubstring($strField, $iPos, $iLength=-1);

  /**
   * Retourne l'expression SQL qui transforme en minuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  abstract public function getLowerCase($strField);
  
  /**
   * Retourne l'expression SQL qui transforme en majuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  abstract public function getUpperCase($strField);
  
  /**
   * Retourne l'expression sql permettant de faire une comparaison de chaines sans tenir compte des caractères accentués
   * Ne fonctionne réellement que sous Oracle. Pour les autres, une simple comparaison en minuscules est réalisée.
   * @param string $strField Nom du champ de la table
   * @param string $strOp    Operateur de test SQL : like, =
   * @param string $strVal   Chaine de comparaison qui doit etre traitee par ora_analyseSQL auparavant
   * @deprecated since version 3.6
   * @return string
   */
  abstract public function getStrConvert2ASCII7($strField, $strOp, $strVal);
  
  /************************************
   * Méthodes de traitement de date
   ************************************/
  
  /**
   * Retourne l'expression SQL qui fournit la date-heure système en tenant compte du fuseau horaire du serveur et du client
   * @return string
   */
  abstract public function getDateCur();

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
  abstract public function getDateFormat($strFormat, $strDate, $bToDate=true, $bCastToInt=false);

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
  abstract public function getDateFromTimestamp($strFormat, $iTimestamp, $bToDate=true);
  
  /**
   * Retourne l'expression sql qui donne le nombre de jours qui sépare les 2 dates
   * Retourne le résultat de $strDateField1-$strDateField2
   * @param string $strDateField1    Nom du champ ou expression sql de type date à traiter contenant la première date
   * @param string $strDateField2    Nom du champ ou expression sql de type date à traiter contenant la seconde date
   * @return string
   */
  abstract public function getDateDiff($strDateField1, $strDateField2);
  
    /**
   * Retourne l'expression SQL qui permet d'additionner des intervalles de temps à une date
   * @param string $strDateField Nom du champ ou expression sql à traiter
   * @param string $iNb          Nombre d'intervalles à ajouter ou expression sql
   * @param char   $strInterval  Type d'intervalle : Y=année, M=mois, D=jour, H=heure
   * @return string
   */
  abstract public function getDateAdd($strDateField, $iNb, $strInterval);
  
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
   * Retourne l'expressino fournissant le timestamp unix correspondant à l'expression de type date passée en paramètre
   * @param string $dateField  expression de type date
   * @return string
   */
  abstract public function getUnixTimestamp($dateField);
}
