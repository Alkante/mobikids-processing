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


/** 
 *  Constantes spécifiants les types de récupération :
 *        Get, Post, Get puis Post, Post puis Get 
 */
if( !defined("REQ_GET") ) {
  define("REQ_GET", 1);
  define("REQ_POST", 2);
  define("REQ_GET_POST", 3);
  define("REQ_POST_GET", 4);
}

/**
 * @package Alkanet_Class_Pattern
 * 
 * @class AlkRequest
 * @brief Classe se chargeant de récupérer les éléments postés en GET ou en POST
 */
final class AlkRequest extends AlkObject
{
  /**
   * Méthodes :
   * 0 = l'ensemble des paramètres sont encodés. Cette encodage représente la valeur du token, placé sur l'url en GET
   *     tabToken[clé] = valeur
   * 1 = ajout d'une clé (selon méthode 2) dans le token construit selon la méthode 0
   * 2 = l'ensemble des paramètres sont mémorisés en session. Le token est tiré aléatoirement, transformé en md5 et représente la clé de session des paramètres mémorisés.
   *     tabToken[clé-token][clé] = valeur
   */
  const ALK_REQUEST_METHOD_DEFAULT  = 0;
  const ALK_REQUEST_METHOD_SDEFAULT = 1;
  const ALK_REQUEST_METHOD_UNIQID   = 2;

  /** méthode de fonctionnement du token */
  private static $method = self::ALK_REQUEST_METHOD_DEFAULT;

  /** longeur du tokenKey */
  private static $lengthTokenKey = 32;

  /** valeur courante du tokenKey, sert de referer */
  private static $currentTokenKey = "";
  
  /** 
   * ensemble des clés de token mémorisées encore actives, 
   * le nombre de peut dépasser maxTokenKeys
   * le ensemble se comporte comme une file lorsque le nombre d'élément atteind maxTokenKeys
   */
  private static $tabTokenKeys = array();
  
  /** ensemble des paramètres du token actif */
  private static $tabToken = array();
  
  /** ensemble des paramètres décodés */
  private static $tabDecode = array();
  
  /**
   * Retourne un token utilisé pour le crossing domain
   * Là où le partage de valeur de session n'est pas possible
   * Cette valeur doit etre stokée sur un support persistant (base, disque) le temps du passage
   * A la réception, cette valeur doit etre vérifiée (si elle existe uniquement) puis détruite.
   * @return string
   */
  public static function getTokenCrossingDomain($data="")
  {
    $data = ( $data == "" ? time().uniqid() : $data );
    return base64_encode(hash_hmac('sha256', $data, md5($data)));
  }
  
  /**
   * Retourne le nom de la clé qui sera associée à la clé privée
   * @param string $alias   alias permettant de définir plusieurs types de jeton
   * @return string
   */
  protected static function getSecurityKeyName($alias="")
  {
    return "ALK_TOKENKEY".( $alias != "" ? "_".$alias : "" );
  }
   
  /**
   * Initialise une clé de sécurité, Si la clé existe, préserve la valeur courante
   * @param string $sessionKey   clé de sécurité
   * @param boolean $new    true par défaut pour générer une nouvelle clé, false pour récupérer celle existante
   */
  protected static function initSecurityKey($sessionKey, $new=true)
  {
    if( !isset($_SESSION) ) {
      $_SESSION = array();
    }
    if( !isset($_SESSION[$sessionKey]) && $new ) {
      $pos = sprintf("%02d", rand(0, 23));
      $_SESSION[$sessionKey] = md5(time().uniqid()).$pos; // sur 32 caractères + 2 caractères pour la position de la clé
    }
  }
  
  /**
   * Retourne la clé publique, celle qui sera intégré sur le formulaire ou dans l'url
   * Le jeton est systématiquement supprimé ensuite.
   * @param string  $alias  alias permettant de définir plusieurs types de jeton
   * @param boolean $new    true par défaut pour générer une nouvelle clé, false pour récupérer celle existante
   * @return string
   */
  public static function getSecurityKey($alias="", $new=true)
  {
    $sessionKey = self::getSecurityKeyName($alias);
    self::initSecurityKey($sessionKey, $new);
    $publicKey = ( isset($_SESSION[$sessionKey]) 
                 ? substr($_SESSION[$sessionKey], substr($_SESSION[$sessionKey], -2)*1, 8)
                 : "" );
    //echo "<br>[$sessionKey]=".(isset($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : "ne" )." pk=".$publicKey."<br>";
    return $publicKey;
  }
  
  /**
   * Vérification de la clé de sécurité
   * return un booleen : true si la clé est vérifiée, false sinon
   * Le jeton est systématiquement supprimé ensuite.
   * @param string  $publicKey  valeur de la clé publique, récupèrée au préalable en post ou en get
   * @param boolean $destroy    =true par défaut pour supprimer la clé après vérification, false pour la préserver
   * @param string  $alias      alias permettant de définir plusieurs types de jeton
   * @return boolean
   */
  public static function checkSecurityKey($publicKey, $destroy=true, $alias="")
  {
    $sessionKey = self::getSecurityKeyName($alias);
    $exactKey   = self::getSecurityKey($alias, false);
    if( $destroy && isset($_SESSION[$sessionKey]) ) {
      unset($_SESSION[$sessionKey]);
    }
    return ( $publicKey != "" && $publicKey==$exactKey ? true : false );
  }
  
  /**
   * Vérifie l'intégrité de la valeur
   * Retourne true si ok ou si non vérifié, false si le format souhaité n'est pas conforme
   * @param mixed $value          valeur à vérifier
   * @param mixed $checkFunction  soit :
   *                              - chaine vide par défaut pour aucune vérification
   *                              - chaine pour identifier une fonction existante qui retourne un booléan et prend en paramètre $value
   *                              - chaine pour identifier une expression régulière
   *                              - tableau à 2 valeurs pour identifier la classe ou l'objet et la méthode à appeler. Cette méthode doit prendre $value en paramètre et retourne un boolean
   * @return bool
   */
  protected static function checkValue($value, $checkFunction="")
  {
    $bRes = true;
    if( is_string($checkFunction) && $checkFunction!="" ) {
      // soit c'est une fonction, soit une expression régulière
      if( function_exists($checkFunction) ) {
        // la fonction existe
        $bRes = call_user_func($checkFunction, $value);
      } 
      else if( !(@preg_match($checkFunction, null) === false) ) {
        // l'expression régulière est valide
        $bRes = ( preg_match($checkFunction, $value) == 1 ? true : false );
      }
      // sinon, pas de vérification
    } 
    else if( is_array($checkFunction) && !empty($checkFunction) 
             && count($checkFunction)==2 
             && method_exists($checkFunction[0], $checkFunction[1]) ) {
      $bRes = call_user_func(array($checkFunction[0], $checkFunction[1]), $value);
    }
    
    return $bRes;
  }
   
  /**
   * Récupère puis retourne la valeur d'un paramètre passé en GET
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _GET($paramName, $defaultValue="", $checkFunction="")
  {
    $value = ( isset($_GET[$paramName]) ? $_GET[$paramName] : $defaultValue );
    return ( self::checkValue($value, $checkFunction) ? $value : $defaultValue );
  }

  /**
   * Récupère puis retourne la valeur d'un paramètre passé en POST
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _POST($paramName, $defaultValue="", $checkFunction="")
  {
    $value = ( isset($_POST[$paramName]) ? $_POST[$paramName] : $defaultValue );
    return ( self::checkValue($value, $checkFunction) ? $value : $defaultValue );
  }

  /**
   * Récupère puis retourne la valeur d'un paramètre passé en REQUEST (POST ou GET)
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _REQUEST($paramName, $defaultValue="", $checkFunction="")
  {
    $value = ( isset($_REQUEST[$paramName]) ? $_REQUEST[$paramName] : $defaultValue );
    return ( self::checkValue($value, $checkFunction) ? $value : $defaultValue );
  }

  /**
   * Récupère puis retourne la valeur numérique d'un paramètre passé en GET
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return int
   */
  public static function _GETint($paramName, $defaultValue=0)
  {
    return self::_GET($paramName, $defaultValue, "is_numeric");
  }

  /**
   * Récupère puis retourne la valeur numérique d'un paramètre passé en POST
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return int
   */
  public static function _POSTint($paramName, $defaultValue=0)
  {
    return self::_POST($paramName, $defaultValue, "is_numeric");
  }

  /**
   * Récupère puis retourne la valeur numérique d'un paramètre passé en REQUEST
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return int
   */
  public static function _REQUESTint($paramName, $defaultValue=0)
  {
    return self::_REQUEST($paramName, $defaultValue, "is_numeric");
  }

  /**
   * Récupère puis retourne la valeur date / heure d'un paramètre passé en GET
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return string
   */
  public static function _GETdate($paramName, $defaultValue=0)
  {
    return self::_GET($paramName, $defaultValue, "/\d/");
  }

  /**
   * Récupère puis retourne la valeur date / heure d'un paramètre passé en POST
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return string
   */
  public static function _POSTdate($paramName, $defaultValue=0)
  {
    return self::_POST($paramName, $defaultValue, "/\d/");
  }

  /**
   * Récupère puis retourne la valeur date / heure d'un paramètre passé en REQUEST
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @return string
   */
  public static function _REQUESTdate($paramName, $defaultValue=0)
  {
    return self::_REQUEST($paramName, $defaultValue, "/\d/");
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox passé en GET
   * Une checkbox ne retourne pas par défaut de valeur, le composant AlkHtmlCheckbox produit un champ caché nommé avec le préfixe "not_"
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _GETcheck($paramName, $defaultValue="", $checkFunction="")
  {
    $paramNameNot = "not_".$paramName;
    return ( isset($_GET[$paramName])
             ? self::_GET($paramName, $defaultValue, $checkFunction)
             : (  isset($_GET[$paramNameNot])
                  ? self::_GET($paramNameNot, $defaultValue, $checkFunction)
                  : $defaultValue ));
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox passé en POST
   * Une checkbox ne retourne pas par défaut de valeur, le composant AlkHtmlCheckbox produit un champ caché nommé avec le préfixe "not_"
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _POSTcheck($paramName, $defaultValue="", $checkFunction="")
  {
    $paramNameNot = "not_".$paramName;
    return ( isset($_POST[$paramName])
             ? self::_POST($paramName, $defaultValue, $checkFunction)
             : (  isset($_POST[$paramNameNot])
                  ? self::_POST($paramNameNot, $defaultValue, $checkFunction)
                  : $defaultValue ));
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox passé en REQUEST
   * Une checkbox ne retourne pas par défaut de valeur, le composant AlkHtmlCheckbox produit un champ caché nommé avec le préfixe "not_"
   * @param string $paramName         Nom du paramètre
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  public static function _REQUESTcheck($paramName, $defaultValue="", $checkFunction="")
  {
    $paramNameNot = "not_".$paramName;
    return ( isset($_REQUEST[$paramName])
             ? self::_REQUEST($paramName, $defaultValue, $checkFunction)
             : (  isset($_REQUEST[$paramNameNot])
                  ? self::_REQUEST($paramNameNot, $defaultValue, $checkFunction)
                  : $defaultValue ));
  }

  /**
   * Récupère puis retourne la valeur à un indice d'un résultat tableau sans test de type
   * @param array  $values            Tableau dans lequel s'effectue la recherche
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  protected static function _array($values, $paramName, $index, $defaultValue="", $checkFunction="")
  {
    $tabRes = ( isset($values[$paramName]) ? $values[$paramName] : $defaultValue );
    if( !is_array($tabRes) ) {
      return ( self::checkValue($tabRes, $checkFunction) ?$tabRes :  $defaultValue );
    }
    if( !empty($tabRes) && array_key_exists($index, $tabRes) ) {
      return ( self::checkValue($tabRes[$index], $checkFunction) ? $tabRes[$index] :  $defaultValue );
    }
    return $defaultValue;
  }
  
  /**
   * Récupère puis retourne la valeur d'un entier sachant qu'il est inclus dans tableau passé en GET
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, 0 par défaut
   * @return int
   */
  public static function _GETarrayint($paramName, $index, $defaultValue=0)
  {
    return self::_array($_GET, $paramName, $index, $defaultValue, "is_numeric");
  }
  
  /**
   * Récupère puis retourne la valeur d'un entier sachant qu'il est inclus dans tableau passé en POST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, 0 par défaut
   * @return int
   */
  public static function _POSTarrayint($paramName, $index, $defaultValue=0)
  {
    return self::_array($_POST, $paramName, $index, $defaultValue, "is_numeric");
  }

  /**
   * Récupère puis retourne la valeur d'un entier sachant qu'il est inclus dans tableau passé en REQUEST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, 0 par défaut
   * @return int
   */
  public static function _REQUESTarrayint($paramName, $index, $defaultValue=0)
  {
    return self::_array($_REQUEST, $paramName, $index, $defaultValue, "is_numeric");
  }
  
  /**
   * Récupère puis retourne la valeur à un indice d'un résultat tableau passé en GET
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _GETarrayvalue($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_array($_GET, $paramName, $index, $defaultValue, $checkFunction);
  }
  
  /**
   * Récupère puis retourne la valeur à un indice d'un résultat tableau passé en POST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _POSTarrayvalue($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_array($_POST, $paramName, $index, $defaultValue, $checkFunction);
  }
  
  /**
   * Récupère puis retourne la valeur à un indice d'un résultat tableau passé en REQUEST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _REQUESTarrayvalue($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_array($_REQUEST, $paramName, $index, $defaultValue, $checkFunction);
  }
  
  /**
   * Récupère puis retourne la valeur d'une checkbox sachant qu'il est inclus dans tableau
   * Une checkbox ne retourne pas par défaut de valeur, le composant AlkHtmlCheckbox produit un champ caché nommé avec le préfixe "not_"
   * @param array  $values            Tableau dans lequel s'effectue la recherche
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param string $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe)
   * @return string
   */
  protected static function _arraycheck($values, $paramName, $index, $defaultValue="", $checkFunction="")
  {
    $tabRes = ( isset($values[$paramName]) ? $values[$paramName] : $defaultValue );
    $paramNameNot = "not_".$paramName;
    $tabResNot = ( isset($values[$paramNameNot]) ? $values[$paramNameNot] : $defaultValue );
    if( !is_array($tabRes) ) {
      return ( self::checkValue($tabRes, $checkFunction) ? $tabRes :  $defaultValue );
    }
    if( !empty($tabRes) && array_key_exists($index, $tabRes) ) {
      return ( self::checkValue($tabRes[$index], $checkFunction) ? $tabRes[$index] :  $defaultValue );
    }
    if( !empty($tabResNot) && array_key_exists($index, $tabResNot) ) {
      return ( self::checkValue($tabResNot[$index], $checkFunction) ? $tabResNot[$index] :  $defaultValue );
    }
    return $defaultValue;
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox sachant qu'il est inclus dans tableau passé en GET
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _GETarraycheck($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_arraycheck($_GET, $paramName, $index, $defaultValue, $checkFunction);
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox sachant qu'il est inclus dans tableau passé en POST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _POSTarraycheck($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_arraycheck($_POST, $paramName, $index, $defaultValue, $checkFunction);
  }

  /**
   * Récupère puis retourne la valeur d'une checkbox sachant qu'il est inclus dans tableau passé en REQUEST
   * @param string $paramName         Nom du paramètre
   * @param int    $index             Index du tableau
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */
  public static function _REQUESTarraycheck($paramName, $index, $defaultValue="", $checkFunction="")
  {
    return self::_arraycheck($_REQUEST, $paramName, $index, $defaultValue, $checkFunction);
  }

  /**
   * Récupère puis retourne le tableau correspondant à un fichier uploadé
   * Retourne un tableau vide si non trouvé
   * @param string paramName  Nom du paramètre
   * @return array
   */
  public static function _FILES($paramName)
  {
    return ( isset($_FILES[$paramName]) ? $_FILES[$paramName] : array() );
  }

  /**
   * Décode la valeur passée en paramètre puis retourne le résultat
   * @param string value   valeur à décoder
   * @return string 
   */
  public static function decodeValue($value)
  {
    return ( is_string($value) ? hex2bin($value) : "" ); 
  }

  /**
   * Encode la valeur passée en paramètre puis retourne le résultat
   * @param string value   valeur à encoder
   * @return string 
   */
  public static function encodeValue($value)
  {
    return ( is_string($value) ? bin2hex($value) : "" );
  }

  /**
   * Retourne la valeur encodée de la liste des paramètres fournis avec $params
   * @param mixed $params   ensemble des paramètres de type soit :
   *                        - string : suite de key=value&...
   *                        - array  : [key]=value
   * @return string
   */
  public static function getEncodeParam($params)
  {
    return self::getTokenParam($params, self::ALK_REQUEST_METHOD_DEFAULT);
  }
  
  /**
   * Retourne la valeur du token à l'aide des paramères passés
   * En fonction de la méthode, soit :
   * - retourne une valeur chaine contenant tous les params dans une valeur speudo-cryptée
   * - retourne une clé correspondant aux valeurs mémorisées en cache
   * @param mixed $params   ensemble des paramètres de type soit :
   *                        - string : suite de key=value&...
   *                        - array  : [key]=value
   * @parm int    $method   force la méthode utlisée, =-1 par défaut pour prendre la méthode sélectionnée par la classe, sinon
   * @return string
   */
  public static function getTokenParam($params, $method=-1)
  {
    $token = "";
    $method = ( $method == -1 
                ? self::$method 
                : ( $method == self::ALK_REQUEST_METHOD_UNIQID
                    ? self::ALK_REQUEST_METHOD_UNIQID 
                    : ( $method == self::ALK_REQUEST_METHOD_SDEFAULT 
                        ? self::ALK_REQUEST_METHOD_SDEFAULT
                        : self::ALK_REQUEST_METHOD_DEFAULT  ))); 
    switch( $method ) {
      case self::ALK_REQUEST_METHOD_UNIQID:
        if( !is_array($params) ) {
          $strParams = $params;
          parse_str($strParams, $params);
        }
        $token = self::addTokenKeyToCache($params);
        break;
      
      case self::ALK_REQUEST_METHOD_DEFAULT:
      case self::ALK_REQUEST_METHOD_SDEFAULT:
      default:
        if( is_array($params) && !empty($params) ) {
          // convertit le tableau en chaine
          $params = http_build_query($params);
        }
        if( $method == self::ALK_REQUEST_METHOD_SDEFAULT ) {
          $tokenKey = self::addTokenKeyToCache(array("tk" => "__tk__"));
          $params .= "&_tk_=".$tokenKey;
        }
        $token = self::encodeValue($params);
        break;
    }        
    return $token;
  }
  
  /**
   * Récupère le paramètre http selon la méthode REQUEST
   * Décode le param encodée par encodeValue() 
   * Vérifie le format en fonction de $defaultValue et $checkFunction
   * Puis retourne la valeur du paramètre
   * @param string $paramName         Nom du paramètre
   * @param int    $defaultValue      Valeur par défaut, chaine vide par défaut
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string
   */  
  public static function getDecodeParam($paramName,  $defaultValue="", $checkFunction="")
  {  
    // récupère le paramètre encodé
    $paramValue = self::_REQUEST($paramName, "", "/^[a-fA-F0-9]*$/");
    if ( !isset(self::$tabDecode[$paramValue]) ){  // récupère le paramètre encodé
      //décodage
      $decodeValue = self::decodeValue($paramValue);
      // vérification
      $decodeValue = ( self::checkValue($decodeValue, $checkFunction) ? $decodeValue : $defaultValue );
      // mémorisation
      self::$tabDecode[$paramValue] = $decodeValue;
    } else {
      // valeur décodée mémorisée
      $decodeValue = self::$tabDecode[$paramValue];
    }
    return $decodeValue;
  }

  /**
   * Initialise la structure et récupère les éléments du cache
   * cache utilisé : session
   * Si pour une raison, il est néssaire d'accéder à des tokenKey directement sans authentification, 
   * il faut les déclarer dans un tableau sérialisé dans la constante ALK_DEFAULT_TOKENS
   */
  protected static function initTokenKeyCache()
  {
    if( !isset($_SESSION["ALK_TOKENKEYS"]) ) {
      $_SESSION["ALK_TOKENKEYS"] = array();
      $_SESSION["ALK_TOKENKEYS_FIFO"] = array();
      if( defined("ALK_DEFAULT_TOKENKEYS") && ALK_DEFAULT_TOKENKEYS != "" ) {
        // charge les tokenKeys appelable directement qui n'expirent jamais
        $_SESSION["ALK_TOKENKEYS"] = unserialize(ALK_DEFAULT_TOKENKEYS);
      }
      self::$tabTokenKeys = $_SESSION["ALK_TOKENKEYS"];
    } else if( empty(self::$tabTokenKeys) ) {
      self::$tabTokenKeys = $_SESSION["ALK_TOKENKEYS"];
    }
  }
  
  /**
   * Enregistre les modifications liées aux tokenKey dans le cache dédié
   * @param string tokenKey   valeur du tokenKey ajouté, =vide par défaut pour juste enregistré
   */
  protected static function saveTokenKeyCache($tokenKey="")
  {
    //echo "<br><br>saveTokenKeyCache<br><br>";
    if( $tokenKey != "" ) {
      // nouveau tokenKey, on l'enfile (au début)
      array_unshift($_SESSION["ALK_TOKENKEYS_FIFO"], $tokenKey);
    } else {
      // suppression de toutes les clés sauf la courante et son referer
      $_SESSION["ALK_TOKENKEYS_FIFO"] = array(self::$currentTokenKey);
      $tabTmp = array(self::$currentTokenKey => self::$tabTokenKeys[self::$currentTokenKey]);
      if( isset(self::$tabTokenKeys[self::$currentTokenKey]["__referer__"]) ) {
        $tkReferer = self::$tabTokenKeys[self::$currentTokenKey]["__referer__"];
        $tabTmp[$tkReferer] = self::$tabTokenKeys[$tkReferer];
        $_SESSION["ALK_TOKENKEYS_FIFO"][] = $tkReferer;
      }
      self::$tabTokenKeys = $tabTmp;
    }
    
    // enregistrement
    $_SESSION["ALK_TOKENKEYS"] = self::$tabTokenKeys;
  }
  
  /**
   * Retourne un tokenKey et ses 2 variables d'initialisation dans un tableau
   * indicés de 0 à 2, l'indice 0 contient le tokenKey
   * @return array
   */
  protected static function getTokenKey()
  {
    $tv = time();
    $uv = uniqid();
    $tk = substr(md5($tv."_".$uv), 0, self::$lengthTokenKey);
    return array($tk, $uv, $tv);
  }
  
  /**
   * Vérifie l'authenticité du tokenKey à l'aide des 2 variables d'initialisation et du tokenKey du referer
   * Retourne true si ok, false sinon
   * @param string $tokenKey  valeur du tokenKey
   * @return bool
   */
  protected static function checkTokenKey($tokenKey)
  {
    $uv        = ( isset(self::$tabTokenKeys[$tokenKey]["__uv__"])      ? self::$tabTokenKeys[$tokenKey]["__uv__"]      : 0 );
    $tv        = ( isset(self::$tabTokenKeys[$tokenKey]["__tv__"])      ? self::$tabTokenKeys[$tokenKey]["__tv__"]      : 0 );
    $tkReferer = ( isset(self::$tabTokenKeys[$tokenKey]["__referer__"]) ? self::$tabTokenKeys[$tokenKey]["__referer__"] : "__no_tkreferer__" );
    return ( $tokenKey == substr(md5($tv."_".$uv), 0, self::$lengthTokenKey) && isset(self::$tabTokenKeys[$tkReferer]) ? true : false );
  }
  
  /**
   * Récupère en local les informations mémorisées associées au tokenKey
   * La lecture du token ne peut se faire qu'une seule fois, la clé de token est ensuite supprimée
   * @param string $tokenKey  clé du token
   */
  protected static function readTokenKeyFromCache($tokenKey)
  {
    if( !empty(self::$tabTokenKeys) 
        && array_key_exists($tokenKey, self::$tabTokenKeys) 
        && self::checkTokenKey($tokenKey) ) {
      self::$tabToken = self::$tabTokenKeys[$tokenKey];
      self::saveTokenKeyCache();
    }
  }
  
  /**
   * Détermine un tokenKey, enregistre les paramètres dans le cache et retourne la valeur du tokenKey
   * @param array $params   ensemble des paramètres à mémoriser
   * @return string
   */
  protected static function addTokenKeyToCache($params)
  {
    self::initTokenKeyCache();
    list($tokenKey, $uv, $tv) = self::getTokenKey();
    self::$tabTokenKeys[$tokenKey] = $params;
    self::$tabTokenKeys[$tokenKey]["__uv__"] = $uv;
    self::$tabTokenKeys[$tokenKey]["__tv__"] = $tv;
    self::$tabTokenKeys[$tokenKey]["__referer__"] = ( self::$currentTokenKey != "" ? self::$currentTokenKey : $tokenKey );
    self::saveTokenKeyCache($tokenKey);
    return $tokenKey;
  }
  
  /**
   * Méthode à appeler pour initialiser la classe 
   * Récupère les données liées au token (passé en GET ou en POST)
   * Retourne true si ok, false si le token n'a pas été fourni
   * @param int $method            valeur correspondant aux constantes de la cette classe
   * @param int $lengthTokenKey    longueur du tokenKey, doit être compris entre 8 et 32, =32 par défaut
   * @return bool
   */
  public static function readToken($method=0, $lengthTokenKey=32)
  {
    if( !empty(self::$tabToken) ) {
      return true;
    }
   
    $lengthTokenKey = floor($lengthTokenKey*1);
    self::$lengthTokenKey = ( $lengthTokenKey>=8 && $lengthTokenKey<=32   ? $lengthTokenKey :  32 );
    self::$method         = ( $method == self::ALK_REQUEST_METHOD_UNIQID 
                              ? self::ALK_REQUEST_METHOD_UNIQID 
                              : ( $method == self::ALK_REQUEST_METHOD_SDEFAULT 
                                  ? self::ALK_REQUEST_METHOD_SDEFAULT
                                  : self::ALK_REQUEST_METHOD_DEFAULT ));

    if( self::$method != self::ALK_REQUEST_METHOD_DEFAULT ) {
      self::initTokenKeyCache();
      //echo "<br>tokenKeys=".print_r($_SESSION["ALK_TOKENKEYS"], true)."<br>";
    }
    self::$tabToken = array();
    
    $bRes = false;
    switch( self::$method ) {
      case self::ALK_REQUEST_METHOD_UNIQID:
        self::$currentTokenKey =
        $tokenKey = self::_REQUEST("token", "", "/^[a-f0-9]{".self::$lengthTokenKey."}$/");
        $bRes = ( $tokenKey == "" ? false : true );
        if( $bRes ) {
          self::readTokenKeyFromCache($tokenKey);
        }
        break;
      
      case self::ALK_REQUEST_METHOD_DEFAULT:
      case self::ALK_REQUEST_METHOD_SDEFAULT:
      default:
        $params = self::getDecodeParam("token");
        $bRes = ( $params == "" ? false : true );
        if( $bRes ) {
          parse_str($params, self::$tabToken);
        }
        if( $bRes && self::$method == self::ALK_REQUEST_METHOD_SDEFAULT ) {
          $bRes = false;
          self::$currentTokenKey =
          $tokenKey = ( isset(self::$tabToken["_tk_"]) ? self::$tabToken["_tk_"] : "" );
          $bRes = ( $tokenKey!="" && self::checkTokenKey($tokenKey) ? true : false );
          self::saveTokenKeyCache();
        }
        break;
    }
    /*
    echo "<br>readToken=<br>".self::$currentTokenKey.
      "<br>tabToken=".print_r(self::$tabToken, true).
      "<br>tokenKeys[cur]=".print_r(isset(self::$tabTokenKeys[self::$currentTokenKey])? self::$tabTokenKeys[self::$currentTokenKey] : "", true)."<br>".
      "<br>tokenKeys=".print_r($_SESSION["ALK_TOKENKEYS"], true)."<br>";
    */
    return $bRes;
  }

  /**
   * Retourne la valeur du tokenKey courant
   * @return string
   */
  public static function getCurrentTokenKey()
  {
    return self::$currentTokenKey;
  }
  
  /**
   * Retourne la valeur d'un paramètre appartenant au token
   * @param string $param         Nom du paramètre
   * @param string $defaultValue  Valeur par défaut si non présent
   * @param mixed  $checkFunction     string : Nom de la fonction ou expression régulière de vérification
   *                                  array  : array(object|className, methodName) pour utiliser la méthode de vérification de l'objet (ou la classe) 
   * @return string : la valeur du paramètre
   */
  public static function getToken($param, $defaultValue="", $checkFunction="")
  {
    
    return ( array_key_exists($param, self::$tabToken)
             ? ( self::checkValue(self::$tabToken[$param], $checkFunction) ? self::$tabToken[$param] : $defaultValue )
             : $defaultValue );
  }

  /**
   * Retourne un checksum pour le token donné
   * @param strToken    Chaine encodée par getEncodeParam
   * @deprecated since version 3.6
   * @return int : checksum du token
   */
  public static function getCheckSumEncodage($strToken)
  {
    $iChecksum = 0;
    for($i=0; $i<strlen($strToken); $i++ ) {
      $iChecksum += hexdec(substr($strToken,  $i, 1));
    }
    return $iChecksum;
  }
  
  /**
   * Retourne dans un tableau, la liste de tous les paramètres dont le nom commence par le préfixe donné en paramètre
   * @param string $method   =POST, GET ou REQUEST
   * @param string $prefix   intitulé du préfixe
   * @return array
   */
  public static function getParamsNameByPrefix($method, $prefix)
  {
    $method = strtoupper($method);
    $tabParams = ( $method=="POST" 
                   ? array_keys($_POST)
                   : ( $method=="GET"
                       ? array_keys($_GET)
                       : array_keys($_REQUEST) ));
    
    $funcCom = create_function('$strCmp', 'return ( substr($strCmp, 0, '.strlen($prefix).') == "'.$prefix.'" ? true : false );');
    
    $tabRes = array_filter($tabParams, $funcCom);
    return $tabRes; 
  }

  /**
   * Retourne la langue de navigateur
   * @param bool bReturnIndex =false par défaut pour retourner la langue sous forme texte, =true pour retourner l'indice de la langue
   * @return string si bReturnIndex=false
   *         int    si bReturnIndex=true
   */
  public static function getNavigatorLanguage($bReturnIndex=false)
  {
    $iIndex = 0;
    $strLg = "fr";
    if( !(isset($GLOBALS["tabLg"]) && is_array($GLOBALS["tabLg"]) && !empty($GLOBALS["tabLg"])) ) {
      return ( $bReturnIndex ? $iIndex : $strLg );
    }
    $strLg =  $GLOBALS["tabLg"][0];
    
    if( isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ) {
      // format : 'en,en-us;q=0.5,fr-fr;q=0.8' (exemple)
      $strAccept = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
      // split chaque langue
      $tabLangs = explode(",", $strAccept);
      $i = 0;
      while( $i < count($tabLangs) ) { 
        $strLang = $tabLangs[$i];
        // retire le coefficiant d'ordre
        $tabLang = explode(";", $strLang);
        if( count($tabLang) > 0 ) {
          $strLangAccept = $tabLang[0];
          // extrait la langue du pays (séparé avec un tiret)
          $tabLg = explode("-", $strLangAccept);
          if( count($tabLg) >0 ) {
            $strLgAccept = $tabLg[0];
            if( in_array($strLgAccept, $GLOBALS["tabLg"]) ) {
              $strLg = $strLgAccept;
              $iIndex = array_search($strLgAccept, $GLOBALS["tabLg"]);
              break;
            } 
          }
        }
        $i++;
      }
    }
    
    return ( $bReturnIndex ? $iIndex : $strLg );
  }
}


/**
 * Anciennes fonctions
 */


/**
 *  fonction de test par défaut pour la function request
 *
 * @param  strValue  Valeur à tester
 * @return Retourne toujours true
 */
function DefaultTest($strValue) { return true; }

/**
 *  fonction de récupération de variables postées par un checkbox
 *
 * @param  strVar              Nom de la variable
 * @param  reqMethod           Doit prendre l'une des valeurs constantes reqXXXX
 * @return La valeur du paramètre récupéré
 */
function RequestCheckbox($strVar, $reqMethod)
{
  $strRes = Request($strVar, $reqMethod, "");
  if( $strRes == "" ) return "0";
  elseif( $strRes == "on" ) return "1";
  return $strRes;
}

/**
 *  fonction de récupération de variables postées
 *
 * @param  strVar              Nom de la variable
 * @param  reqMethod           Doit prendre l'une des valeurs constantes reqXXXX
 * @param  strDefault          Valeur par défaut si n'existe pas
 * @param  strFunctionTestType Nom de la fonction pour tester le type de la valeur
 * @return La valeur du paramètre récupéré
 */
function Request($strVar, $reqMethod, $strDefault, $strFunctionTestType="DefaultTest")
{
  if( $strVar == "" ) return $strDefault;
  
  $bTest = false;
  $strVal = $strDefault;
  switch( $reqMethod ) {
  case REQ_GET :
    if( isset($_GET[$strVar]) )
      eval("\$bTest = $strFunctionTestType(\$_GET[\$strVar]);");
    $strVal = (($bTest==true && $_GET[$strVar]!="") ? $_GET[$strVar] : $strDefault);
    break;
  case REQ_POST :
    if( isset($_POST[$strVar]) )
      eval("\$bTest = $strFunctionTestType(\$_POST[\$strVar]);");
    $strVal = (($bTest==true && $_POST[$strVar]!="") ? $_POST[$strVar] : $strDefault);
    break;
  case REQ_GET_POST :
    if( isset($_GET[$strVar]) )
      eval("\$bTest = $strFunctionTestType(\$_GET[\$strVar]);");
    $strVal = (($bTest==true && $_GET[$strVar]!="") ? $_GET[$strVar] : $strDefault);
    if( $strVal==$strDefault ) {
      $bTest = false;
      if( isset($_POST[$strVar]) )
        eval("\$bTest = $strFunctionTestType(\$_POST[\$strVar]);");
      $strVal = (($bTest==true && $_POST[$strVar]!="") ? $_POST[$strVar] : $strDefault);
    }
    break;
  case REQ_POST_GET :
    if( isset($_POST[$strVar]) )
      eval("\$bTest = $strFunctionTestType(\$_POST[\$strVar]);");
    $strVal = (($bTest==true && $_POST[$strVar]!="") ? $_POST[$strVar] : $strDefault);
    if( $strVal==$strDefault ) {
      $bTest = false;
      if( isset($_GET[$strVar]) )
        eval("\$bTest = $strFunctionTestType(\$_GET[\$strVar]);");
      $strVal = (($bTest==true && $_GET[$strVar]!="") ? $_GET[$strVar] : $strDefault);
    }
    break;
  }

  if( is_string($strVal) )
    $strVal = stripslashes($strVal);

  return $strVal;
}

/**
 *  Encode puis retourne une chaine de caractères
 *        représentant un paramètre dans une URL http
 * 
 * @param strParam  Chaine de caractères à encoder
 * @return Retourne un string
 */
function EncodeRequest($strParam)
{
  //encode le paramètre en hexa
  $strEncode = "";
  for($i=0; $i<strlen($strParam); $i++) {
    $strEncode .= dechex(ord($strParam[$i]));
  }
  return $strEncode;
}

/**
 *  Récupère le paramètre http selon la méthode $reqMethod
 *        Décode le param encodée par EncodeRequest() 
 *        Vérifie le format en fonction de $strDefault et $strFunctionTestType
 *        Puis retourne la valeur du paramètre
 * 
 * @param strParam  Chaine de caractères à encoder
 * @return Retourne un string
 */
function RequestDecode($strVar, $reqMethod, $strDefault, $strFunctionTestType="DefaultTest")
{  
  // récupère le paramètre encodé
  $strParam = Request($strVar, $reqMethod, "");

  //décodage
  $strDecode = "";
  for($i=0; $i<strlen($strParam); $i+=2 ) {
    $strDecode .= chr(hexdec($strParam[$i].$strParam[$i+1]));
  }

  // vérifie le format du param décodé
  $bTest = true;
  eval("\$bTest = $strFunctionTestType(\$strDecode);");
  $strDecode = (($bTest==true && $strDecode!="") ? $strDecode : $strDefault);

  return $strDecode;
}

if ( !function_exists( 'hex2bin' ) ) {
    function hex2bin($hexdata) {
      $bindata="";
      $len = strlen( $hexdata );
      for ( $i = 0; $i < $len; $i += 2 ) {
        $bindata.= chr(hexdec(substr($hexdata,$i,2)));
      }

      return $bindata;
    }
}

