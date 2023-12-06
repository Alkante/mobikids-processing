<?php

/*licence/

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Module::Alkanet
Module Alkanet.
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


//Définition des constantes de langues
(!defined("ALK_LENV_BACK")  ? define("ALK_LENV_BACK",  "backoffice")  : '' );
(!defined("ALK_LENV_FRONT") ? define("ALK_LENV_FRONT", "frontoffice") : '' );
(!defined("ALK_LENV_MAIL")  ? define("ALK_LENV_MAIL",  "mail")        : '' );
(!defined("ALK_LTYPE_ID_INTERFACE")  ? define("ALK_LTYPE_ID_INTERFACE",  1) : '' );
(!defined("ALK_LTYPE_ID_DATA")       ? define("ALK_LTYPE_ID_DATA",       2) : '' );

(!defined("ALK_LG_ID_DEFAUT")  ? define("ALK_LG_ID_DEFAUT",  0) : '' );
(!defined("ALK_LG_LOCALE_DEFAUT") ? define("ALK_LG_LOCALE_DEFAUT", "fr_FR") : '' );


/**
 * @package Alkanet_Class_Pattern
*
* @class AlkSessionHandler
* @brief Classe de session handler
*/
final class AlkLangHandler {
  
  /** booléen qui mémorise l'état initial de cette classe */
  protected static $bInit = false;
  
  /** booléen pour traduire les textes liés aux pj qu'une seule fois */
  protected static $bTranslatePj = false;
  
  /** balises pour identifier les éléments traduits */
  protected static $ALK_GT_START = "";
  protected static $ALK_GT_END = "";
  
  /** identifiant de l'espace courant, =1 par défaut pour l'espace racine */
  protected static $cont_id  = 0;
  
  /** précise le type d'interface : =true pour le frontoffice, =false pour le backoffice */
  protected static $bFrontOffice   = true;
  
  /** mémorise les informations de locales par espace */
  protected static $tabSpace = array();
  
  /*****************************************************************************/
  /** Tableaux des langues *****************************************************/
  /*****************************************************************************/
  
  /** tableau contenant toutes les langues activées en base ********************/
  protected static $tabLangsBDD = array();
  
  /** tableau contenant les langues actives pour l'interface *******************/
  protected static $tabAlkLgInterface = array();

  /** tableau contenant les langues actives pour les données *******************/
  protected static $tabAlkLgData = array();
  
  /**
   *  Constructeur par défaut
   */
  public function __construct() {  }

  /**
   * Détermine le type d'interface et met à jour l'attribut bFontOffiche
   */
  public static function initInterface($bFrontOffice=true)
  {
    self::$bFrontOffice = $bFrontOffice;
    if( isset($_SESSION) && isset($_SESSION[""]) ){
      $_SESSION["ALK_B_FRONTOFFICE"] = self::$bFrontOffice;
    }
    return self::$bFrontOffice;
  }
  
  /**
   * Fixe la locale par défaut dans le cas où la notion d'espace de travail n'existe pas.
   * Positionne l'espace courant sur l'indice 0.
   * @param strLocaleName  tableau des noms de locale à utiliser, = array(fr_FR => français - France) par défaut
   */
  public static function setDefaultLocale($tabLocalesName=array())
  {
    self::$cont_id = 0;
  
    if( empty($tabLocalesName) ) {
      $tabLocalesName = ( isset($GLOBALS["tabStrLocales"])
                          ? $GLOBALS["tabStrLocales"]
                          : array("fr_FR" => "français - France") );
    }
    
    self::$tabLangsBDD[0] = array(
      ALK_LENV_BACK => array(
        ALK_LTYPE_ID_INTERFACE =>  array( 
          "_0" => array(
            'lang_id'         => ALK_LG_ID_DEFAUT,
            'lang_name'       => "Français",
            'lang_localename' => "français - France",
            'lang_locale'     => ALK_LG_LOCALE_DEFAUT,
            'lang_suffix'     => "fr",
            'lenv_id'         => 0,
            'lenv_name'       => ALK_LENV_BACK,
            'ltype_id'        => ALK_LTYPE_ID_INTERFACE,
            'ltype_name'      => "Interface",
            'bdd'             => "_FR",
            'rep'             => "fr",
          ),
        ),
        ALK_LTYPE_ID_DATA => array(
          "_0" => array(
            'lang_id'         => ALK_LG_ID_DEFAUT,
            'lang_name'       => "Français",
            'lang_localename' => "français - France",
            'lang_locale'     => ALK_LG_LOCALE_DEFAUT,
            'lang_suffix'     => "fr",
            'lenv_id'         => 0,
            'lenv_name'       => ALK_LENV_BACK,
            'ltype_id'        => ALK_LTYPE_ID_DATA,
            'ltype_name'      => "Données",
            'bdd'             => "_FR",
            'rep'             => "fr",
          ),
        ),
      ),
      ALK_LENV_FRONT => array(
        ALK_LTYPE_ID_INTERFACE => array(
          "_0" => array(
            'lang_id'         => ALK_LG_ID_DEFAUT,
            'lang_name'       => "Français",
            'lang_localename' => "français - France",
            'lang_locale'     => ALK_LG_LOCALE_DEFAUT,
            'lang_suffix'     => "fr",
            'lenv_id'         => 1,
            'lenv_name'       => ALK_LENV_BACK,
            'ltype_id'        => ALK_LTYPE_ID_INTERFACE,
            'ltype_name'      => "Interface",
            'bdd'             => "_FR",
            'rep'             => "fr",
          ),
        ),
        ALK_LTYPE_ID_DATA => array(
          "_0" => array(
            'lang_id'         => ALK_LG_ID_DEFAUT,
            'lang_name'       => "Français",
            'lang_localename' => "français - France",
            'lang_locale'     => ALK_LG_LOCALE_DEFAUT,
            'lang_suffix'     => "fr",
            'lenv_id'         => 1,
            'lenv_name'       => ALK_LENV_FRONT,
            'ltype_id'        => ALK_LTYPE_ID_DATA,
            'ltype_name'      => "Données",
            'bdd'             => "_FR",
            'rep'             => "fr",
          ),
        ),
      ),
      ALK_LENV_MAIL => array(
        ALK_LTYPE_ID_INTERFACE => array(
          "_0" => array(
            'lang_id'         => ALK_LG_ID_DEFAUT,
            'lang_name'       => "Français",
            'lang_localename' => "français - France",
            'lang_locale'     => ALK_LG_LOCALE_DEFAUT,
            'lang_suffix'     => "fr",
            'lenv_id'         => 1,
            'lenv_name'       => ALK_LENV_BACK,
            'ltype_id'        => ALK_LTYPE_ID_INTERFACE,
            'ltype_name'      => "Interface",
            'bdd'             => "_FR",
            'rep'             => "fr",
          ),
        ),
      ),
    );
    
    //On comble les trous avec le tableau de langue du app_conf
    $idLang=0;
    foreach( $tabLocalesName as $keyLocal => $strLocales ){
      $tabTmpLang = array(
        'lang_id'         => $idLang,
        'lang_name'       => "",
        'lang_localename' => $strLocales,
        'lang_locale'     => $keyLocal,
        'lang_suffix'     => substr($keyLocal, 0, 2),
        'bdd'             => substr($keyLocal, -3),
        'rep'             => substr($keyLocal, 0, 2),
      );
      $tabTmpLang['lenv_id']    = 0;
      $tabTmpLang['lenv_name']  = ALK_LENV_BACK;
      $tabTmpLang['ltype_id']   = ALK_LTYPE_ID_INTERFACE;
      $tabTmpLang['ltype_name'] = "Interface";
      self::$tabLangsBDD[0][ALK_LENV_BACK][ALK_LTYPE_ID_INTERFACE]["_".$idLang] = $tabTmpLang;
      $tabTmpLang['ltype_id']   = ALK_LTYPE_ID_DATA;
      $tabTmpLang['ltype_name'] = "Données";
      self::$tabLangsBDD[0][ALK_LENV_BACK][ALK_LTYPE_ID_DATA]["_".$idLang] = $tabTmpLang;
      $tabTmpLang['lenv_id']    = 1;
      $tabTmpLang['lenv_name']  = ALK_LENV_BACK;
      $tabTmpLang['ltype_id']   = ALK_LTYPE_ID_INTERFACE;
      $tabTmpLang['ltype_name'] = "Interface";
      self::$tabLangsBDD[0][ALK_LENV_FRONT][ALK_LTYPE_ID_INTERFACE]["_".$idLang] = $tabTmpLang;
      $tabTmpLang['ltype_id']   = ALK_LTYPE_ID_DATA;
      $tabTmpLang['ltype_name'] = "Données";
      self::$tabLangsBDD[0][ALK_LENV_FRONT][ALK_LTYPE_ID_DATA]["_".$idLang] = $tabTmpLang;
      $tabTmpLang['lenv_id']    = 1;
      $tabTmpLang['lenv_name']  = ALK_LENV_BACK;
      $tabTmpLang['ltype_id']   = ALK_LTYPE_ID_INTERFACE;
      $tabTmpLang['ltype_name'] = "Interface";
      self::$tabLangsBDD[0][ALK_LENV_MAIL][ALK_LTYPE_ID_INTERFACE]["_".$idLang] = $tabTmpLang;
      $idLang++;
    }
    
    $i = 0;
    foreach($tabLocalesName as $strLocale => $strLocaleName) {
      $strLang = substr($strLocale, 0, 2);
      $strBDD = "_".strtoupper($strLang);
      $strRep = $strLang;
      self::$tabSpace[0]["tabLg"][ALK_LENV_BACK][$i]       = $strLang;
      self::$tabSpace[0]["tabLg"][ALK_LENV_FRONT][$i]      = $strLang;
      self::$tabSpace[0]["tabLg"][ALK_LENV_MAIL][$i]       = $strLang;
      self::$tabSpace[0]["tabLocales"][ALK_LENV_BACK][$i]  = $strLocale;
      self::$tabSpace[0]["tabLocales"][ALK_LENV_FRONT][$i] = $strLocale;
      self::$tabSpace[0]["tabLocales"][ALK_LENV_MAIL][$i]  = $strLocale;
      self::$tabSpace[0]["tabStrLocales"][$strLocale]      = $strLocaleName;
      self::$tabSpace[0]["_LG_tab_langue"]                 = array();
      $i++;
    }
    self::getLGTabLangue();
    self::translateConf();
    
    $_SESSION["ALK_TAB_LG_BDD"] = self::$tabLangsBDD;
    $GLOBALS["_LG_tab_langue"] = self::getLGTabLangue();
  }
  
  /**
   * Initialise la prise en change de la langue et de la locale courante
   *
   * Scrute le paramètre Url GET lg pour déterminer un éventuel changement de langue
   * $_GET["lg"] est un indice de la langue compris entre 0 et nbLangue-1
   * sinon fixe la langue et la locale en fonction du second paramètre optionnel $lg
   *
   * Cette fonction est à appeler avant self::initLocale()
   * Met à jour les variables de session :
   *   ALK_LG_ID_DATA_BACK        = indice de la langue sélectionnée, 0 à n-1 pour n langues pour les données du backoffice
   *   ALK_LG_LOCALE_DATA_BACK       = locale utilisée pour les données du backoffice (fr_FR, en_GB, ...)
   *   ALK_LG_ID_DATA_FRONT       = indice de la langue sélectionnée, 0 à n-1 pour n langues pour les données du frontoffice
   *   ALK_LG_LOCALE_DATA_FRONT      = locale utilisée pour les données du frontoffice (fr_FR, en_GB, ...) 
   *   ALK_LG_ID_INTERFACE_BACK   = indice de la langue sélectionnée, 0 à n-1 pour n langues pour l'interface du backoffice
   *   ALK_LG_LOCALE_INTERFACE_BACK  = locale utilisée pour l'interface du backoffice (fr_FR, en_GB, ...)
   *   ALK_LG_ID_INTERFACE_FRONT  = indice de la langue sélectionnée, 0 à n-1 pour n langues pour l'interface du frontoffice
   *   ALK_LG_LOCALE_INTERFACE_FRONT = locale utilisée pour l'interface du frontoffice (fr_FR, en_GB, ...) 
   * @param cont_id   identifiant de l'espace courrant, =0 par défaut pour le cas où pas de gestion d'espace
   * @param $lg       langue sélectionnée par défaut, ="" si non renseignée. La langue sélectionnée correspond à par exemple : fr, en, es...
   */
  protected static function initAlkLocale($cont_id="0", $lg="")
  {
    /** détermination type interface */
    $strKey        = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    $strSessionIDData = ( self::$bFrontOffice ? "ALK_LG_ID_DATA_FRONT" : "ALK_LG_ID_DATA_BACK" );
    $strSessionKeyData = ( self::$bFrontOffice ? "ALK_LG_LOCALE_DATA_FRONT" : "ALK_LG_LOCALE_DATA_BACK" );
    $strSessionIDInterface = ( self::$bFrontOffice ? "ALK_LG_ID_INTERFACE_FRONT" : "ALK_LG_ID_INTERFACE_BACK" );
    $strSessionKeyInterface = ( self::$bFrontOffice ? "ALK_LG_LOCALE_INTERFACE_FRONT" : "ALK_LG_LOCALE_INTERFACE_BACK" );
    
    $cont_id = self::setLocaleSpace($cont_id);
    
    //On assure l'assignation des variables de SESSION
    if( !isset($_SESSION[$strSessionIDData]) )
      $_SESSION[$strSessionIDData] = ALK_LG_ID_DEFAUT;
    if( !isset($_SESSION[$strSessionKeyData]) )
      $_SESSION[$strSessionKeyData] = ALK_LG_LOCALE_DEFAUT;
    if( !isset($_SESSION[$strSessionIDInterface]) )
      $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
    if( !isset($_SESSION[$strSessionKeyInterface]) )
      $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
    
    
    /** variable de gestion I18N */
    self::initLgCategory();
    
    /** Langue sélectionnée par URL */
    if( isset($_GET["lg"]) && ( $_GET["lg"]!="" || $_GET["lg"] === 0) &&
        is_numeric($_GET["lg"]) && $_GET["lg"]>=0 && floor($_GET["lg"])==$_GET["lg"] ) {
      $lg = $_GET["lg"];
      if( isset( self::$tabLangsBDD[$cont_id][$strKey] ) ){
        foreach( self::$tabLangsBDD[$cont_id][$strKey] as $lTypeID => $tabLangByLType ){
          if( $lTypeID == ALK_LTYPE_ID_INTERFACE ){
            if( $lg != "" && isset($tabLangByLType["_".$lg]) ){
              $bRes = setlocale(constant(ALK_LG_CATEGORY), $tabLangByLType["_".$lg]['lang_locale']);
              if( $bRes !== false ) {
//                echo '<br/><span style="color:green" >SETLOCALE : '.$tabLangByLType["_".$lg]['lang_locale'].'</span>';
                $_SESSION[$strSessionIDInterface] = $tabLangByLType["_".$lg]['lang_id'];
                $_SESSION[$strSessionKeyInterface] = $tabLangByLType["_".$lg]['lang_locale'];
              }else{
//                echo '<br/><span style="color:red" >ECHEC SETLOCALE : '.$tabLangByLType["_".$lg]['lang_locale'].'</span>';
//                echo '<br/><span style="color:orange" >REPLACE BY SETLOCALE : '.ALK_LG_LOCALE_DEFAUT.'</span>';
                setlocale(constant(ALK_LG_CATEGORY), ALK_LG_LOCALE_DEFAUT);
                $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
                $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
              }
            }
          }elseif( $lTypeID == ALK_LTYPE_ID_DATA ){
            if( $lg != "" && isset($tabLangByLType["_".$lg]) ){
              $_SESSION[$strSessionIDData] = $tabLangByLType["_".$lg]['lang_id'];
              $_SESSION[$strSessionKeyData] = $tabLangByLType["_".$lg]['lang_locale'];
            }
          }
        }
      }
    }else if( $lg !== "" ) {
      $lg_id = ( isset( self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg]) ? self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg]['lang_id'] : false);
      if( $lg_id === false ) {
        $_SESSION[$strSessionIDData] = ALK_LG_ID_DEFAUT;
        $_SESSION[$strSessionKeyData] = ALK_LG_LOCALE_DEFAUT;
      }else{
        $_SESSION[$strSessionIDData] = self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg_id]['lang_id'];
        $_SESSION[$strSessionKeyData] = self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg_id]['lang_locale'];
        
      }
      $lg_id = (  isset( self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$lg]) 
                  ? self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg]['lang_id'] 
                  : false
                );
      if( $lg_id === false ) {//La langue demandée pour l'interface n'est pas disponible
        //Si on n'a pas trouvé la langue testé dans Interface, on regarde si on ne peut pas prendre la meme langue que celle des données
        $lg_id = ( isset( self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$_SESSION[$strSessionIDData]]) 
                   ? self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$_SESSION[$strSessionIDData]]['lang_id'] 
                   : false
                  );
        if($lg_id === false){ // La langue utilisée pour les données n'est pas disponible pour l'interface
//          echo '<br/><span style="color:green" >SETLOCALE : '.ALK_LG_LOCALE_DEFAUT.' car langue demandé non existante</span>';
          setlocale(constant(ALK_LG_CATEGORY), ALK_LG_LOCALE_DEFAUT);
          $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
          $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
        }else{// La langue utilisée pour les données est disponible pour l'interface
          $bRes = setlocale(constant(ALK_LG_CATEGORY), $_SESSION[$strSessionKeyData]);
          if( $bRes !== false ) {
//            echo '<br/><span style="color:green" >SETLOCALE : '.$_SESSION[$strSessionKeyData].'</span>';
            $_SESSION[$strSessionIDInterface] = $_SESSION[$strSessionIDData];
            $_SESSION[$strSessionKeyInterface] = $_SESSION[$strSessionKeyData];
          }else{//La langue est mal installée, on charge donc celle par défaut
//            echo '<br/><span style="color:red" >ECHEC SETLOCALE : '.$_SESSION[$strSessionKeyData].'</span>';
//            echo '<br/><span style="color:orange" >REPLACE BY SETLOCALE : '.ALK_LG_LOCALE_DEFAUT.'</span>';
            setlocale(constant(ALK_LG_CATEGORY), ALK_LG_LOCALE_DEFAUT);
            $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
            $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
          }
        }
      }else{//La langue demandée pour l'interface est disponible
        $bRes = setlocale(constant(ALK_LG_CATEGORY), self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg_id]['lang_locale']);
        if( $bRes !== false ) {
//          echo '<br/><span style="color:green" >SETLOCALE : '.self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$lg_id]['lang_locale'].'</span>';
          $_SESSION[$strSessionIDInterface] = self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$lg_id]['lang_id'];
          $_SESSION[$strSessionKeyInterface] = self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$lg_id]['lang_locale'];
        }else{//La langue est mal installée, on charge donc celle par défaut
//          echo '<br/><span style="color:red" >ECHEC SETLOCALE : '.self::$tabLangsBDD[$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$lg_id]['lang_locale'].'</span>';
//          echo '<br/><span style="color:orange" >REPLACE BY SETLOCALE : '.ALK_LG_LOCALE_DEFAUT.'</span>';
          setlocale(constant(ALK_LG_CATEGORY), ALK_LG_LOCALE_DEFAUT);
          $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
          $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
        }
      }
    }else{
      //On est ici dans le cas où on arrive sur la page mais sans forcément avec un parametre, on vérifie que ce qui est en session existe bien
      $strLenv = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
      
      if( ! isset(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_DATA]['_'.$_SESSION[$strSessionIDData]]) ){
        //Ce qui est en session n'existe pas, on ecrase donc la session avec la première valeur disponible
        foreach(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_DATA] as $tabLang){
          $_SESSION[$strSessionIDData] = $tabLang['lang_id'];
          $_SESSION[$strSessionKeyData] = $tabLang['lang_locale'];
        }
      }
      
      if( ! isset(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_INTERFACE]['_'.$_SESSION[$strSessionIDInterface]]) ){
        //Ce qui est en session n'existe pas, on ecrase donc la session avec la première valeur disponible
        $indLang = 1;
        foreach(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_INTERFACE] as $tabLang){
          $bRes = setlocale(constant(ALK_LG_CATEGORY), $tabLang['lang_locale']);
          if( $bRes !== false ) {
            $_SESSION[$strSessionIDInterface] = $tabLang['lang_id'];
            $_SESSION[$strSessionKeyInterface] = $tabLang['lang_locale'];
            break;
          }elseif( $indLang == count( array_keys(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_INTERFACE])) ){//La langue est mal installée, on charge donc celle par défaut
            setlocale(constant(ALK_LG_CATEGORY), ALK_LG_LOCALE_DEFAUT);
            $_SESSION[$strSessionIDInterface] = ALK_LG_ID_DEFAUT;
            $_SESSION[$strSessionKeyInterface] = ALK_LG_LOCALE_DEFAUT;
          }
          $indLang++;
        }
      }
    }
    
    /**************************************************************************/
    /** UNe fois les locales affectées et les sessions également, *************/
    /** on réordonne le tableau self::$tabLangsBDD ****************************/
    /**************************************************************************/
    $lgIDFirstData = ( isset($_SESSION[$strSessionIDData]) ? $_SESSION[$strSessionIDData] : -1 );
    $lgSuffixFirstData = ( isset($_SESSION[$strSessionKeyData]) ? substr($_SESSION[$strSessionKeyData], 0, 2) : substr(ALK_LG_LOCALE_DEFAUT, 0, 2) );
    $lgLocaleFirstData = ( isset($_SESSION[$strSessionKeyData]) ? $_SESSION[$strSessionKeyData] : ALK_LG_LOCALE_DEFAUT );
    $lgIDFirstInterface = ( isset($_SESSION[$strSessionIDInterface]) ? $_SESSION[$strSessionIDInterface] : -1 );
    $lgSuffixFirstInterface = ( isset($_SESSION[$strSessionKeyInterface]) ? substr($_SESSION[$strSessionKeyInterface], 0, 2) : substr(ALK_LG_LOCALE_DEFAUT, 0, 2) );
    $lgLocaleFirstInterface = ( isset($_SESSION[$strSessionKeyInterface]) ? $_SESSION[$strSessionKeyInterface] : ALK_LG_LOCALE_DEFAUT );
    
    if( isset($_SESSION[$strSessionKeyInterface]) ){
      $_SESSION["alk_userLg"] = $_SESSION[$strSessionKeyInterface];
    }
    
    foreach( self::$tabLangsBDD[self::$cont_id] as $lang_env => $tabLangEnv){
      foreach( $tabLangEnv as $lang_type => $tabLangType ){
        $idTmpLang = ( $lang_type == ALK_LTYPE_ID_INTERFACE ? $lgIDFirstInterface : $lgIDFirstData );
        if( isset(self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type]["_".$idTmpLang])){
          $tabSaveLangFirst = array( "_".$idTmpLang => self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type]["_".$idTmpLang]);
          unset(self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type]["_".$idTmpLang]);
          ksort(self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type]);
          self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type] = array_merge($tabSaveLangFirst, self::$tabLangsBDD[self::$cont_id][$lang_env][$lang_type]);
        }
        unset($idTmpLang);
      }
    }
    
    //_LG_tab_langue
    unset(self::$tabSpace[self::$cont_id]["_LG_tab_langue"][ALK_LENV_FRONT]);
    $GLOBALS["_LG_tab_langue"] = self::getLGTabLangue();
        
    //tabLg (Mode Data uniquement), identifié par lang_id
    foreach(self::$tabSpace[self::$cont_id]["tabLg"] as $lang_env => $tabLangEnv ){
//    if(isset(self::$tabSpace[self::$cont_id]["tabLg"][$lgIDFirstData])){
      $indLang = array_search($lgSuffixFirstData, self::$tabSpace[self::$cont_id]["tabLg"][$lang_env]);
      if( $indLang !== FALSE){
        $tabSaveLangFirst = array( $indLang => self::$tabSpace[self::$cont_id]["tabLg"][$lang_env][$indLang]);
        array_splice(self::$tabSpace[self::$cont_id]["tabLg"][$lang_env], $indLang,1);
        self::$tabSpace[self::$cont_id]["tabLg"][$lang_env] = array_merge($tabSaveLangFirst, self::$tabSpace[self::$cont_id]["tabLg"][$lang_env] );
      }
    }
    
    //tabLocales (Mode Interface Uniquement), identifié par lang_id
    foreach(self::$tabSpace[self::$cont_id]["tabLocales"] as $lang_env => $tabLangEnv ){
      $indLang = array_search($lgLocaleFirstInterface, self::$tabSpace[self::$cont_id]["tabLocales"][$lang_env]);
      if( $indLang !== FALSE){
        $tabSaveLangFirst = array( $indLang => self::$tabSpace[self::$cont_id]["tabLocales"][$lang_env][$indLang]);
        array_splice(self::$tabSpace[self::$cont_id]["tabLocales"][$lang_env], $indLang,1);
        self::$tabSpace[self::$cont_id]["tabLocales"][$lang_env] = array_merge($tabSaveLangFirst, self::$tabSpace[self::$cont_id]["tabLocales"][$lang_env] );
      }
    }
    
    //tabStrLocales, identifié par lang_locale
    $indLang = array_search($lgLocaleFirstInterface, array_keys(self::$tabSpace[self::$cont_id]["tabStrLocales"]));
    if( $indLang !== FALSE){
      $tabSaveLangFirst = array( $lgLocaleFirstInterface => self::$tabSpace[self::$cont_id]["tabStrLocales"][$lgLocaleFirstInterface]);
      array_splice(self::$tabSpace[self::$cont_id]["tabStrLocales"], $indLang,1);
      self::$tabSpace[self::$cont_id]["tabStrLocales"] = array_merge($tabSaveLangFirst, self::$tabSpace[self::$cont_id]["tabStrLocales"] );
    }    
  }
  
  /**
   * Initialise les paramètres de langue et de locales 
   * Utilise les informations mémorisées en session si dispo.
   * Ne met pas à jour les informations en session.
   * 
   * Définit les constantes suivantes si celles-ci ne l'ont pas encore été :
   *   ALK_LG_ID_DATA      = indice de la langue sélectionnée, 0 à n-1 pour n langues
   *                       : var session : ALK_LG_ID_DATA_BACK     : valeur backoffice
   *                                     : ALK_LG_ID_DATA_FRONT    : valeur frontoffice
   *   ALK_LG_LOCALE_DATA     = fr_FR, en_GB, ....
   *                       : var session : ALK_LG_LOCALE_DATA_BACK     : valeur backoffice
   *                                     : ALK_LG_LOCALE_DATA_FRONT    : valeur frontoffice
   *
   *   ALK_LG_ID_INTERFACE = indice de la langue sélectionnée, 0 à n-1 pour n langues
   *                    : var session : ALK_LG_ID_INTERFACE_BACK   : valeur backoffice
   *                                  : ALK_LG_ID_INTERFACE_FRONT  : valeur frontoffice
   *
   *   ALK_LG_LOCALE_INTERFACE = fr_FR, en_GB, ....
   *                    : var session : ALK_LG_LOCALE_INTERFACE_BACK   : valeur backoffice
   *                                  : ALK_LG_LOCALE_INTERFACE_FRONT  : valeur frontoffice
   *   
   *   ALK_MAIL_ID      = indice de la langue sélectionnée, 0 à n-1 pour n langues
   *   ALK_MAIL_KEY     = fr_FR, en_GB, ....
   *   ALK_LG_BDD       = _FR, _GB, ... (calculé en fonction de ALK_LG_ID_DATA)
   *   ALK_LG_REP       = fr, en, ....  (calculé en fonction de ALK_LG_ID_DATA)
   *   ALK_LG_CATEGORY  = LC_ALL (pour plate-forme windows) ou LC_MESSAGES (pour plate-forme linux)
   *   ALK_LG_DOMAIN    = locales_php
   *   ALK_LG_DOMAIN_JS = locales_js
   *   ALK_LG_JSON      = url vers le fichier js contenant la traduction courante (enregistré en session)
   * @param cont_id   identifiant de l'espace courrant, =0 par défaut pour le cas où pas de gestion d'espace
   * @param $lg       langue sélectionnée par défaut, ="" si non renseignée. La langue sélectionnée correspond à par exemple : fr, en, es...
   */
  public static function initLocale($cont_id="0", $lg="")
  {
    if( self::$bInit ) return false;
    
    self::$bInit = false;
    if( !isset(self::$tabSpace[0]) ) {
      self::setDefaultLocale();
    }
    self::initAlkLocale($cont_id, $lg);
    $strKey = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    self::$cont_id;
    
    /***************************************************************************/
    /** On cherche à récupérer depuis la base les langues actives **************/
    /** pour l'espace courant **************************************************/
    /***************************************************************************/
    $_SESSION["ALK_TAB_LG_BDD"] = self::$tabLangsBDD;
    
    //A partir de là, on récupère le tableau des langues dans leur ordre d'affichage pour l'interface
    //TODO: TRAVAIL ENCORE A FAIRE, FOURNIR LES TABLEAUX DE LANGUES POUR CHAQUE CATEGORIE D'AFFICHAGE
    $tabTmpLangInterface = self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE];
    self::$tabAlkLgInterface = array();
    foreach( $tabTmpLangInterface as $slang_id => $tabLgInterface){
      self::$tabAlkLgInterface[] =  $tabLgInterface;
    }
    $tabTmpLangData = self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA];
    self::$tabAlkLgData = array();
    foreach( $tabTmpLangData as $slang_id => $tabLgData){
      self::$tabAlkLgData[] =  $tabLgData;
    }
    $_SESSION["ALK_TAB_LG_DATA"] = self::$tabAlkLgData;
    $_SESSION["ALK_TAB_LG_INTERFACE"] = self::$tabAlkLgInterface;
    
    // Langue en cours des données à afficher   
    if( !defined("ALK_LG_ID_DATA") ) {
      define("ALK_LG_ID_DATA", ( self::$bFrontOffice 
                         ? ( isset($_SESSION["ALK_LG_ID_DATA_FRONT"]) 
                             ? $_SESSION["ALK_LG_ID_DATA_FRONT"] 
                             : ALK_LG_ID_DEFAUT )
                         : ( isset($_SESSION["ALK_LG_ID_DATA_BACK"])          
                             ? $_SESSION["ALK_LG_ID_DATA_BACK"]          
                             : ALK_LG_ID_DEFAUT ) ));
    }
    if( !defined("ALK_LG_LOCALE_DATA") ) {
      define("ALK_LG_LOCALE_DATA", ( self::$bFrontOffice 
                         ? ( isset($_SESSION["ALK_LG_LOCALE_DATA_FRONT"]) 
                             ? $_SESSION["ALK_LG_LOCALE_DATA_FRONT"] 
                             : ALK_LG_LOCALE_DEFAUT )
                         : ( isset($_SESSION["ALK_LG_LOCALE_DATA_BACK"])          
                             ? $_SESSION["ALK_LG_LOCALE_DATA_BACK"]          
                             : ALK_LG_LOCALE_DEFAUT ) ));
    }

    // langue en cours des éléments affichés côté interface
    if( !defined("ALK_LG_ID_INTERFACE") ) {
      define("ALK_LG_ID_INTERFACE", ( self::$bFrontOffice
                                ? ( isset($_SESSION["ALK_LG_ID_INTERFACE_FRONT"]) 
                                    ? $_SESSION["ALK_LG_ID_INTERFACE_FRONT"] 
                                    : ALK_LG_ID_DEFAUT )
                                : ( isset($_SESSION["ALK_LG_ID_INTERFACE_BACK"])          
                                    ? $_SESSION["ALK_LG_ID_INTERFACE_BACK"]          
                                    : ALK_LG_ID_DEFAUT ) ));
    }
    // langue en cours des éléments affichés côté interface
    if( !defined("ALK_LG_LOCALE_INTERFACE") ) {
      define("ALK_LG_LOCALE_INTERFACE", ( self::$bFrontOffice
                                ? ( isset($_SESSION["ALK_LG_LOCALE_INTERFACE_FRONT"]) 
                                    ? $_SESSION["ALK_LG_LOCALE_INTERFACE_FRONT"] 
                                    : ALK_LG_LOCALE_DEFAUT )
                                : ( isset($_SESSION["ALK_LG_LOCALE_INTERFACE_BACK"])          
                                    ? $_SESSION["ALK_LG_LOCALE_INTERFACE_BACK"]          
                                    : ALK_LG_LOCALE_DEFAUT ) ));
    }

    //Vérifier selon le but recherché ici par la personne ayant implémenté la sélection d'une langue particulière pour les mail,
    // il faudra peut être prendre ALK_LG_ID_DATA et ALK_LG_LOCALE_DATA au lieu de respectivement, ALK_LG_ID_INTERFACE et ALK_LG_LOCALE_INTERFACE
//    if( !defined("ALK_MAIL_ID") || !defined("ALK_MAIL_KEY") ) {
    if( !defined("ALK_LG_ID_MAIL") || !defined("ALK_LG_LOCALE_MAIL") ) {
      // la locale courante est utilisée pour envoyer le mail si celle-ci fait partie des locales disponibles
      // sinon, on utilise la locale par défaut pour l'envoi de mail
      $idMailLocale = ALK_LG_ID_INTERFACE;
      $strMailLocale = ALK_LG_LOCALE_INTERFACE;
      if( isset(self::$tabLangsBDD[self::$cont_id][ALK_LENV_MAIL][ALK_LTYPE_ID_INTERFACE]["_".ALK_LG_ID_INTERFACE]) ){
        $idMailLocale = self::$tabLangsBDD[self::$cont_id][ALK_LENV_MAIL][ALK_LTYPE_ID_INTERFACE]["_".ALK_LG_ID_INTERFACE]['lang_id'];
        $strMailLocale = self::$tabLangsBDD[self::$cont_id][ALK_LENV_MAIL][ALK_LTYPE_ID_INTERFACE]["_".ALK_LG_ID_INTERFACE]['lang_locale'];
      }

      ( !defined("ALK_LG_ID_MAIL") ? define("ALK_LG_ID_MAIL" , $idMailLocale) : "" );
      ( !defined("ALK_LG_LOCALE_MAIL") ? define("ALK_LG_LOCALE_MAIL", $strMailLocale) : "" );
    }

    if( !defined("ALK_LG_BDD") ) {
      define("ALK_LG_BDD", self::$tabSpace[self::$cont_id]["_LG_tab_langue"][$strKey][ALK_LG_ID_DATA]["bdd"]);
    }
    if( !defined("ALK_LG_REP") ) {
      define("ALK_LG_REP", self::$tabSpace[self::$cont_id]["_LG_tab_langue"][$strKey][ALK_LG_ID_DATA]["rep"]);
    }
    
    /** variable de gestion I18N */
    self::initLgCategory();
    
    if( !defined("ALK_LG_DOMAIN")   ) define("ALK_LG_DOMAIN", "locales_php");
    if( !defined("ALK_LG_DOMAIN_JS")   ) define("ALK_LG_DOMAIN_JS", "locales_js");
    
    if( ALK_LG_LOCALE_INTERFACE != "" &&
        
        is_dir(ALK_ALKANET_ROOT_PATH."locales/".ALK_LG_LOCALE_INTERFACE."/".ALK_LG_CATEGORY) ) {

      if( !self::setAlkLocale(ALK_LG_LOCALE_INTERFACE) ) {
        //exit("Erreur de locale : ".ALK_LG_LOCALE_INTERFACE." non installée");
      } 
    
      if( !defined("ALK_LG_JSON") ) {
        if( isset($_SESSION["ALK_LG_JSON"]) ) {
          define("ALK_LG_JSON", $_SESSION["ALK_LG_JSON"]);
        } else {
          $strPathJson = ALK_ALKANET_ROOT_PATH."locales/".ALK_LG_DOMAIN_JS.".json";
          $strUrlJson = ALK_ALKANET_ROOT_URL."locales/".ALK_LG_DOMAIN_JS.".json";
          if( file_exists($strPathJson) && is_file($strPathJson) ) {
            define("ALK_LG_JSON", $strUrlJson);
            $_SESSION["ALK_LG_JSON"] = $strUrlJson;
          } else {
            $strPathJson = ALK_ALKANET_ROOT_PATH."locales/".ALK_LG_LOCALE_INTERFACE."/".ALK_LG_CATEGORY."/".ALK_LG_DOMAIN_JS.".json";
            $strUrlJson = ALK_ALKANET_ROOT_URL."locales/".ALK_LG_LOCALE_INTERFACE."/".ALK_LG_CATEGORY."/".ALK_LG_DOMAIN_JS.".json";
            if( file_exists($strPathJson) && is_file($strPathJson) ) {
              define("ALK_LG_JSON", $strUrlJson);
              $_SESSION["ALK_LG_JSON"] = $strUrlJson;
            }
          }
        }
      }
    }
    self::$bInit = true;
    return true;
  }
  
  /**
   * Fixe l'espace courant et charge si nécessaire les informations de langues et de locales liées à cet espace
   * @param cont_id identifiant de l'espace courant, =0 par défaut pour charger toutes les langues disponibles et l'associé à cet espace par défaut identifié par cont_id=0
   * @return int : l'identifiant de l'espace courant 
   */
  public static function setLocaleSpace($cont_id="0")
  {
    $cont_id = floor($cont_id*1);
    $iPrev = self::$cont_id; 
    self::$cont_id = $cont_id;
    
    if( $cont_id=="0" || isset(self::$tabSpace[$cont_id]) ) { 
      return $cont_id; 
    }
       
    $dbConn = AlkFactory::getDbConn();
    $strSql = "select l.*, cl.CONT_ID, cl.LTYPE_ID, cl.LENV_ID, cl.LANG_RANK, lt.LTYPE_NAME, le.LENV_NAME".
      " from ALK_LANG l".
      "  left join SIT_CONT_LANG cl on cl.LANG_ID=l.LANG_ID".
      "  left join ALK_LANG_TYPE lt on cl.LTYPE_ID=lt.LTYPE_ID".
      "  left join ALK_LANG_ENV le on cl.LENV_ID=le.LENV_ID".
      " where "." l.LANG_VISIBLE=1 and"." cl.CONT_ID=".$cont_id.
      " order by cl.LENV_ID, cl.LTYPE_ID, cl.LANG_RANK";
    $dsLang = $dbConn->getDs($strSql);
    if( $dsLang->getCountTotDr() > 0 ) { 
      self::$tabSpace[$cont_id] = array();
      while( $drLang = $dsLang->fetch() ) {
        $lang_id         = $drLang["LANG_ID"];
        $lenv_id         = $drLang["LENV_ID"];
        $lenv_name       = strtolower($drLang["LENV_NAME"]);
        $lang_suffix     = $drLang["LANG_SUFFIX"];
        $ltype_id        = $drLang["LTYPE_ID"];
        $ltype_name      = $drLang["LTYPE_NAME"];
        $lang_locale     = $drLang["LANG_LOCALE"];
        $lang_name       = $drLang["LANG_NAME"];
        $lang_localename = $drLang["LANG_LOCALENAME"];

        if( !isset(self::$tabLangsBDD[$cont_id]) ){
          self::$tabLangsBDD[$cont_id] = array();
        }
        if( !isset(self::$tabLangsBDD[$cont_id][$lenv_name]) ){
          self::$tabLangsBDD[$cont_id][$lenv_name] = array();
        }
        if( !isset(self::$tabLangsBDD[$cont_id][$lenv_name][$ltype_id]) ){
          self::$tabLangsBDD[$cont_id][$lenv_name][$ltype_id] = array();
        }
        self::$tabLangsBDD[$cont_id][$lenv_name][$ltype_id]["_".$lang_id] = array(
            'lang_id'         => $lang_id,
            'lang_name'       => $lang_name,
            'lang_localename' => $lang_localename,
            'lang_locale'     => $lang_locale,
            'lang_suffix'     => $lang_suffix,
            'lenv_id'         => $lenv_id,
            'lenv_name'       => $lenv_name,
            'ltype_id'        => $ltype_id,
            'ltype_name'      => $ltype_name,
            'bdd'             => "_".strtoupper($lang_suffix),
            'rep'             => $lang_suffix,
        );
        
        if( $ltype_id == "2" ) { // donnée
          self::$tabSpace[$cont_id]["tabLg"][$lenv_name][] = $lang_suffix;
        } else { // interface
          self::$tabSpace[$cont_id]["tabLocales"][$lenv_name][] = $lang_locale;
        }
        if( !isset(self::$tabSpace[$cont_id]["tabStrLocales"][$lang_locale]) ) {
          self::$tabSpace[$cont_id]["tabStrLocales"][$lang_locale] = $lang_localename;
        }
      }
      // calcul le tableau de langue et retourne l'identifiant du nouvel espace sélectionné
      self::getLGTabLangue();
      self::translateConf();
      return $cont_id;
    }
    
    // n'a rien fait, retourne l'identifiant de l'espace précédent cet appel
    self::$cont_id = $iPrev;
    return $iPrev;
  }
  
  /**
   * Retourne la locale courrante en fonction du contexte courant. Exemple : fr_FR, en_GB, ...
   * @return string
   */
  public static function getCurrentLocale($ltype=ALK_LTYPE_ID_INTERFACE)
  {
    $strLocaleReturn = "";
    
    if(self::$bInit ){
      if( self::$bFrontOffice ){
        $strLocaleReturn = ( $ltype == ALK_LTYPE_ID_DATA ? $_SESSION["ALK_LG_LOCALE_DATA_FRONT"] : $_SESSION["ALK_LG_LOCALE_INTERFACE_FRONT"] );
      }else{
        $strLocaleReturn = ( $ltype == ALK_LTYPE_ID_DATA ? $_SESSION["ALK_LG_LOCALE_DATA_BACK"] : $_SESSION["ALK_LG_LOCALE_INTERFACE_BACK"] );
      }
    }else{
      $strLocaleReturn = ALK_LG_LOCALE_DEFAUT;
      if( self::$bFrontOffice ){
        if( isset(self::$tabLangsBDD[0][ALK_LENV_FRONT][$ltype]) && is_array(self::$tabLangsBDD[0][ALK_LENV_FRONT][$ltype]) && count(self::$tabLangsBDD[0][ALK_LENV_FRONT][$ltype]) > 0 ){
          $tabTmpLang = reset(self::$tabLangsBDD[0][ALK_LENV_FRONT][$ltype]);
          $strLocaleReturn = $tabTmpLang['lang_locale'];
        }
      }else{
        if( isset(self::$tabLangsBDD[0][ALK_LENV_BACK][$ltype]) && is_array(self::$tabLangsBDD[0][ALK_LENV_BACK][$ltype]) && count(self::$tabLangsBDD[0][ALK_LENV_BACK][$ltype]) > 0 ){
          $tabTmpLang = reset(self::$tabLangsBDD[0][ALK_LENV_BACK][$ltype]);
          $strLocaleReturn = $tabTmpLang['lang_locale'];
        }
      }
    }
    return $strLocaleReturn;
  }

  /**
   * Retourne le code JS initialisant le tableau de langue utilisé pour la traduction js
   * Déclare le tableau js tabLang et la variable js ALK_LG_LOCALE_INTERFACE et ALK_LG_ID_INTERFACE 
   * @return string
   */
  public static function getJSTabLang() 
  {
    $strKey  = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    $cont_id = self::$cont_id;
    $idLgLocaleInterface = ALK_LG_ID_INTERFACE;
    $idLgLocaleData = ALK_LG_ID_DATA;

    $iLocaleInterface = ( isset(self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$idLgLocaleInterface]) 
            ? self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$idLgLocaleInterface]['lang_id'] 
            : ALK_LG_ID_DEFAUT );
    $strLocaleInterface = ( isset(self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$idLgLocaleInterface]) 
            ? self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_INTERFACE]["_".$idLgLocaleInterface]['lang_locale'] 
            : ALK_LG_LOCALE_DEFAUT );
    $iLocaleData = ( isset(self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$idLgLocaleData]) 
            ? self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$idLgLocaleData]['lang_id'] 
            : ALK_LG_ID_DEFAUT );
    $strLocaleData = ( isset(self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$idLgLocaleData]) 
            ? self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA]["_".$idLgLocaleData]['lang_locale'] 
            : ALK_LG_LOCALE_DEFAUT );
    
    $strJsHtml = " var tabLang = new Array();";
    foreach(self::$tabLangsBDD[self::$cont_id][$strKey][ALK_LTYPE_ID_DATA] as $tabLg){      
      $keyLg = $tabLg['lang_id'];
      $strLg = $tabLg['lang_suffix'];
      $strJsHtml .= " tabLang[".$keyLg."]='_".strtoupper($strLg)."';";
    }
    $strJsHtml .= " var ALK_LG_ID_INTERFACE = ".$iLocaleInterface.";";
    $strJsHtml .= " var ALK_LG_LOCALE_INTERFACE = '".$strLocaleInterface."';";
    $strJsHtml .= " var ALK_LG_ID_DATA = ".$iLocaleData.";";
    $strJsHtml .= " var ALK_LG_LOCALE_DATA = '".$strLocaleData."';";
    
    return $strJsHtml;
  }
  
  /**
   * Retourne le tableau de langue nécessaire aux objets qui héritent de AlkObject pour mettre à jour l'attribut tabLangue. 
   * Utilise les informations de configuration courante stokées dans cette classe : bFrontOffice et cont_id
   * @return array
   */
  public static function getLGTabLangue()
  {
    $strKey  = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    $tabTmpReturn = self::getTabLg();
    return $tabTmpReturn[$strKey];
  }
  
  /**
   * Retourne le tableau de langue nécessaire aux objets qui héritent de AlkObject pour mettre à jour l'attribut tabLangue. 
   * Utilise les informations de configuration courante stokées dans cette classe : bFrontOffice et cont_id
   * @return array
   */
  public static function getTabLg()
  {
    $tabKey  = array(ALK_LENV_FRONT, ALK_LENV_BACK);
     
    if( isset(self::$tabSpace[self::$cont_id]["_LG_tab_langue"][ALK_LENV_FRONT]) && isset(self::$tabSpace[self::$cont_id]["_LG_tab_langue"][ALK_LENV_BACK]) ) {
      return self::$tabSpace[self::$cont_id]["_LG_tab_langue"];
    }
    foreach($tabKey as $sKey) {
      if( isset(self::$tabLangsBDD[self::$cont_id][$sKey]) && isset(self::$tabLangsBDD[self::$cont_id][$sKey][ALK_LTYPE_ID_DATA]) ){
        self::$tabSpace[self::$cont_id]["_LG_tab_langue"][$sKey] = array();
        foreach(self::$tabLangsBDD[self::$cont_id][$sKey][ALK_LTYPE_ID_DATA] as $iKey => $tabLang){
          if( !defined("ALK_LG_ID_DATA_".strtoupper($tabLang['lang_suffix'])) ) {
            define("ALK_LG_ID_DATA_".strtoupper($tabLang['lang_suffix']), $tabLang['lang_id']);
          }
          if( !defined("ALK_LG_LOCALE_DATA_".strtoupper($tabLang['lang_suffix'])) ) {
            define("ALK_LG_LOCALE_DATA_".strtoupper($tabLang['lang_suffix']), $tabLang['lang_locale']);
          }
          self::$tabSpace[self::$cont_id]["_LG_tab_langue"][$sKey][$tabLang['lang_id']] = array("bdd" => "_".strtoupper($tabLang['lang_suffix']), "rep" => $tabLang['lang_suffix']);
        }
      }
    }
    return self::$tabSpace[self::$cont_id]["_LG_tab_langue"];
  }
  
  /**
   * Retourne le tableau de langue nécessaire aux objets qui héritent de AlkObject pour mettre à jour l'attribut tabLangue. 
   * Utilise les informations de configuration courante stokées dans cette classe : bFrontOffice et cont_id
   * @return array
   */
  public static function getTabLocales()
  {
    $cont_id = self::$cont_id;
    if( isset(self::$tabSpace[$cont_id]["tabLocales"]) ) {
      return self::$tabSpace[$cont_id]["tabLocales"];
    }
  }
  
  /**
   * Retourne le tableau de langue nécessaire aux objets qui héritent de AlkObject pour mettre à jour l'attribut tabLangue. 
   * Utilise les informations de configuration courante stokées dans cette classe : bFrontOffice et cont_id
   * @return array
   */
  public static function getTabStrLocales($bSmarty=false)
  {
    $cont_id = self::$cont_id;
    $strLenv = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    self::$tabSpace[self::$cont_id]["tabStrLocales"] = array();
    if( isset(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_INTERFACE]) ) {
      foreach(self::$tabLangsBDD[self::$cont_id][$strLenv][ALK_LTYPE_ID_INTERFACE] as $tabLg){
        if($bSmarty){
          self::$tabSpace[self::$cont_id]["tabStrLocales"][$tabLg["lang_id"]]= array( 'lang_locale' => $tabLg['lang_locale'], 'lang_localname' => $tabLg['lang_localename']);
        }else{
          self::$tabSpace[self::$cont_id]["tabStrLocales"][$tabLg['lang_locale']] = $tabLg['lang_localename'];
        }
      }
    }
    
    if( isset(self::$tabSpace[$cont_id]["tabStrLocales"]) ) {
      return self::$tabSpace[$cont_id]["tabStrLocales"];
    }
  }
  
  /**
   * Retourne la langue sélectionnée par défaut obtenue à partir de la configuration du navigateur
   * @param cont_id   identifiant de l'espace en cours, =-1 par défaut pour prendre celui stocké.
   * @param cont_id  identifiant de l'espace courant, =-1 par défaut pour ne pas prendre en compte ce paramètre
   * @return string : fr, en, ....
   */
  public static function getDefaultNavLang($cont_id="-1")
  {
    self::initInterface();
    $strKey  = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    
    $cont_id = ( $cont_id == "-1" ? self::$cont_id : $cont_id );
    $cont_id = self::setLocaleSpace($cont_id);
    
    //recherche de la langue du navigateur client
    $strLgClient = "";
    if( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
      $strLgClient = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
      $strLgClient = strtolower(substr(rtrim($strLgClient[0]), 0, 2));
    }
    if( $strLgClient=="" || 
        $strLgClient!="" && !in_array($strLgClient, self::$tabSpace[$cont_id]["tabLg"][$strKey]) ) {
      $strLgClient = strtolower(substr(self::$tabSpace[$cont_id]["tabLg"][$strKey][0], 0, 2));
    }
    return $strLgClient; 
  }
  
  /**
   * Fixe une locale, retourne true si ok, false sinon
   * @param strNewLocale  nouvelle locale souhaitée
   * @return boolean
   */
  public static function setAlkLocale($strNewLocale)
  {
    putenv(constant(ALK_LG_CATEGORY)."=".$strNewLocale); // nécessaire pour windows
    $bRes = @setLocale(constant(ALK_LG_CATEGORY), $strNewLocale);
    @setlocale (LC_TIME, $strNewLocale.".utf8"); // nécessaire pour les dates smarty
    
    if( $bRes === false ) {
      return false;
    }
  
    @bindtextdomain(ALK_LG_DOMAIN, ALK_ALKANET_ROOT_PATH."locales");
    @textdomain(ALK_LG_DOMAIN);
    @bind_textdomain_codeset(ALK_LG_DOMAIN, ALK_HTML_ENCODING);
    return true;
  }

  /**
   * Fixe la locale par défaut pour envoyer les mails
   * - Si le mail est destiné à un seul destinataire, ou à un ensemble de destinataires de même langue,
   *   fournir la locale souhaitée
   * - sinon la locale utilisée est celle sélectionnée par défaut dans initLocale();
   *
   * Retourne true si ok, false sinon
   * @param strUserDestLg ="" par défaut, =locale de l'utilisateur destinataire si unique
   * @return boolean
   */
  public static function setMailAlkLocale($strUserDestLg="")
  {
    $cont_id = self::$cont_id;
    $strMailLocale = ( $strUserDestLg!="" && in_array($strUserDestLg, self::$tabSpace[$cont_id]["tabLocales"][ALK_LENV_FRONT])
        ? $strUserDestLg
        : ( defined("ALK_LG_LOCALE_MAIL") ? ALK_LG_LOCALE_MAIL : ALK_LG_LOCALE_INTERFACE) );
  
    return self::setAlkLocale($strMailLocale);
  }  

  /**
   * Restaure la locale courante
   * Retourne true si ok, false sinon
   * @return boolean
   */
  public static function setCurrentAlkLocale()
  {
    return self::setAlkLocale(ALK_LG_LOCALE_INTERFACE);
  }

  /**
   * Alias de la fonction ngettext($strTextSingular, $strTextPlural, $iCpt)
   * @return string
   */
  public static function getAlkText($strFunction, $tabParam)
  {
    $strFront = ( self::$bFrontOffice ? ALK_LENV_FRONT : ALK_LENV_BACK );
    $cont_id = self::$cont_id;
      
    $strKey = $tabParam[0];
    
    $currentLocale = ALK_LG_LOCALE_INTERFACE;
    $usedLocale = $currentLocale."0";
    $strRes = call_user_func_array($strFunction, $tabParam);
    if( $strRes == $strKey && substr($currentLocale, 0, 5) != ALK_LG_LOCALE_DEFAUT ) {
      // le texte n'est pas traduit dans la langue sélectionnée, sélection de la locale par défaut
      $defaultLocale = ALK_LG_LOCALE_DEFAUT; //self::$tabSpace[$cont_id]["tabLocales"][$strFront][0];
      $usedLocale = $defaultLocale."1";
      $bRes = self::setAlkLocale($defaultLocale);
      if( $bRes ) {
        // traduit le texte avec la locale par défaut
        $strRes = call_user_func_array($strFunction, $tabParam);
        if( $strRes == $strKey && substr($defaultLocale, 0, 5) != ALK_LG_LOCALE_DEFAUT ) {
          // le texte n'est pas traduit dans la langue par défaut, sélection de la locale française
          $usedLocale = ALK_LG_LOCALE_DEFAUT."2";
          $bRes = self::setAlkLocale(ALK_LG_LOCALE_DEFAUT);
          if( $bRes ) {
            // traduit le texte avec la locale française
            $strRes = call_user_func_array($strFunction, $tabParam);
          } else {
            // pas de locale française
            $strRes = $strKey;
          }
        }
      } else {
        // pas de locale par défaut
        $strRes = $strKey;
      }
  
      // réinstalle la locale courante
      setAlkLocale($currentLocale);
    }
  
    return ( self::$ALK_GT_START == '' ? $strRes : $usedLocale." ".$strRes );
  }
  
  /**
   * Alias de la fonction gettext($strText)
   * @return string
   */
  public static function _t($strText)
  {
    //echo "_t($strText)<br>";
    $strRes = self::getAlkText("gettext", array($strText));
  
    return self::$ALK_GT_START.$strRes.self::$ALK_GT_END;
  }
  
  /**
   * Alias de la fonction ngettext($strTextSingular, $strTextPlural, $iCpt)
   * @return string
   */
  public static function _n($strTextSingular, $strTextPlural, $iCpt)
  {
    //echo "_n($strTextSingular)<br>";
    $strRes = self::getAlkText("ngettext", array($strTextSingular, $strTextPlural, $iCpt));
  
    return self::$ALK_GT_START.$strRes.self::$ALK_GT_END;
  }
  
  /**
   * Alias de la fonction sprintf(_(strText), param1, param2, ..., paramN)
   * @return string
   */
  public static function _f($strText)
  {
    // récupère les params de cette fonction
    $tabParam = func_get_args();
    // retire le premier paramètre pour ne garder que les param de sprintf
    array_shift($tabParam);
    // ajoute en premier, le résultat de la traduction de strText
    $strText = str_replace("%nl", "%s", $strText);
    array_unshift($tabParam, self::getAlkText("gettext", array($strText)));
    return self::$ALK_GT_START.@call_user_func_array("sprintf", $tabParam).self::$ALK_GT_END;
  }
  
  /**
   * Alias de la fonction sprintf(ngettext(strTextSingular, strTextPlural, iCpt), param1, param2, ..., paramN)
   * Si aucun paramètre optionnel, param1 prend automatiquement la valeur de iCpt
   * @return string
   */
  public static function _nf($strTextSingular, $strTextPlural, $iCpt)
  {
    // récupère les params de cette fonction
    $tabParam = func_get_args();
    // retire les 3 premiers paramètres pour ne garder que les param de sprintf
    $tabParam = array_splice($tabParam, 3);
    if( empty($tabParam) ) {
      // ajoute le compteur comme paramètre de sprintf
      $tabParam = array($iCpt);
    }
    // ajoute en premier, le résultat de la traduction
    $strTextSingular = str_replace("%nl", "%s", $strTextSingular);
    $strTextPlural = str_replace("%nl", "%s", $strTextPlural);
    array_unshift($tabParam, self::getAlkText("ngettext", array($strTextSingular, $strTextPlural, $iCpt)));
    return self::$ALK_GT_START.@call_user_func_array("sprintf", $tabParam).self::$ALK_GT_END;
  }  

  /**
   * Initialise le paramètre de locale : category
   */
  protected static function initLgCategory()
  {
    if( !defined("ALK_LG_CATEGORY") ) {
      if (isset($_SERVER['SERVER_SOFTWARE'])){
        if( preg_match("/Win32/", $_SERVER['SERVER_SOFTWARE']) > 0 ) {
          define("ALK_LG_CATEGORY", "LC_ALL");
        } else {
          define("ALK_LG_CATEGORY", "LC_MESSAGES");
        }
      } else {
        define("ALK_LG_CATEGORY", "LC_MESSAGES");
      }
    }
  }

  /**
   * Accesseur sur le type d'interface
   * @return boolean
   */
  public static function isFrontOffice()
  {
    return self::$bFrontOffice;
  }

  /**
   * Traduit les variables déclarées dans les fichiers de configuration 
   */
  protected static function translateConf()
  {
    if( !self::$bInit ) return;
    
    if( isset($GLOBALS["tabPJDetails"]) && is_array($GLOBALS["tabPJDetails"]) && !empty($GLOBALS["tabPJDetails"]) && !self::$bTranslatePj ) {
      if( false ) {
        // force l'indexation
        _t("Date");
        _t("Titre");
        _t("Copyright");
        _t("Description");
        _t("Mots-clés");
        _t("Langue");
      }
      $nbDetail = count($GLOBALS["tabPJDetails"]);
      for($i=0; $i<$nbDetail; $i++) {
        if( isset($GLOBALS["tabPJDetails"][$i]["label"]) ) {
          $GLOBALS["tabPJDetails"][$i]["label"] = _t($GLOBALS["tabPJDetails"][$i]["label"]);
        }
      }
      self::$bTranslatePj = true;
    }
  
    $cont_id = self::$cont_id;
    if( isset(self::$tabSpace[$cont_id]["tabStrLocales"]) ) {
      if( false ) {
        _t("anglais - Grande Bretagne");
        _t("français - France");
        _t("espagnol - Espagne");
        _t("portugais - Portugal");
        _t("allemand - Allemagne");
        _t("italien - Italie");
        _t("anglais - Etats-Unis");
      }
      foreach(self::$tabSpace[$cont_id]["tabStrLocales"] as $strLocale => $strLabel) {
        self::$tabSpace[$cont_id]["tabStrLocales"][$strLocale] = _t($strLabel);
      }
    }
  }
  
}

?>