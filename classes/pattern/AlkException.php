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
 * @package Alkanet_classes_pattern
 * @class AlkException
 * 
 * @brief Classe d'exception alkanet qui hérite de la classe ErrorException de php
 * pour y ajouter les informations de catégorie, classe et méthode
 */
class AlkException extends ErrorException
{
  protected $category  = "";
  protected $class     = "";
  protected $method    = "";
  protected $mark      = false;
  protected $screenLog = false;
  
  /**
   * 
   * @param string    $message     message d'erreur
   * @param string    $class       ==__CLASS__ par défaut, ou le nom de la classe ou se situe l'exception rencontrée, = chaine vide pour signaler une fonction
   * @param string    $method      ==__METHOD__ par défaut, ou le nom de la méthode (si $class renseigné) ou nom de fonction sinon
   * @param int       $code        =0 par défaut, numéro du code d'exception sinon
   * @param string    $severity    niveau d'erreur, =E_USER_ERROR par défaut
   * @param int       $filename    =__FILE__ par défaut, ou nom du fichier où se situe l'exception rencontrée
   * @param int       $line        =__LINE__ par défaut, ou le numéro de ligne dans le fichier où se situe l'exception rencontrée
   * @param string    $category    catégorie d'exception, utilisé pour classer les logs dans des fichiers différents
   * @param Exception $previous    =null par défaut, exception précédente sinon 
   */
  public function __construct($message, $class=__CLASS__, $method=__METHOD__, $code=0, $severity=E_USER_ERROR, 
                              $filename=__FILE__, $line=__LINE__, $category="Alkanet", Exception $previous=null)
  {
    parent::__construct($message, $code, $severity, $filename, $line, $previous);
    $this->category  = $category;
    $this->class     = $class;
    $this->method    = $method;
    $this->mark      = false;
    $this->screenLog = false;
  }

  /**
   * Marque l'exception afin de ne pas boucler sur la capture d'exception
   */
  public function mark()
  {
    $this->mark = true;
  }

  /**
   * Marque l'exception afin de ne pas boucler sur la capture d'exception
   */
  public function unmark()
  {
    $this->mark = false;
  }
  
  /**
   * Retourne vrai si l'exception a déjà été marquée, faux sinon
   * @return bool
   */
  public function isMark()
  {
    return $this->mark;
  }
  
  /**
   * Fixe le mode de sortie pour la méthode magique __toString() :
   * - true  : format de log pour écran 
   * - false : format de log pour fichier
   * @param bool $screenLog  =true pour mode écran, =false pour mode fichier
   */
  public function setScreenLog($screenLog=true)
  {
    $this->screenLog = $screenLog;
  }
 
  /**
   * Retourne le nom de la catégorie où l'exception a été levée
   * @return string
   */
  public function getCategory()
  {
    return $this->category;
  }

  /**
   * Retourne le nom de la classe où l'exception a été levée
   * @return string
   */
  public function getClass()
  {
    return $this->class;
  }

  /**
   * Retourne le nom de la méthode ou fonction où l'exception a été levée
   * @return string
   */
  public function getMethod()
  {
    return $this->method;
  }
  
  /**
   * Retourne le texte correspondant à la sévérité de l'erreur
   */
  public function getSeverityText()
  {
    $errorText = "Error";
    $severity = $this->getSeverity();
    switch( $severity ) {
      case 0 : 
        $errorText = "Debug information"; 
        break;
        
      case E_NOTICE :
      case E_USER_NOTICE :
        $errorText = "Notice"; 
        break;
       
      case E_WARNING :
      case E_CORE_WARNING :
      case E_USER_WARNING : 
        $errorText = "Warning"; 
        break;
    }
    return $errorText;
  }
  
  /**
   * Méthode magique d'édition de la classe pour retourner une chaine au format 
   * d'écrire de log dans un fichier
   * @return string
   */
  public function __toString()
  {
    $strClass = $this->getClass();
    $strMethod = $this->getMethod();
    $strMessage = $this->getMessage();
    $nbMsgLine = substr_count("\n", $strMessage);
    $rc = "\n";
    
    $tabBackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $strBacktrace = print_r($tabBackTrace, true);
    
    $strException = 
      ( $this->screenLog
        ? '<div class="alert alert-'.( $this->getCode() > 0 || $this->getSeverity() > 0 ? 'danger' : 'info' ).'" role="alert">'.
          '<strong>'.$this->getSeverityText().' :</strong>'.$rc.
          '<ul>'.
          '<li>Catégory : '.$this->getCategory().'</li>'.$rc.
          '<li>Duration : #duration#'.'</li>'.$rc.
          '<li>Error code : internal = '.$this->getCode().', php = '.$this->getSeverity().'</li>'.$rc.
          '<li>Filename : '.$this->getFile().' on line '.$this->getLine().'</li>'.$rc.
          ( $strClass!="" 
            ? '<li>Method : '.$strClass.'::'.$strMethod 
            : '<li>Function : '.$strMethod ).
          '</li>'.$rc.
          '<li>Message : '.$strMessage.'</li>'.$rc.
          '<li>Pile d\exécution : '.
          $strBacktrace.
          '</li>'.$rc.
          '</ul></div>'.$rc

        : date("c")."\t".
          $this->getCategory()."\t".
          "#duration#\t".
          $this->getCode()."\t".
          $this->getSeverity()."\t".
          $this->getFile()."\t".
          $this->getLine()."\t".
          ( $strClass!="" ? $strClass."::".$strMethod : $strMethod ).
          ( $nbMsgLine > 1 
            ? "\n".$strMessage."\n#".str_repeat("-", 79)
            : "\t".$strMessage ).
          "\n" );
    return $strException;
  }
}