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

require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CLASSE."pattern/alklanghandler.class.php");

/**
 * @package Alkanet_Class_Pattern
 *          Module de classes génériques du framework Alkanet
 * 
 * @class AlkObject
 * @brief Classe abstraite racine du framework Alkanet
 */
abstract class AlkObject 
{ 
  /** objet null utilisé pour les retours en échec */
  /*protected*/ public static $oNull = null;
  
  /** tableau des langues prises en compte, = vide par défaut */
  /*protected*/ public static $defaultTabLangue;
  /*protected*/ public static $defaultNbLangue;
  /*protected*/ public $tabLangue;

  /** nombre de langue uilisée */
  /*protected*/ public $nbLangue;

  /** 
   *  Constructeur par défaut
   */
  public function __construct($initLocale=true)
  {
    if ( $initLocale ){
      if ( !self::$defaultTabLangue ){
        self::$defaultTabLangue = AlkLangHandler::getLGTabLangue();
        self::$defaultNbLangue = count(self::$defaultTabLangue);
      }
      $this->tabLangue = self::$defaultTabLangue;
      $this->nbLangue = self::$defaultNbLangue;
    }
  }

  /**
   *  Destructeur par défaut
   */
  public function __destruct()
  {
  }

  /**
   *  Par défaut, les classes ne sont pas pas clonable
   */
  public function __clone()
  {
    //$this->triggerError("Le clônage n'est pas autorisé avec ".get_class($this), E_USER_ERROR);
  }

  /**
   *  Gestionnaire d'erreur
   * 
   * @param strMsg  Message personnalisé
   * @param typeMsg Type de message : E_USER_ERROR, E_USER_WARNING
   */
  public function triggerError($strMsg, $typeMsg)
  {
    trigger_error($strMsg, $typeMsg);
    if( $typeMsg == E_USER_ERROR ) die();
  }

  
  /**
   *  Affecte une propriété de l'objet
   * 
   * @param strProperty   Nom de la propriété d'objet à affecter (propriétés private non acceptées)
   * @param value         Valeur à affecter
   * @param bTriggerError Autorise la levée d'erreur (true par défaut)
   * 
   * @return boolean : affectation réussie (vérifications menant à triggeError(E_USER_ERROR) en cas d'échec) 
   */
  public function setProperty($strProperty, $value, $bTriggerError=true)
  {
    $oReflection = new ReflectionClass(get_class($this));
    
    try{
      
      $oProperty = $oReflection->getProperty($strProperty);
      
    } catch (Exception $ex){
      if ( $bTriggerError ) $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : La propriété '".$strProperty."' n'existe pas sur cet objet", E_USER_ERROR);
      return false;
    }   
    
    $strLowerProp = mb_strtolower($strProperty);
    $setter = "set".$strLowerProp;
    if ( method_exists($this, $setter)){
      if ( $bTriggerError ) $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : Un setter sur la propriété '".$strProperty."' existe sur cet objet, il est préférable de l'appeler.", E_USER_ERROR);
      return eval("return \$this->".$setter."(\$value);");
    }
    
    if ( $oProperty->isPrivate() ){
      $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : La propriété '".$strProperty."' est privée et ne possède pas de setter : vous ne pouvez la modifier par cette fonction", E_USER_ERROR);
    }
    $this->$strProperty = $value;
    return true;
  }

  
  /**
   *  Affecte une propriété de l'objet
   * 
   * @param strProperty   Nom de la propriété d'objet à affecter (propriétés private non acceptées)
   * @param bTriggerError Autorise la levée d'erreur (true par défaut)
   * 
   * @return la valeur de la propriétés si toutes les conditions sont acceptées, triggeError(E_USER_ERROR) sinon 
   */
  public function getProperty($strProperty, $bTriggerError=true)
  {
    $oReflection = new ReflectionClass(get_class($this));
    
    try {
      
      $oProperty = $oReflection->getProperty($strProperty);
      
    } catch (Exception $ex){
      if ( $bTriggerError ) $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : La propriété '".$strProperty."' n'existe pas sur cet objet", E_USER_ERROR);
      return false;
    }   
    $strLowerProp = mb_strtolower($strProperty);
    $getter = "get".$strLowerProp;
    if ( method_exists($this, $getter)){
      if ( $bTriggerError ) $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : Un getter sur la propriété '".$strProperty."' existe sur cet objet, il est préférable de l'appeler.", E_USER_ERROR);
      return eval("return \$this->".$getter."();");
    }

    if ( $oProperty->isPrivate() ){
      $this->triggerError("class ".get_class($this)."::".__FUNCTION__." : La propriété '".$strProperty."' est privée et ne possède pas de getter : vous ne pouvez y accéder par cette fonction", E_USER_ERROR);
    }
    return $this->$strProperty;
  }

  /**
   *  Détermine si un objet est de la classe donnée ou de ses dérivées
   * 
   * @param strClass    Nom de la classe
   * @return boolean 
   */
  public function isTypeOf($strClass)
  {
    return ($this instanceof $strClass) || is_subclass_of($this, $strClass);
  }
    
  /**
   * Met à jour le tableau de langue 
   */
  public function setLGTabLangue()
  {
    $this->tabLangue = AlkLangHandler::getLGTabLangue();
    $this->nbLangue = count($this->tabLangue);
  }
}

?>