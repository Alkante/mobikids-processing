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
 * @class AlkDsPDO
 * Classe de représentation d'une requête préparée (héritage de PDOStatement) et de gestion du résultat (dataset)
 */
class AlkDsPDO extends PDOStatement implements Serializable
{
  /** référence sur l'objet de connexion ouverte */
  protected $dbPDO = null ;
  
  /** Nombre de lignes obtenues dans le résultat */
  protected $rowsCount;
  
  /** Nombre de lignes obtenues dans le résultat sans la pagination */
  protected $totalRowsCount;
  
  /** =true pour utilisation du cache interne, =false sinon */
  protected $bUseCache;
  
  /** cache interne mémorisant les lignes consultées */
  protected $rowsCache;
  
  /** indice du curseur de consultation du cache interne */
  protected $cursorCache;

  /** indice max du curseur de consultation du cache interne */
  protected $maxCursorCache;

  /** délai d'expiration du cache externe, =0 pour aucun cache externe */
  protected $cacheExpire;
  
  /** clé nomminative du cache externe */
  protected $cacheName;
  
  /** true si tous les éléments du dataset ont été consultés ou chargés en mémoire */
  protected $bFetchAll;
  
  /** true si fin du résultat atteint, false sinon */
  protected $bEos;
  
  /** tableau contenant les noms des colonnes */
  protected $tabFields;

  /** tableau contenant les types de colonnes */
  protected $tabTypes;
  
  /** fonction de conversion du résultat pour l'iterateur */
  protected $converter;
  
  /** =false pour le mode silencieux et continuation, =true pour le mode verbeux et arrêt en cas d'erreur */
  protected $bErr;
  
  /** Mémorise les paramètres de setFetchMode, ce qui évite de les répéter sur la méthode fetchData */
  protected $fetchStyle;
  protected $fetchArg;
  protected $fetchCtorArgs;
  
  
  /**
   * Constructeur par défaut de la classe
   * @param dbPDO  référence sur la connexion en cours
   */
  protected function __construct(AlkDbPDO $dbPDO=null)
  {
    $this->dbPDO          = $dbPDO;
    $this->rowsCount      = 0;
    $this->totalRowsCount = 0;
    $this->bUseCache       = false;
    $this->rowsCache      = array();
    $this->cursorCache    = -1;
    $this->cacheExpire    = 0;
    $this->cacheName      = "";
    $this->maxCursorCache = -1;
    $this->bFetchAll      = false;
    $this->bEos           = true;
    $this->tabFields      = array();
    $this->tabTypes       = array();
    $this->converter      = null;
    $this->bErr           = true;
    $this->fetchStyle    = PDO::FETCH_ASSOC;
    $this->fetchArg      = null;
    $this->fetchCtorArgs = null;
  }
  
  /**
   * Méthode de l'interface Serializable appelée par un serialize de cette classe
   * Retourne une chaine avec les éléments de la classe sérialisés
   * @return string
   */
  public function serialize()
  {
    $properties = get_class_vars($this);
    $values = array();
    foreach($properties as $property) {
      if( $property != "dbPDO" ) {
        $values[$property] = $this->$property;
      }
    }
    
    return serialize($values);
  }

  /**
   * Méthode de l'interface Serializable qui désérialise le paramètre fourni pour affecter les valeurs aux attributs de la classe
   * @param string $data   éléments sérialisés
   */
  public function unserialize($data)
  {
    $this->dbPDO = null;
    $properties = get_class_vars($this);
    $values = unserialize($data);
    foreach($properties as $property) {
      if( $property != "dbPDO" ) {
        $this->$property = $values[$property];
      }
    }
  }
  
  /**
   * Active ou désactive la mise en cache interne et externe avec les paramètres de durée d'expiration et de préfixe pour le cache externe (memcache).
   * Le cache interne est géré par la classe AlkDs
   * L'appel à fetchAll() et/ou l'utilisation de la pagination fixe bUseCache à true
   * Le préfixe est utilisé pour le flush du cache afin de ne pas tout effacer.
   * @param bUseInternalCache  false par défaut, true pour activer la mise en cache interne le temps d'exécution du script. Si iExpire>0, ce paramètre est automatiquement mis à true
   * @param iExpire            =0 par défaut, >0 pour mémoriser en cache externe le résutat de la requête avec un délai d'expiration de iExpire secondes
   * @param strCacheName       =alkanet par défaut, permet de regrouper les éléments cachés à l'aide de ce nom afin de gérer en live, la libération du cache
   * @return AlkDsPDO
   */
  public function setCache($bUseInternalCache=false, $iExpire=0, $strCacheName="alkanet")
  {
    $this->bUseCache = ( $this->bUseCache || $iExpire > 0 || $bUseInternalCache );
    $this->cacheExpire = $iExpire;
    $this->cacheName = $strCacheName;
    
    return $this;
  }
  
  /**
   * Exécute la requête avec gestion des paramètres, de la pagination et de la gestion des erreurs
   * Retourne true si l'exécution s'est exécutée, false sinon
   * @param array   $tabParams       tableau permettant d'associer les valeurs (bindValue) à la requête préparée
   * @param boolean $bPagination     =true si une pagination est démandée, false sinon
   * @param boolean $bTotalRowCount  =true si le résultat total sans pagination est à récupérer, =false sinon
   * @param string  $strSqlRowCount  requête sql pour obtenir le nombre total de lignes (sans la pagination)
   * @param boolean $bErr
   * @return boolean
   */
  public function executeQuery($tabParams=array(), $bPagination=false, $bTotalRowCount=false, $strSqlRowCount="", $bErr=true)
  {
    $this->bErr = $bErr;
    if( !$this->bErr ) {
      ob_start();
    }
    if( is_array($tabParams) && !empty($tabParams) ) {
      foreach($tabParams as $paramName => $value) {
        if( is_array($value) ) {
          // indice 0 : valeur
          // indice 1 : type : constante PDO::PARAM_XXX : PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL, PDO::PARAM_LOB
          $this->bindValue($paramName, $value[0], $value[1]);
        } else {
          // type par défaut : PDO::PARAM_STR
          $this->bindValue($paramName, $value);
        }
      }
    }
    
    // exécution
    $bRes = false;
    try {
      $bRes = $this->execute();
    } catch( PDOException $e ) {
      $bRes = false;
      throw new AlkException($e->getMessage()." : ".$this->queryString, __CLASS__, __METHOD__, ($this->bErr ? E_USER_ERROR : 0), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
    }
    
    $this->totalRowsCount = -1; // non calculé
    $this->rowsCount      = -1; // non calculé
    
    if( $bRes ) {
      $this->rowsCount = $this->rowCount();
      if( $bPagination ) {
        $this->bUseCache = true;
        switch( $this->dbPDO->getDriverName() ) {
          case AlkDbPDO::DRIVER_MYSQL:
          case AlkDbPDO::DRIVER_PGSQL:
            // exécution de la seconde requête pour obtenir le compte total
            if( $strSqlRowCount != "" && $bTotalRowCount ) {
              try {
                $ds = $this->dbPDO->query($strSqlRowCount);
              } catch( PDOException $e ) {
                $ds = false;
                throw new AlkException($e->getMessage(), __CLASS__, __METHOD__, ($this->bErr ? E_USER_ERROR : 0), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
              }
              if( $ds instanceof AlkDsPDO ) {
                $this->totalRowsCount = $ds->fetchColumn();
                $ds->closeCursor();
              }
            }
            break;
            
          case AlkDbPDO::DRIVER_ORACLE:
            // lecture du premier enregistrement pour récupérer l'information de compte total
            try {
              $dr = $this->fetch();
            } catch(PDOExeption $e) {
              $dr = false;
              throw new AlkException($e->getMessage(), __CLASS__, __METHOD__, ($this->bErr ? E_USER_ERROR : 0), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
            }
              
            if( is_array($dr) ) {
              $this->rowsCount      = $dr["ALK_NB_RES"];
              $this->totalRowsCount = $dr["ALK_NB_TOT"];
            }
            break;
        }
      }
    }
    
    if( $this->totalRowsCount == -1 ) {
      $this->totalRowsCount = $this->rowsCount;
    }

    $this->moveFirst();
    
    if( !$this->bErr ) {
      ob_end_clean();
    }
  }
  
  /**
   * Enregistre dans memCache le contenu de cette classe
   */
  protected function saveToExternalCache()
  {
    $this->maxCursorCache = $this->rowsCount;
    $this->bFetchAll = true;
    if( $this->bUseCache && $this->cacheExpire>0 ) {
      AlkFactory::memCacheSetData($this->cacheName, $this->queryString, $this, $this->cacheExpire);
    }
  }
  
  /**
   * Mémorise le contenu du dataSet en mémoire pour permettre la mise en cache par la suite
   * @param mixed $dr  contenu de l'enregistrement de type dépendant de PDO::ATTR_DEFAULT_FETCH_MODE
   */
  protected function saveToInternalCache($dr)
  {
    if( is_bool($dr) && !$dr ) {
      return;
    }
  
    $this->rowsCache[] = $dr;
    $this->maxCursorCache = count($this->rowsCache);
  
    if( $this->maxCursorCache == $this->rowsCount && !$this->bFetchAll ) {
      $this->saveToExternalCache();
    }
  }
  
  /**
   * @see PDOStatement::setFetchMode()
   */
  public function setFetchMode($mode, $params = NULL)
  {
    call_user_func_array(array("parent", "setFetchMode"), func_get_args());
    $this->fetchStyle    = $mode;
    $this->fetchArg      = $params;
    $this->fetchCtorArgs = ( func_num_args()>=3 && is_array(func_get_arg(2)) ? func_get_arg(2) : null );
  }
  
  /**
   * Appelle la méthode PDOStatement::fetchAll(), mémorise le résultat dans cette classe 
   * puis retourne ce résultat de type array
   * @see PDOStatement::fetchAll()
   * @return array
   */
  public function fetchAll($how = NULL, $class_name = NULL, $ctor_args = NULL)
  {
    if( is_null($how) ) {
      $how        = $this->fetchStyle;
      $class_name = $this->fetchArg;
      $ctor_args  = $this->fetchCtorArgs;
    }
    
    // le nombre d'élément est déjà calculé 
    $this->rowsCache = parent::fetchAll($how, $class_name, $ctor_args);
    if( !is_array($this->rowsCache) ) {
      $this->rowsCache = array();
    }
    // enregistrement au cache externe
    $this->saveToExternalCache();
    // positionne le cursor sur le premier élément
    $this->moveFirst();
    // retourne le dataSet
    return $this->rowsCache; 
  }
  
  /**
   * Le type retourné dépend de la valeur de PDO::ATTR_DEFAULT_FETCH_MODE
   * Il est possible de personnaliser le fetch en passant à cette fonction, les paramètres attendus par la fonction fetch()
   * @deprecated since version 3.6, utiliser fetchDr() pour l'équivalent ou fetchArray() pour retourner un tableau nommé
   * @return mixed si ok, false sinon
   */
  public function getRowIter()
  {
    return $this->fetch();
  }
  
  /**
   * Retourne l'enregistrement correspondant à l'indice courant (cursorCache) puis passe au suivant
   * Retourne false si EOF est atteint
   * Le type retourné dépend de la valeur de PDO::ATTR_DEFAULT_FETCH_MODE
   * Il est possible de personnaliser le fetch en passant à cette fonction, les paramètres attendus par la fonction fetch()
   * @see PDOStatement::fetch() : http://php.net/manual/fr/pdostatement.fetch.php
   * @param int $fetch_style        = PDO::ATTR_DEFAULT_FETCH_MODE par défaut, Ce paramètre n'est pas utilisé, l'attribut self::fetch_style est utilisé
   * @param int $cursor_orientation = PDO::FETCH_ORI_NEXT 
   * @param int $cursor_offset      = 0 
   * @return mixed si ok, false sinon
   */
  public function fetch($fetch_style=PDO::FETCH_ASSOC, $cursor_orientation=PDO::FETCH_ORI_NEXT, $cursor_offset=0)
  {
    $dr = false;
    if( $this->cursorCache>=0 && $this->cursorCache<$this->rowsCount ) {
      if( !$this->bErr ) {
        ob_start();
      }
      
      if( !$this->bFetchAll ) {
        try {
          switch( $this->fetchStyle ) {
            case PDO::FETCH_COLUMN: $dr = $this->fetchColumn($this->fetchArg); break;
            case PDO::FETCH_CLASS: 
            case  PDO::FETCH_OBJ:   $dr = $this->fetchObject($this->fetchArg, $this->fetchCtorArgs); break;
            default:                $dr = parent::fetch($this->fetchStyle, $cursor_orientation, $cursor_offset); break;
          }
        } catch(PDOException $e) {
          throw new AlkException($e->getMessage(), __CLASS__, __METHOD__, ($this->bErr ? E_USER_ERROR : 0), E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO", $e);
          $dr = false;
        }
        if( $this->bUseCache ) {
          $this->saveToInternalCache($dr);
        } else {
          $this->maxCursorCache = max($this->maxCursorCache, $this->cursorCache+1);
        }
      } else {
        $dr = $this->rowsCache[$this->cursorCache];
      }
      $this->moveNext();
  
      if( !$this->bErr ) {
       ob_end_clean();
      }
    }
  
    return $dr;
  }
    
  /**
   * Retourne l'ensemble des résultats en JSON
   * @param string $strTableName   Le nom de la table dans la base de données
   * @return string
   */
  public function getJson($strTableName="")
  {
    return ( $strTableName==""
             ? json_encode($this->rowsCache)
             : "{".$strTableName.":".json_encode( $this->rowsCache)."}" );
  }
    
  /**
   * Retourne le booléen indiquant que la lecture des résultats est terminée
   * @return boolean
   */
  public function isEndOfDs()
  {
    return $this->bEos || ($this->cursorCache==$this->rowsCount);
  }
  
  /**
   * Retourne le booléen indiquant que la lecture des résultats est terminée
   * @deprecated since 3.6 utiliser isEndOfDs
   * @return boolean
   */
  public function isEndOfFile()
  {
    return $this->isEndOfDs();
  }
  
  /**
   * Place le curseur sur le premier enregistrement
   */
  public function moveFirst()
  {
    if( ! $this->bUseCache ) {
      $this->bUseCache = true;
      //trigger_error( htmlentities('moveFirst nécessite que le cache soit activé pour fonctionner'), E_USER_WARNING);
    }
    if( $this->rowsCount > 0 ) {
      $this->cursorCache = 0;
      $this->maxCursorCache = max($this->maxCursorCache, $this->cursorCache);
      $this->bEos = false;
    } else {
      $this->cursorCache = -1;
      $this->bEos = true;
    }
  }
  
  /**
   * Place le curseur sur l'enregistrement précédent
   */
  public function movePrev()
  {
    if( $this->rowsCount>0 && $this->cursorCache>0 ) {
      $this->cursorCache--;
    } else {
      $this->cursorCache = -1;
      $this->bEos = true;
    }
  }
  
  /**
   * Place le curseur sur l'enregistrement suivant
   */
  public function moveNext()
  {
    $iLimit = ( $this->bFetchAll
                ? $this->rowsCount
                : $this->maxCursorCache );
    if( $this->rowsCount>0 && $this->cursorCache<$iLimit ) {
      $this->cursorCache++;
    } else {
      $this->cursorCache = -1;
      $this->bEos = true;
    }
  }
  
  /**
   * Place le curseur sur le dernier enregistrement
   */
  public function moveLast( )
  {
    if( !$this->bFetchAll ) {
      throw new AlkException("Méthode non autorisée tant que le dataset n'est pas totalement parcouru.", 
                             __CLASS__, __METHOD__, E_USER_ERROR, E_USER_ERROR, __FILE__, __LINE__, "Alkanet.PDO");
    }
  
    if ( $this->rowsCount > 0 ) {
      $this->cursorCache = $this->rowsCount-1;
    } else {
      $this->cursorCache = -1;
      $this->bEos = true;
    }
  }
  
  /**
   * Retourne le nombre d'éléments présents dans le dataset
   * @return int
   */
  public function getRowCount()
  {
    return $this->rowsCount;
  }
  
  /**
   * Retourne le nombre d'éléments présents dans le dataset
   * @deprecated since 3.6 utiliser getRowCount()
   * @return int
   */
  public function getCountDr()
  {
    return $this->getRowCount();
  }
  
  /**
   * Retourne le nombre total d'éléments lorsque le résultat est paginé
   * @deprecated since 3.6 utiliser getRowsCount()
   * @return int
   */
  public function getTotalRowCount()
  {
    return ( $this->totalRowsCount == -1 ? $this->rowsCount : $this->totalRowsCount );
  }
  
  /**
   * Retourne le nombre total d'éléments lorsque le résultat est paginé
   * @deprecated since 3.6 utiliser getRowsCount()
   * @return int
   */
  public function getCountTotDr()
  {
    return $this->getTotalRowCount();
  }
  
  /**
   *  Retourne l'indice courant de lecture dans le dataset (NB: =iCurDr, =indice du dr suivant(faire -1))
   * @return int
   */
  public function getCurrentIndex()
  {
    return $this->cursorCache;
  }
  
  /**
   * Retourne le type du numéro du champ
   * Retourne 0 si chaine, 1 si nombre, 2 si date
   * Retourne -1 sur la position est incorrecte
   * @return int
   */
  public function getFieldType($iFieldPosition)
  {
    $pdoType = $this->getFieldPDOType($iFieldPosition);
    $alkType = -1;
    switch( $pdoType ) {
      case PDO::PARAM_BOOL:
      case PDO::PARAM_INT: $alkType = 1; break;
      case PDO::PARAM_STR:
      case PDO::PARAM_LOB: $alkType = 0; break;
      /** @todo : type date ? */
    }
    return $alkType;
  }

  /**
   * Retourne le type du numéro du champ
   * Retourne l'une des constantes PDO::PARAM_xxx
   * Retourne -1 sur la position est incorrecte
   * @return int
   */
  public function getFieldPDOType($iFieldPosition)
  {
    if( empty($this->tabTypes) ) {
      $this->setFields();
    }
    if( $iFieldPosition >= 0 && $iFieldPosition < count($this->tabTypes) ) {
      return $this->tabTypes[$iFieldPosition];
    }
    return "-1";
  }
  
  /**
   * Retourne le type du numéro du champ
   * Retourne 0 si chaine, 1 si nombre, 2 si date
   * Retourne chaine vide si la position est incorrecte
   * @return string
   */
  public function getFieldName($iFieldPosition)
  {
    if( empty($this->tabFields) ) {
      $this->setFields();
    }
    if( $iFieldPosition >= 0 && $iFieldPosition < count($this->tabFields) ) {
      return $this->tabFields[$iFieldPosition];
    }
    return "";
  }
  
  /**
   * Retourne le tableau des noms de champ contenus dans la requête résultat
   * @return array
   */
  public function getFields()
  {
    if( empty($this->tabFields) ) {
      $this->setFields();
    }
    return $this->tabFields;
  }

  /**
   * Mémorise le nom et le type des champs de la requête résultat
   */
  protected function setFields()
  {
    $nbColumn = $this->columnCount();
    for($iField=0; $iField<=$nbColumn; $iField++) {
      $tabMeta = $this->getColumnMeta($iField);
      $this->tabFields[] = $tabMeta['name'];
      $this->tabTypes[]  = $tabMeta['pdo_type'];
    }
  }
  
  /**
   * Tri le dataSet après lecture dans la source donnée
   * Effectue un tri à plusieurs niveaux
   * Nécessite de connaite les noms de champ : ID et ID_PERE
   * le dataset doit être déjà trié par niv puis par rang
   * Ne fonctionne qu'en mode PDO::FETCH_ASSOC ou PDO::FETCH_NUM où les paramètres devront prendre l'indice des colonnes. 
   *
   * @param strFieldId  Nom du champ ID dans la requeête
   * @param strFieldIdp Nom du champ ID_PERE dans la requête
   */
  public function setTree($strFieldId, $strFieldIdp)
  {
    $tabNoeud = array();   // contient l'ensemble des noeuds
    $tabIndex = array();   // contient l'index inverse id -> cpt
    $stack    = array();
  
    if( !$this->bFetchAll ) {
      $this->fetchAll();
    }
  
    for($i=0; $i< $this->rowsCount; $i++) {
      $drTmp = $this->rowsCache[$i];
  
      $idFils = $drTmp[$strFieldId];
      $idPere = $drTmp[$strFieldIdp];
      $tabNoeud[$i]["FILS"] = array();
      $tabNoeud[$i]["ID"] = $idFils;
      $tabNoeud[$i]["OK"] = false;
  
      // memorize the first level id in a stack
      array_push($stack, $i);
  
      // met a jour la table index secondaire
      $tabIndex[$idFils] = $i;
  
      // ajoute l'id fils a l'id pere
      if( $idPere > 0 ) {
        if( array_key_exists($idPere, $tabIndex) == true ) {
          $iPere = $tabIndex[$idPere];
          array_push($tabNoeud[$iPere]["FILS"], $i);
        }
      }
    }
  
    // parse the sons
    $tabDr2 = array();
    $j = 0;
    while( count($stack) > 0 ) {
      $i = array_shift($stack);
      if( $tabNoeud[$i]["OK"] == false ) {
        $tabNoeud[$i]["OK"] = true;
        $tabDr2[$j] = $this->rowsCache[$i];
        $j++;
  
        $nbFils = count($tabNoeud[$i]["FILS"]);
        for ($k=$nbFils-1; $k>=0; $k--) {
          $if = $tabNoeud[$i]["FILS"][$k];
          array_unshift($stack, $if);
        }
      }
      $i++;
    }
  
    $tabNoeud = null;
    $tabIndex = null;
    $this->rowsCache = $tabDr2;
  }
  
  /**
   * @see Iterator::current()
   *
   * A foreach loop will call Iterator functions in the following order :
   * rewind -> valid -> current -> key -> next -> valid -> current -> key -> next ... valid
   * End of loop occurs when valid returns false
   *
   * @return array the current element
   */
  public function current()
  {
    // return the current element
    return $this->rowsCache[$this->cursorCache];
  }
  
  /**
   * @see Iterator::next()
   */
  public function next()
  {
    // set position to the next element
    // nothing to do, Iterator::valid() calls AlkDs::getRowIter which already calls AlkDs::moveNext()
  }
  
  /**
   * @see Iterator::key()
   * @return int index key of the current element
   */
  public function key()
  {
    // return key index of the the current position
    return $this->cursorCache - 1; // zero-based index
  }
  
  /**
   * @see Iterator::valid()
   * @return boolean true if current element is valid, false when the loop should terminate
   */
  public function valid()
  {
    // tells if the current element is valid (used to terminate the loop)
    $curDr = $this->fetch();
    if( $curDr != false && 
        ((is_string($this->converter)&& $this->converter!="") || 
         (is_array($this->converter) && !empty($this->converter))) ) {
      $curDr = call_user_func_array($this->converter, array($this, $curDr));
    }
    return $curDr != false;
  }
  
  /**
   * @see Iterator::rewind()
   */
  public function rewind()
  {
    // go back to the first position
    $this->moveFirst();
  }
  
  /**
   * Fixe une fonction de conversion pour obtenir le résultat de l'itérateur
   * @param mixed $_converter  chaine ou tableau correspondant à un appel de fonction ou de méthode
   */
  public function setConverter($converter)
  {
    $this->converter = $converter;
  }
}
