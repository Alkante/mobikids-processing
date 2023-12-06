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
 * @package Alkanet_Class_Pattern
 * 
 * @interface AlkIntDbPlateform
 * @brief Interface qui propose une api dédiée à la génération de code sql selon les plates-formes (mysql, postgres ou oracle)
 */
interface AlkIntDbPlateform
{
  
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
  public function getSqlCopyRowFromTableToTable($strTableSrc, $strTableDest, $tabFieldPkSrc, $tabFieldPkDest, $tabFieldsName);
  
  /**
   * Retourne le code SQL permettant de créer une table (uniquement les champs typés)
   * @param strTableName  nom de la table ou tableau
   * @param tabFields     tableau contenant les informations sur les champs à créer
   * @return string
   */
  public function getSqlCreateTable($strTableName, $tabDataFields, $tableInherit=null);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer un ensemble de tables
   * @param tabData        tableau contenant les informations sur les tables à créer
   * @param tabInheritance tableau contenant les caractéristiques éventuelles d'héritage entre tables (valable en Postgrès)
   * @param dropMode       mode d'affichage de l'instruction préalable DROP TABLE : none=non visible, comment=visible en commentaire, drop=visible et exécutable 
   * @return array
   */
  public function getTabSqlCreateTable($tabData, $tabInheritance=array(), $dropMode="none");
  
  /**
   * Retourne le code SQL permettant de supprimer une table
   * @param strTableName  nom de la table ou tableau
   * @return string
   */
  public function getSqlDropTable($strTablename);
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de tables
   * @param tabData  tableau contenant les informations sur les tables à supprimer
   * @return array
   */
  public function getTabSqlDropTable($tabData);
  
  /**
   * Retourne le code Sql permettant d'ajouter une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @param strFieldList  liste des champs caractérisant la clé primaire
   * @return string 
   */
  public function getSqlAddPrimary($strTableName, $strPkName, $strFieldList);
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à créer
   * @return array
   */
  public function getTabSqlAddPrimary($tabData);
  
  /**
   * Retourne le code Sql permettant de supprimer une clé primaire à une table
   * @param strTableName  nom de la table
   * @param strPkName     nom de la clé primaire
   * @return string
   */
  public function getSqlDropPrimary($strTableName, $strPkName);
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés primaires à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés primaires à supprimer
   * @return array
   */
  public function getTabSqlDropPrimary($tabData);
  
  /**
   * Retourne le code SQL permettant de créer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @param strFieldName   nom du champ
   * @return string
   */
  public function getSqlCreateIndex($strTableName, $strIndexName, $strFieldName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlCreateIndex($tabData);
  
 /**
   * Retourne le code SQL permettant de supprimer un index sur un champ d'une table
   * @param strTableName   nom de la table
   * @param strIndexName   nom de l'index
   * @return string
   */
  public function getSqlDropIndex($strTableName, $strIndexName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des index à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les index à créer
   * @return array
   */
  public function getTabSqlDropIndex($tabData);

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
  public function getSqlAddConstraintForeignKey($strTableName, $strFkName, $strFieldFk, $strTablePk, $strFieldPk, $strOption="");

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlAddConstraintForeignKey($tabData);
  
  /**
   * Retourne le code SQL permettant de supprimer une clé étrangère à une table
   * @param strTableName   nom de la table locale
   * @param strFkName      nom de la clé étrangère
   * @return string
   */
  public function getSqlDropConstraintForeignKey($strTableName, $strFkName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des clés étrangères à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropConstraintForeignKey($tabData);

  /**
   * Retourne le code SQL permettant d'ajouter une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @param strFieldList   liste des champs caractérisant l'unicité
   * @return string
   */
  public function getSqlAddConstraintUnique($strTableName, $strUqName, $strFieldList);

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlAddConstraintUnique($tabData);
  
  /**
   * Retourne le code SQL permettant de supprimer une contrainte d'unicité à une table
   * @param strTableName   nom de la table
   * @param strUqName      nom de la contrainte
   * @return string
   */
  public function getSqlDropConstraintUnique($strTableName, $strUqName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer des contraintes d'unicité à un ensemble de tables
   * @param tabData  tableau contenant les informations sur les contraintes d'unicité à créer
   * @return array
   */
  public function getTabSqlDropConstraintUnique($tabData);

  /**
   * Retourne le code SQL permettant de créer une vue
   * @param strViewName   nom de la vue
   * @param strSql        code sql de la vue
   */
  public function getSqlCreateView($strViewName, $strSql);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de créer des vues
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlCreateView($tabData, $dropMode="none");
  
  /**
   * Retourne le code SQL permettant de supprimer une vue
   * @param strViewName   nom de la vue
   * @return string
   */
  public function getSqlDropView($strViewName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer une vue
   * @param tabData  tableau contenant les informations sur les clés étrangères à créer
   * @return array
   */
  public function getTabSqlDropView($tabData);

  /**
   * Retourne le code SQL permettant de créer une séquence
   * @param strSeqName   nom de la séquence
   * @param iStart       indice de début de la séquence
   */  
  public function getSqlCreateSequence($strSeqName, $iStart);

  /**
   * Retourne un tableau contenant les requetes SQL permettant d'ajouter un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à créer
   * @return array
   */
  public function getTabSqlCreateSequence($tabData);
  
  /**
   * Retourne le code SQL permettant de supprimer une séquence
   * @param strSeqName   nom de la séquence
   * @return string
   */  
  public function getSqlDropSequence($strSeqName);

  /**
   * Retourne un tableau contenant les requetes SQL permettant de supprimer un ensemble de séquences
   * @param tabData  tableau contenant les informations sur les séquences à supprimer
   * @return array
   */  
  public function getTabSqlDropSequence($tabData);
  
  /**
   * Retourne un tableau contenant les requetes SQL permettant de modifier la structure de tables
   * @param tabData  tableau contenant les informations sur les tables à modifier
   * @return array
   */  
  public function getTabSqlAlterTable($tabData);

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
                                                     $strColumnLength="", $strColumnDefault="", $iColumnNullable=-1);

  /**
   * Retourne la requête sql effectuant un alter table drop column
   * Retourne une chaine vide si erreur
   *
   * @param strTableName       Nom de la table
   * @param strColumnName      Nom de la colonne
   * @return string
   */
  public function getSqlAlterTableDropColumn($strTableName, $strColumnName);

  /***********************************
   * Méthodes fonctions sql avancées
   **********************************/
  
  /**
   * Retourne l'expression SQL qui correspond à la fonction cast
   * @param string $strValue Nom du champ ou expression sql à traiter
   * @param string $strType  Type du champ ou expression sql à traiter
   * @return string
   */
  public function getCast($strValue, $strType);
  
  /**
   *  Retourne le code sql des instructions "show tables" et "show tables like "
   * 
   * @param strLikeTable    Si non vide permet de faire un show tables like 
   * @return string SQL
   */
  public function getShowTables($strLikeTable="") ;

  /**
   * Retourne la description des colonnes d'une table
   * @param strTableName    Nom de la table
   * @return dataset
   */
  public function getSqlTableColumns($strTableName);

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
  public function compareSql($strField, $strCompare, $strValue, $strCaseOK, $strCaseNok);

  /**
   * Retourne le code SQL permettant de récupérer un numéro de séquence
   * Ce code sql peut-être intégrer dans une requête de type insert
   * @param string  $strSequenceName  nom de la séquence
   * @param boolean $bUpdateSeq       =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @return string
   */
  public function getSqlNextSeqId($strSequenceName, $bUpdateSeq=true);
  
  /**
   * Obtenir le prochain identifiant à inserer dans la table strTable
   * Retourne un entier : le prochain id
   * @param strTable    Nom de la table
   * @param strField    Nom du champ id
   * @param strSequence Nom de la sequence associée
   * @param boolean $bUpdateSeq       =true par défaut pour incrémenter puis retourner la valeur, =false pour ne retourner que la valeur
   * @deprecated since version 3.6 use getSqlSeqId()
   * @return int
   */
  public function getStrNextId($strTable, $strField, $strSequence="", $bUpdateSeq=true);

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
  public function getGroupConcat($strField, $strSeparator="','", $strOrder="", $bDistinct=false, $strFrom="", $bNullTest=true);
  
  /************************************
   * Méthodes de traitement de chaine
   ************************************/
  
  /**
   * Retourne l'expression SQL qui fournit la concatenation d'un nombre indéfinit de paramètres
   * @return string
   */
  public function getConcat();
  
  /**
   * Retourne l'expression SQL qui fournit une sous-chaine
   * @param sstring $strField Nom du champ ou expression sql à traiter
   * @param int     $iPos     Position de départ (premier caractère = 0)
   * @param int     $iLength  Longueur de la sous-chaine (facultatif), =-1 par défaut pour signifier la fin de chaine
   * @return string
   */
  public function getSubstring($strField, $iPos, $iLength=-1);

  /**
   * Retourne l'expression SQL qui transforme en minuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getLowerCase($strField);
  
  /**
   * Retourne l'expression SQL qui transforme en majuscules une expression
   * @param string $strField   Nom du champ ou expression sql à traiter
   * @return string
   */
  public function getUpperCase($strField);
  
  /**
   * Retourne l'expression sql permettant de faire une comparaison de chaines sans tenir compte des caractères accentués
   * Ne fonctionne réellement que sous Oracle. Pour les autres, une simple comparaison en minuscules est réalisée.
   * @param string $strField Nom du champ de la table
   * @param string $strOp    Operateur de test SQL : like, =
   * @param string $strVal   Chaine de comparaison qui doit etre traitee par ora_analyseSQL auparavant
   * @deprecated since version 3.6
   * @return string
   */
  public function getStrConvert2ASCII7($strField, $strOp, $strVal);
  
  /************************************
   * Méthodes de traitement de date
   ************************************/
  
  /**
   * Retourne l'expression SQL qui fournit la date-heure système en tenant compte du fuseau horaire du serveur et du client
   * @return string
   */
  public function getDateCur();

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
  public function getDateFormat($strFormat, $strDate, $bToDate=true, $bCastToInt=false);

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
  public function getDateFromTimestamp($strFormat, $iTimestamp, $bToDate=true);
  
  /**
   * Retourne l'expression sql qui donne le nombre de jours qui sépare les 2 dates
   * Retourne le résultat de $strDateField1-$strDateField2
   * @param string $strDateField1    Nom du champ ou expression sql de type date à traiter contenant la première date
   * @param string $strDateField2    Nom du champ ou expression sql de type date à traiter contenant la seconde date
   * @return string
   */
  public function getDateDiff($strDateField1, $strDateField2);
  
    /**
   * Retourne l'expression SQL qui permet d'additionner des intervalles de temps à une date
   * @param string $strDateField Nom du champ ou expression sql à traiter
   * @param string $iNb          Nombre d'intervalles à ajouter ou expression sql
   * @param char   $strInterval  Type d'intervalle : Y=année, M=mois, D=jour, H=heure
   * @return string
   */
  public function getDateAdd($strDateField, $iNb, $strInterval);
  
  /**
   * Retourne l'expression sql qui donne le nombre de jour entre deux dates
   * @param string strDateFrom   Valeur de la date supérieure
   * @param string strDateTo     Valeur de la date inférieure 
   * @deprecated since version 3.6
   * @param string
   */
  public function getNbDaysBetween($strDateFrom, $strDateTo);
  
}
