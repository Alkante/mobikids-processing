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
 * @class AlkDrPDO
 * Classe d'enregitrement PDO pour maintenir la rétro-compatiblité Alkanet < 3.6
 */
class AlkDrPDO implements ArrayAccess
{
  /** propriétés de gestion */
  protected $__fieldsCount;
  protected $__fields;
  protected $__fieldsIdx;
  protected $__fieldsUpper;

  /** les champs sont ajoutés automatiquement par la commande PDOStatement::fetch() */
  
  /**
   * Constructeur par défaut
   */
  public function __construct()
  {
    $this->__fields      = array();
    $this->__fieldsIdx   = array();
    $this->__fieldsUpper = array();
    $this->__fieldsCount = -1;
    $this->setFields();
  }

  /**
   * Mémorise les champs de la classe dans le tableau __fields
   * et calcul le nombre de champs
   */
  protected function setFields()
  {
    $this->__fields = get_object_vars($this);
    unset($this->__fields["__fields"]);
    unset($this->__fields["__fieldsIdx"]);
    unset($this->__fields["__fieldsUpper"]);
    unset($this->__fields["__fieldsCount"]);
    $this->__fieldsCount = count($this->__fields);
    $this->__fieldsIdx   = array_keys($this->__fields);
    $this->__fieldsUpper = array_flip(array_map("strtoupper", $this->__fieldsIdx));
  }
  
  /**
   * Retourne la valeur du champs passé en paramètre
   * Retourne la valeur par défaut si le champ n'existe pas
   * @param string  $fieldName      nom du champs
   * @param string  $defaultValue   Valeur par défaut si le champ n'est pas trouvé dans oRow
   * @return string
   */
  public function getValueName($fieldName, $defaultValue="")
  {
    if( isset($this->__fields[$fieldName]) ) {
      return $this->__fields[$fieldName];
    }
    $fieldNameUpper = strtoupper($fieldName);
    if( isset($this->__fieldsUpper[$fieldNameUpper]) ) {
      return $this->__fields[$this->__fieldsIdx[$this->__fieldsUpper[$fieldNameUpper]]];
    }
    return $defaultValue;
  }
  
  /**
   * Met à jour la valeur du champs passé en paramètre
   * @param string  $fieldName      nom du champs
   * @param string  $value   				valeur du champ
   */
  public function setValueName($fieldName, $value)
  {
  	if( isset($this->__fields[$fieldName]) ) {
  		$this->__fields[$fieldName] = $value;
  	}
  	$fieldNameUpper = strtoupper($fieldName);
  	if( isset($this->__fieldsUpper[$fieldNameUpper]) ) {
  		$this->__fields[$this->__fieldsIdx[$this->__fieldsUpper[$fieldNameUpper]]] = $value;
  	}
  }

  /**
   * Retourne le nom du champ correspondant à l'indice fourni
   * @param int $iNumField indice du champs dans la requête(indicé à 0 à n-1)
   * @return string
   */
  public function getFieldName($iNumField)
  {
    return ( $iNumField >=0 && $iNumField<$this->__fieldsCount ? $this->__fieldsIdx[$iNumField] : "" );
  }
  
  /**
   * Retourne le nombre de champs dans l'enregistrement
   */
  public function getCountFields()
  {
    return $this->__fieldsCount;
  }
  /**
   * Retourne la valeur du champs identifié par son numéro d'ordre dans la requete
   * Retourne la valeur par défaut si cet identifiant est hors limite
   * @param int    $iNumField     indice du champs dans la requête(indicé à 0 à n-1)
   * @param string $defaultValue  valeur par défaut, = "" par défaut
   * @return string
   */
  public function getValueNum($iNumField, $defaultValue="")
  {
    $fieldName = ( $iNumField >=0 && $iNumField<$this->__fieldsCount ? $this->__fieldsIdx[$iNumField] : "" );
    return ( $fieldName == ""
             ? $defaultValue
             : $this->getValueName($fieldName, $defaultValue) );
  }
  
  /**
   * Retourne dans un tableau la liste des champs
   * @return array
   */
  public function getFields()
  {
    return $this->__fields;
  }
  
  /**
   * Retourne le dataRow dans un tableau associatif avec comme clé,
   * Force les noms de colonne en majuscules si bUpper=true, en minuscules sinon
   * @param bool bUpper  =true par défaut pour transformer les clés en majuscules, =false pour les minuscules
   * @return array
   */
  public function getDataRow($bUpper=true)
  {
    return array_change_key_case($this->__fields, ( $bUpper ? CASE_UPPER : CASE_LOWER ));
  }
  
  /**
   * Retourne le datarow au format json
   * @param string $strDrName  Le nom du DR, =vide par défaut
   * @return string
   */
  public function getJson($strDrName="")
  {
    return ( $strDrName == ""
             ? json_encode( $this->getJsonArray() )
             : json_encode( array($strDrName => $this->getJsonArray()) ));
  }
  
  /**
   * Retourne le datarow au format array pour json avec mise en minuscules des champs
   * @return array
   */
  public function getJsonArray()
  {
    return array_change_key_case($this->__fields, CASE_LOWER);
  }
  
  /**
   * @see ArrayAccess::offsetExists()
   * 
   * @param mixed $offset
   * @return boolean true if offset exists, false otherwise
   */
  public function offsetExists($offset)
  {
    return isset($this->__fieldsUpper[strtoupper($offset)]);
  }
  
  /**
   * @see ArrayAccess::offsetGet()
   * 
   * @param mixed $offset
   * @return mixed the value at the specified offset
   */
  public function offsetGet($offset)
  {
    return $this->getValueName($offset);
  }
  
  /**
   * @see ArrayAccess::offsetSet()
   * 
   * @param mixed $offset
   * @param mixed $value
   */
  public function offsetSet($offset, $value)
  {
    $this->setValueName($offset, $value);
    //trigger_error(__CLASS__." - fonction ".__FUNCTION__." non autorisée.", E_USER_ERROR);
  }
  
  /**
   * @see ArrayAccess::offsetUnset()
   * 
   * @param unknown_type $offset
   */
  public function offsetUnset($offset)
  {
    trigger_error(__CLASS__." - fonction ".__FUNCTION__." non autorisée.", E_USER_ERROR);
  }
  
}
