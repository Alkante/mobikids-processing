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

require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/AlkDbPlateform.php");

/**
 * @package Alkanet_Class_Pattern
 * 
 * @class AlkDbPlateformPgsql
 * @brief Classe qui implémente les méthodes de retour sql spécifiques Postgres
 */
class AlkDbPlateformPgsql extends AlkDbPlateform
{
  /*******************************
   * Méthodes gestion de requêtes
   *******************************/
  
  /**
   * Retourne une requête SQL neutre qui fonctionne constamment et retourne un résultat vide
   * @return string
   */
  public function getEmptyQuery()
  {
    return "select s.f from (select 0 as f) s where 1=0";
  }
  
  /**
   * Applique à la requête passée en paramètre, les modifications adéquates pour permettre la pagination
   * Les 3 paramètres sont passés en référence
   * Retourne true si la méthode a appliqué des changements, false sinon
   * @param string $strSql requête sql de base
   * @param int    $iFirst indice de début de pagination, =0 par défaut
   * @param int    $iLast  indice de fin de pagination, =-1 par défaut pour aucune pagination
   * @return boolean
   */
  public function applySqlPaginationLimits(&$strSql, $iFirst=0, $iLast=-1)
  {
    if( $iFirst>=0 && $iFirst <= $iLast ) {
      $nbEltParPage = ($iLast-$iFirst)+1;
      $strSql .= " limit ".$nbEltParPage." offset ".$iFirst;
      return true;
    }
    return false;
  }
  
  /**
   * Applique à la requête passée en paramètre, les modifications adéquates pour calculer le nombre total d'éléments sans la pagination 
   * Retourne true si la méthode a appliqué des changements, false sinon
   * @param string $strSql  requête sql de base
   * @return boolean
   */
  public function applySqlRowCountWithoutPagination(&$strSql)
  {
    return true;
  }
  
  /**
   * Retourne la requête SQL qui permet de récupérer le nombre de total de ligne sans pagination
   * @param string $strSql  requête sql de base
   * @return string
   */
  public function getSqlRowCountWithoutPagination($strSql)
  {
    return "select count(*) as ALK_NB_TOT from (".$strSql.") subQuery";
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
    $strSql  = "";
    $strCopy = "";
    $strWhere = "";    
    foreach($tabFieldsName as $strFieldNameDest => $strValueSrc ) {
      $strCopy .= ( $strCopy == "" ? "" : ", " ).
        $strFieldNameDest."=".
        ( $strValueSrc == "" 
          ? "s.".$strFieldNameDest
          : $strValueSrc ); 
    }
    foreach($tabFieldPkSrc as $strFielPkName => $FielPkValue) {
      $strWhere .= ( $strWhere == "" ? "" : " and " )." s.".$strFielPkName."=".$FielPkValue;
    }
    foreach($tabFieldPkDest as $strFielPkName => $FielPkValue) {
      $strWhere .= ( $strWhere == "" ? "" : " and " )." ".$strTableDest.".".$strFielPkName."=".$FielPkValue;
    }
    $strSql = "update ".$strTableDest." set ".
      $strCopy.
      " from ".$strTableSrc." s".
      ( $strWhere != "" ? " where ".$strWhere : "" );
    return $strSql;
  }
  
  /**
   * Retourne le code SQL permettant de créer une table (uniquement les champs typés)
   * @param strTableName  nom de la table ou tableau
   * @param tabFields     tableau contenant les informations sur les champs à créer
   * @return string
   */
  public function getSqlCreateTable($strTableName, $tabDataFields, $tableInherit=null)
  {
    if ( empty($tabDataFields) && $tableInherit===null ) { return ""; }
    $tabTypeAssoc = $this->getSqlType();
    $tabComments = array();
    $tabResFields = array();
    $tabCreateOptions = array();
    foreach($tabDataFields as $strFieldName => $tabData) {
      $tabType = explode("(", $tabData["type"]);
      $strType = strtolower($tabType[0]);
      $strTypeTest = trim(str_replace("[]", "", $strType));
      if( array_key_exists($strTypeTest, $tabTypeAssoc) ) {
        $strType = str_replace($strTypeTest, strtoupper($tabTypeAssoc[$strTypeTest]), $strType);
      } else {
        $this->triggerError("Type SGBD [".$strType."] non reconnu. ".$strTableName.".".$strFieldName." non ajouté.", E_USER_ERROR);
        continue; 
      }
      if( count($tabType)>1 ) {
        $strType .= "(".$tabType[1]; 
      }
      $tabResFields[] = strtolower($strFieldName)." ".$strType. " ".$tabData["dn"]; 
      if( isset($tabData["comment"]) ) {
        $tabComments[$strTableName.".".strtolower($strFieldName)] = $tabData["comment"];
      }
    }
    if ( $tableInherit!==null ){
      if ( is_array($tableInherit) ){
        $checks = (array)$tableInherit["check"];
        $tableInherit = $tableInherit["table"];
        foreach ($checks as $check){
          $tabResFields[] = "CHECK ( ".$check." ) ";
        }
      }
      $tabCreateOptions[] = " INHERITS (".$tableInherit.")";
    }
    $strSql = "CREATE TABLE ".$strTableName." (\n".
      implode(",\n  ", $tabResFields).
      "\n)\n". 
      implode("\n ", $tabCreateOptions);
    
    if( !empty($tabComments) ) {
      // fermeture create table fait avec le prefixe;\n du premier comment
      // ajout des commantaires sur les champs
      foreach($tabComments as $tableFieldName => $comment) {
        $strSql .= ";\nCOMMENT ON COLUMN ".$tableFieldName." IS '".str_replace("'", "''", $comment)."'";
      }
    }
    
    return $strSql;
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une table
   * @param strTableName  nom de la table ou tableau
   * @return string
   */
  public function getSqlDropTable($strTablename)
  {
    return "drop table if exists ".strtoupper($strTablename)." cascade";
  }  
  
  /**
   * Retourne le code SQL permettant de supprimer une vue
   * @param strViewName   nom de la vue
   * @return string
   */
  public function getSqlDropView($strViewName)
  {
    return "drop view if exists ".strtoupper($strViewName)." cascade";
  }
  
  /**
   * Retourne le code Sql permettant de supprimer une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @return string
   */
  public function getSqlDropPrimary($strTableName, $strPkName)
  {
    return "alter table ".strtoupper($strTableName).
      " drop constraint ".strtoupper($strPkName);
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
    return "create index ".strtoupper($strIndexName).
      " on ".strtoupper($strTableName)." (".strtoupper($strFieldName).")";
  }
  
 /**
   * Retourne le code SQL permettant de supprimer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @return string
   */
  public function getSqlDropIndex($strTableName, $strIndexName)
  {
    return "drop index ".strtoupper($strIndexName);
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
    return "alter table ".strtoupper($strTableName).
      " add constraint ".strtoupper($strFkName)." foreign key (".strtoupper($strFieldFk).")".
      " references ".strtoupper($strTablePk)." (".strtoupper($strFieldPk).") ".$strOption;
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une clé étrangère à une table
   * @param strTableName   nom de la table locale
   * @param strFkName      nom de la clé étrangère
   * @return string
   */
  public function getSqlDropConstraintForeignKey($strTableName, $strFkName)
  {
    return "alter table ".strtoupper($strTableName).
      " drop constraint ".strtoupper($strFkName);
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @return string
   */
  public function getSqlDropConstraintUnique($strTableName, $strUqName)
  {
    return "alter table ".strtoupper($strTableName).
      " drop constraint ".strtoupper($strUqName);
  }
  
  /**
   * Retourne le code SQL permettant de créer une séquence
   * @param strSeqName   nom de la séquence
   * @param iStart       indice de début de la séquence
   */  
  public function getSqlCreateSequence($strSeqName, $iStart)
  {
    return "create sequence ".strtoupper($strSeqName).
      " start ".$iStart." increment 1";    
  }
  
  /**
   * Retourne le code SQL permettant de supprimer une séquence
   * @param strSeqName   nom de la séquence
   * @return string
   */  
  public function getSqlDropSequence($strSeqName)
  {
    return "drop sequence if exists ".strtoupper($strSeqName);
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
    if( $strNewColumnName == "" ) {
      $strNewColumnName = $strColumnName;
    }
    if( $strTableName=="" || $strColumnName=="" ) {
      return "";
    }
      
    $strSql = "alter table ".strtoupper($strTableName);
    
    if( strtoupper($strColumnName) != strtoupper($strNewColumnName) ) {
      $tabSql[] = $strSql." rename ".strtoupper($strColumnName)." to ".strtoupper($strNewColumnName);
    }
    
    if( $strNewColumnType!="" ) {
      $strType = $this->getColumnType($strNewColumnType, $strNewColumnLength);
      if( $strType!==false ) {
        $tabSql[] = $strSql." alter column ".strtoupper($strNewColumnName)." type ".$strType;
      } else {
        return "";
      }
    }
    if( $strNewColumnDefault!="" ) {
      $tabSql[] = $strSql." alter column ".strtoupper($strNewColumnName).
        " set default ".$strNewColumnDefault;
    }
    if( $iNewColumnNullable!=-1 ) {
      $tabSql[] = $strSql." alter column ".strtoupper($strNewColumnName).
        ( $iNewColumnNullable==0 
          ? " set not null" 
          : " drop not null" );
    }
    
    return implode("; ", $tabSql);
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
   * @param columnLength2   Longueur max des données de la colonne
   * @return string, retourne FALSE en cas d'erreur
   */
  protected function getColumnType($columnType, $columnLength="", $columnLength2="")
  {
    $strRes = "";
    $columnType = strtoupper($columnType);
    if ( $columnLength2!="" ) {
      $columnLength2 = ", ".$columnLength2;
    }
       
    switch ( $columnType ){
      case "INT" :
        $strRes = "integer";
        if ( $columnLength!="" ){
          $strRes .= "(".$columnLength.")";
        }
      break;
      
      case "BIGINT" :
        $strRes = "bigint";
      break;      
            
      case "TEXT":
        $strRes = "text";
      break;
      
      case "VARCHAR":
        if ( $columnLength!="" ){
          $strRes = "varchar(".$columnLength.")";
        }
      break;
      
      case "FLOAT":
      case "DECIMAL":
        if ( $columnLength!="" && $columnLength2 !="" ){
          $strRes = "numeric(".$columnLength.$columnLength2.")";
        } else {
          $strRes = "double precision";
        }
      break;
      
      case "DATE":
        $strRes = "date";
      break;
      
      case "DATETIME":
        $strRes = "timestamp";
      break; 
      
      case "BOOLEAN":
        $strRes = "boolean";
      break; 
      
      case "BINARY":
        $strRes = "bytea";
      break; 
      
      case "SERIAL":
        $strRes = "serial";
      break; 
      
      default:
        $strRes = $columnType;
      break; 
    }
    if ( $strRes!="" ) {
      return $strRes;
    }
    return false;
  }
  
  /**
   * Retourne un tableau associatif fournissant la correspondances entre les types SGBD alkanet et ceux des SGBD
   * Les types Alkanet correspondent aux clés du tableau, les types SGBD correspondent aux valeurs
   * @return array 
   */
  public function getSqlType()
  {
    return 
      array("int"      => "integer", 
            "bigint"   => "bigint",
            "varchar"  => "varchar", 
            "time"     => "time",
            "date"     => "date", 
            "datetime" => "timestamp", 
            "float"    => "numeric",
            "decimal"  => "decimal",
            "text"     => "text",
            "binary"   => "bytea",
            "geometry" => "public.geometry",
            "polygon"  => "public.polygon",
            "line"     => "public.line",
            "point"    => "public.point",
            "boolean"  => "boolean",
            "serial"   => "serial",
            );
  }

    /**
   * Retourne l'expression SQL qui correspond à la fonction cast
   * @param string $strValue Nom du champ ou expression sql à traiter
   * @param string $strType  Type du champ ou expression sql à traiter
   * @return string
   */
  public function getCast($strValue, $strType)
  {
    $strSql = $strValue;
    $tabTypeAssoc = $this->getSqlType();
    $tabType = explode("(", $strType);
    $strTypeBase = strtolower($tabType[0]);
    if( array_key_exists($strTypeBase, $tabTypeAssoc) && $tabTypeAssoc[$strTypeBase] != "" ) {
      $strType = strtoupper($tabTypeAssoc[$strTypeBase]).(count($tabType)>1 ? "(".$tabType[1] : "");
    } else {
      trigger_error(__CLASS__."::".__METHOD__." : Type ".$strType." non reconnu pour le driver ".$this->driverName.".", E_USER_ERROR);
      return $strSql;
    }
    $strSql = "cast(".$strValue." as ".$strType.")";
    return $strSql;
  }
  
  /**
   *  Retourne le code sql des instructions "show tables" et "show tables like "
   * 
   * @param strLikeTable    Si non vide permet de faire un show tables like 
   * @return string SQL
   */
  public function getShowTables($strLikeTable="", $bOnlyTables=false)
  {
    $strSql = "select tablename".
      " from pg_tables".
      ( $strLikeTable != "" 
        ? " where ".$this->getLowerCase("tablename")." like ".$this->getLowerCase($this->dbLink->quote($strLikeTable))
        : "" ).
      ( !$bOnlyTables 
        ? " union".
          " select viewname as tablename".
          " from pg_views".
          ( $strLikeTable != "" 
            ? " where ".$this->getLowerCase("viewname")." like ".$this->getLowerCase($this->dbLink->quote($strLikeTable))
            : "" )
        : "" );
    return $strSql;
  }

  /**
   * Retourne la description des colonnes d'une table
   * @param strTableName    Nom de la table
   * @return dataset
   */
  public function getSqlTableColumns($strTableName)
  {
    return  "SELECT a.attnum as NUMB, a.attname as FIELD, t.typname as TYPE, a.attlen as LEN, a.atttypmod, a.attnotnull, a.atthasdef as DEF".
      " FROM pg_class c".
      "  inner join pg_attribute a on a.attrelid = c.oid" .
      "  inner join pg_type t on a.atttypid = t.oid" .
      " WHERE c.relname = '".strtolower($strTableName)."'".
      " and a.attnum > 0".
      " ORDER BY attnum";
  }

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
  public function compareSql($strField, $strCompare, $strValue, $strCaseOK, $strCaseNok)
  {
    return " (case when ".$strField." ".$strCompare." ".$strValue." then ".$strCaseOK." else ".$strCaseNok." end) ";
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
    return "nextval('".$strSequenceName."')";
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
    $strSql = "array_to_string(" .
      "array(select ".($bDistinct ? "distinct " : "").
      ( $bNullTest 
        ? " (case when ".$strField." is null then '' else ".$strField." end) "
        : $strField ).
      $strFrom."), ".
      $strSeparator.")";
    return $strSql;
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
    $nbParam = func_num_args();
    if( $nbParam == 0 ) {
      return "null";
    }
    
    $strRes = "";
    $strGlu = "";
    $strSep = "||";
    for($i=0; $i<$nbParam; $i++) {
      $strRes .= $strGlu.func_get_arg($i);
      $strGlu = $strSep;
    }
    return $strRes;
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
    if( $iLength == -1 ) {
      return "substr(".$strField.", ".$iPos."+1)";
    }
    return "substr(".$strField.", ".$iPos."+1, ".$iLength.")";
  }

  /**
   * Retourne l'expression SQL qui transforme en minuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getLowerCase($strField)
  {
    return "lower(".$strField.")";
  }
  
  /**
   * Retourne l'expression SQL qui transforme en majuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getUpperCase($strField)
  {
    return "upper(".$strField.")";
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
    return "lower(".$strField.") ".$strOp." lower(".$strVal.")";
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
    return "(now() - interval '".$this->deltaGMT." hour' + interval '".$this->deltaGMTServ." hour')";
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
    $strFormat = str_replace("HH","HH24", $strFormat);
        
    $strSql = ( !$bToDate ? "to_char" : "to_timestamp" )."(".$strDate.", '".$strFormat."')";
    $strSql = ( !$bToDate && $bCastToInt 
    		        ? "cast(".$strSql." as integer)" 
    		        : ( $bToDate && $bCastToInt 
                    ? "extract(epoch FROM ".$strSql.")::bigint" 
                    : $strSql ));
    return $strSql;
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
    $strSql = "to_timestamp(".$iTimestamp.")";
    if( !$bToDate ) {
      $strSql = $this->getDateFormat($strFormat, $strSql, false);
    }
    return $strSql;
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
    return "datediff(day, ".$strDateField1.", ".$strDateField2.")";
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
    $strSql = "null";
    $strInterval = strtoupper($strInterval);
    $tabTypeInterval = array("Y" => "year", "M" => "month", "D" => "day", "H" => "hour");
    if( !array_key_exists($strInterval, $tabTypeInterval) ) {
      return $strSql;
    }
    return "(".$strDateField." + (".$iNb." || ' ".$tabTypeInterval[$strInterval]."')::interval)";
  }
  
  /**
   * Retourne l'expressino fournissant le timestamp unix correspondant à l'expression de type date passée en paramètre
   * @param string $dateField  expression de type date
   * @return string
   */
  public function getUnixTimestamp($dateField)
  {
    return "extract(epoch from ".$dateField.")";
  }
}
