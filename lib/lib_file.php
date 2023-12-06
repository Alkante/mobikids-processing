<?php
/*licence/ 

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Library
Librairie js et php globale à Alkanet.
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
 * @file lib_file.php
 * @package Alkanet_Library
 * @brief Ensemble des fonctions sur les traitements de fichiers
 */

/**
 * Stocke le contenu d'un tableau dans un fichier
 * @param tabContent    tableau
 * @param strFile       emplacement du fichier
 * @param mode          mode d'ouverture du fichier (correspondant au mode de la fonction fopen()), "w" par défaut (écriture avec écrasement du contenu existant)
 * @param sep           séparateur entre chaque entrée du tableau
 * @param strEncoding   encodage de sortie du fichier, "ISO-8859-1" par défaut
 * @return 1 : OK
 *        -1 : mode erroné
 *        -2 : erreur d'ouverture du fichier
 *        -3 : erreur d'écriture dans le fichier
 *        -4 : erreur de fermeture du fichier 
 */
function writeTabInFile($tabContent, $strFile, $mode="w", $sep="\n", $strEncoding="ISO-8859-1")
{
  // vérification du mode
  $tabModeAccepted = array("w", "w+", "a", "a+", "x+", "c", "c+");
  if ( !in_array($mode, $tabModeAccepted) ) {
    return -1;
  }
  
  // ouverture du fichier
  $file = fopen($strFile, $mode);
  if ( $file === FALSE ) {
    return -2;
  }
  
  // analyse et encode chaque entrée du tableau
  // l'ajout du "a" permet de palier un bug de la fonction mb_detect_encoding(), cf. : http://de2.php.net/manual/de/function.mb-detect-encoding.php#55228
  for ( $i=0; $i<count($tabContent); $i++ ) {
    $tabContent[$i] = mb_convert_encoding($tabContent[$i], $strEncoding, mb_detect_encoding($tabContent[$i]."a", "UTF-8, ISO-8859-1"));
  }
  
  // écriture dans le fichier
  $strContent = implode($sep, $tabContent).$sep;
  if ( fwrite($file, $strContent) === FALSE ) {
    return -3;
  }
  
  // fermeture du fichier
  if ( fclose($file) === FALSE ) {
    return -4;
  }
  
  return 1;
}

/**
 * Spécifie le header de retour 
 * puis affiche sur la sortie standard, le résultat de la foncton json_encode sur l'objet passé en paramètre
 * @param oObject  objet à transcrire en json  
 */
function writeJsonEncode($oObject)
{
  header($_SERVER["SERVER_PROTOCOL"].' 200 Ok');
  header('content-type', 'application/json');
  header("Cache-Control: no-cache");
  echo json_encode($oObject);
}

/**
 * @brief Renvoie le code HTML d'une page n'exécutant que le code du onload (placer le closeWindow() sur strJsOnUnload)
 * @param strJsOnLoad    Code javascript à exécuter au chargement de la page
 * @param strJsOnUnload  Code javascript à exécuter au déchargement de la page
 * @param tabScriptJs    Tableau des fichiers de scripts javascript à inclure dans la page
 * @return string html
 */
function getBodyOnLoadExec($strJsOnLoad, $strJsOnUnload="", $tabScriptJs=array())
{
  header("Cache-Control: no-cache");
  $strHtml = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">".
    "<html>".
    "<head>".
    "<title></title>".
    "<meta http-equiv=\"content-type\" content=\"text/html; charset=".ALK_HTML_ENCODING."\" />".
    // constante ALK_B_POPUP_JQUERY à déclarer dans le app_conf.php
    ( defined("ALK_B_POPUP_JQUERY") && ALK_B_POPUP_JQUERY==true 
      ? "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."jquery/jquery-1.4.2.min.js'></script>".
        "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."jquery/jquery-ui-1.8.2.custom.min.js'></script>".
        ( defined("ALK_LG_JSON") ? "<script type='text/javascript' src='".ALK_LG_JSON."'></script>" : "" ).
        "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."lib_js.js'></script>".
        ( ALK_NAV != ALK_NAV_IE6
          ? "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."lib_js_jquery.js'></script>"
            .( defined("ALK_THEME") && ( 
              ( defined("ALK_THEME_BS_ALKANET") && ALK_THEME == ALK_THEME_BS_ALKANET ) ||
              ( defined("ALK_THEME_BS_SMARTADMIN") && ALK_THEME == ALK_THEME_BS_SMARTADMIN )
            ) 
              ? "<link rel='stylesheet' href='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."jquerybs.alkModal.css'>"
               ."<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."jquerybs.alkModal.js'></script>"
              : "")                  
          : "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."lib_js_jquery_ie6.js'></script>" )
      : "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."lib_js.js'></script>" ).
    "<script type='text/javascript' src='".ALK_ALKANET_ROOT_URL.ALK_ROOT_LIB."lib_js.php'></script>";
  foreach ($tabScriptJs as $strFileScriptJs){
    $strHtml .= "<script type='text/javascript' src='".$strFileScriptJs."'></script>";
  }
  $strHtml .=
    "<script type='text/javascript'>".
    //" function onWindLoad() { try { ".$strJsOnLoad."; ".( $strJsOnUnload!="" ? "window.location.href = 'about:blank';" : "")." } catch(err) { console.log(err); } }".
    //" function onWindUnload() { ".$strJsOnUnload." }".
    //" (function() { onWindLoad(); try{ ".( $strJsOnUnload != "" ? "top.".$strJsOnUnload.";" : "" )."}catch(err){console.log(err);}  })(); ".
    " jQuery(document).ready(function(){".
    ( $strJsOnLoad != ""   ? $strJsOnLoad.";"   : "" ).
    ( $strJsOnUnload != "" ? $strJsOnUnload.";" : "" ).
    " });".
    "</script>".
    "</head><body></body></html>";
  
  return $strHtml;
}

/**
 * Fonction a appeler
 */
function writeHTTP404()
{
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
  echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
    <html><head>
    <title>404 Not Found</title>
    </head><body>
    <h1>Not Found</h1>
    <p>The requested URL was not found on this server.</p>
    </body></html>";
  exit(); 
}

/**
 * @TODO Remplacer str_replace et ereg par leurs homologues mb_
 * @brief Supprime le fichier dont le chemin est passé en paramètre
 *        Retourne le nombre de fichier supprimé
 * @param strPathFileName  chemin et nom du fichier à supprimer
 * @return int
 */
function supprFichier($strPathFileName) { return ( delFile($strPathFileName) > 0 ); }
function delFile($strPathFileName)
{
  if(is_dir($strPathFileName) || $strPathFileName == '' || $strPathFileName == ALK_ALKANET_ROOT_PATH.ALK_ROOT_UPLOAD || $strPathFileName == ALK_ALKANET_ROOT_PATH  ) return 0;
  $iDel = 0;
  $files = glob($strPathFileName);
  foreach($files as $file){
    if ( $file=="/" || $file=="." || $file=="..") continue;
    if ( is_dir($file) ){
      $iDel += delFile($file."/*");
      @rmdir($file);
    } else {
      $iDel += ( @unlink($file) ? 1 : 0 );
    }
  }
  return $iDel;
}

/**
 * @brief Copie le fichier source vers la destination
 *        si la destination existe déjà, elle est écrasée
 * @param strPathFileNameSrc   chemin d'accès au fichier à copier (source)
 * @param strPathFileNameDest  chemin d'accès au fichier copié (destination)
 * @return Booleen : Retourne vrai si ok, faux sinon
 */
function copieFichier($strPathFileNameSrc, $strPathFileNameDest) { return copyFile($strPathFileNameSrc, $strPathFileNameDest); }
function copyFile($strPathFileNameSrc, $strPathFileNameDest)
{
	$bCopy = false;
  if( file_exists($strPathFileNameSrc) && is_file($strPathFileNameSrc) )
		$bCopy = @copy($strPathFileNameSrc, $strPathFileNameDest);
	return $bCopy;
}

/**
 * @brief vérifie le nommage du fichier :
 *        - n'accepte que les caractères [a-z][A-Z][0-9]_.-%
 * @param strFileName nom du fichier à traiter
 * @param bToLower    force le nom en minuscule
 * @returns Retourne le nom du fichier correcte
 */
function verifyFileName($strFileName, $bToLower=false)
{
  // passage en minuscule
  $strTmp = ( $bToLower ? mb_strtolower($strFileName) : $strFileName );

  // remplace les caractères accentués courant par leur équivalent non accentué
  // remplace l'espace par souligné
  $tabChar = array(" -éèêëäàâüùûîïôöç", "__eeeeaaauuuiiooc");
  for($i=0; $i<mb_strlen($tabChar[0]); $i++) {
    $strTmp = mb_ereg_replace(mb_substr($tabChar[0], $i, 1), mb_substr($tabChar[1], $i, 1), $strTmp);
  }

  // supprime tous les caractères n'étant pas : lettre, chiffre, point, tiré et souligné et %
	$strTmp = mb_ereg_replace("([^_a-zA-Z0-9\%\-\.])", "", $strTmp);
	$strTmp = mb_ereg_replace("\\\\", "", $strTmp);
	return $strTmp;
}

/**
 * @brief vérifie le nommage de la page :
 *        - n'accepte que les caractères [a-z][A-Z][0-9]_
 * @param strPageName nom du fichier à traiter
 * @returns Retourne le nom du fichier correcte
 */
function verifyPageName($strPageName)
{
  // passage en minuscule
  $strTmp = mb_strtolower(trim($strPageName));

  // remplace les caractères accentués courant par leur équivalent non accentué
  // remplace l'espace par souligné
  $tabChar = array(" -éèêëäàâüùûîïôöç", "__eeeeaaauuuiiooc");
  for($i=0; $i<mb_strlen($tabChar[0]); $i++) {
    $strTmp = mb_ereg_replace(mb_substr($tabChar[0], $i, 1), mb_substr($tabChar[1], $i, 1), $strTmp);
  }

  // supprime tous les caractères n'étant pas : lettre, chiffre et souligné
  $strTmp = mb_ereg_replace("([^_a-zA-Z0-9])", "", $strTmp);
  $strTmp = mb_ereg_replace("\\\\", "", $strTmp);
  return $strTmp;
}

/**
 * @brief Effectue l'upload d'un fichier
 * @param strPostVar    nom de la variable http postée
 * @param strPrefixe    préfixe ajouté devant le nom du fichier
 * @param strPathUpload  chemin d'upload (à partir de la racine)
 * @param iDel           =1 pour supprimer l'ancien fichier, =0 par défaut
 * @param strOldFileName nom de l'ancien fichier à supprimer si renseigné et si il existe
 * @return 
 *   -  Si upload uniquement, Retourne le nom du fichier
 *   -  Si suppr uniquement, Retourne chaine vide
 *   -  Si suppr + upload, Retourne le nom du fichier
 *   -  Si rien, Retourne false
 */
function doUpload($strPostVar, $strPrefixe, $strPathUpload, $iDel=0, $strOldFileName="")
{
	$bDel = false;
	$bUpload = false;
  $nbUpload = 0;
	$tabFileName = array("");

  $tabFile = AlkRequest::_FILES($strPostVar);
  $iDel = AlkRequest::_REQUESTint("del_".$strPostVar, $iDel);
  $strOldFileName = AlkRequest::_REQUEST("old_".$strPostVar, $strOldFileName);
	if( !empty($tabFile) )	{
		if( !is_array($tabFile["tmp_name"]) ) {
			$tabFile["name"]     = array(0 => $tabFile["name"]);
      $tabFile["tmp_name"] = array(0 => $tabFile["tmp_name"]);
      $tabFile["size"]     = array(0 => $tabFile["size"]);
      $tabFile["error"]    = array(0 => $tabFile["error"]);
      $tabFile["type"]     = array(0 => $tabFile["type"]);
		} else {
			// pas de suppression en mode upload multiple
			$iDel=0; 
      $strOldFileName="";
		}
	  $nbPj = count($tabFile["name"]);
    for($i=0; $i<$nbPj; $i++) {
      $bUpload = is_uploaded_file($tabFile["tmp_name"][$i]);
      // pas possible d'uploader des fichiers js, php et phtml
      $bUpload = ( $bUpload && 
                   strtolower(substr($tabFile["name"][$i], -3)) != ".js" &&
                   strtolower(substr($tabFile["name"][$i], -4)) != ".jsp" &&
                   strtolower(substr($tabFile["name"][$i], -4)) != ".inc" &&
                   strtolower(substr($tabFile["name"][$i], -4)) != ".php" &&
                   strtolower(substr($tabFile["name"][$i], -6)) != ".phtml"
                   ? true
                   : false );

      //if( $bDel== false && $iDel==1 && $strOldFileName!="" || $bUpload==true ) {
      if( $strOldFileName!="" && (($bDel== false && $iDel==1) || $bUpload==true) ) {
        delFile(ALK_ALKANET_ROOT_PATH.$strPathUpload.$strOldFileName);
        $bDel = true;
      }

    	if( $bUpload == true ) {
        $tabFileName[$i] = $strPrefixe.verifyFileName($tabFile["name"][$i]);
        $pathFile = ALK_ALKANET_ROOT_PATH.$strPathUpload.$tabFileName[$i];
        move_uploaded_file($tabFile["tmp_name"][$i], $pathFile);
        $nbUpload++;
      } else {
      	if( !$bDel ) {
          $tabFileName[$i] = false;
        }
      }
    }
  }
  
  if( $bDel== false && $iDel==1 && $strOldFileName!="" ) {
    delFile(ALK_ALKANET_ROOT_PATH.$strPathUpload.$strOldFileName);
    $bDel = true;
  }

	if( $nbUpload>0 || $bDel==true ) {
		return ( count($tabFileName)== 1 
             ? $tabFileName[0]  
             : $tabFileName );
  }
	return false;
}

/**
 * @brief Créé le répertoire dont le chemin est passé en paramètre.
 * @param strPathDir Chemin d'accès au répertoire à créer
 * @return booleen. Retourne vrai si ok, faux sinon
 */
function creeRepertoire($strPathDir) { return createDir($strPathDir); }
function createDir($strPathDir)
{
  $bOk = false;
  if( is_dir($strPathDir)==false ){
    $bOk = @mkdir($strPathDir) ;
    @chmod($strPathDir, 0770);
  }
  return $bOk;
}

/**
 * @brief Vérifie si un répertoire est vide
 * @param $strPathDir Chemin d'accès au répertoire à tester
 * @return booleen. Retourne vrai si ok, faux sinon
 */
function isDirEmpty($strPathDir)
{
  $dir     = opendir($strPathDir);
  $isEmpty = true;
  while(($entry = readdir($dir)) !== false) {
    if($entry !== '.' && $entry !== '..') {
      $isEmpty = false;
      break;
    }
  }

  closedir($dir);
  return $isEmpty;
}

/**
 * @brief Retourne dans un tableau, la liste des sous-répertoires de strPath
 * @param strPath   chemin physique à lire
 * @param bRec      false par défaut, vrai pour un parcours recursif
 * @param bAddPath  vrai pour ajouter le chemin complet pour la valeur, la clé ne contenant que le nom du fichier, =false par défaut
 * @return array
 */
function getTabDir($strPath, $bRec=false, $bAddPathValue=false, $bGetCount=false)
{
  if( $strPath!="" && $strPath[strlen($strPath)-1] != '/' )
    $strPath .= '/';

  if( !file_exists($strPath) )
    return array();

  $tabRes = array();
  $hDir = opendir($strPath);
  while( $strFile = readdir($hDir) ) {
    if( $strFile=='.' || $strFile=='..' )
      continue;
    if( @is_dir($strPath.$strFile) ) {
      $tabRes[$strFile]["dir"] =  ($bAddPathValue ? $strPath : "" ).$strFile;
      if( $bRec ) {
        $tabRes[$strFile]["subDir"] = getTabDir($strPath.$strFile, $bRec, $bAddPathValue);
      } elseif( !$bGetCount ) {
        $tabSubDir = getTabDir($strPath.$strFile, false, false, true);
        $tabRes[$strFile]["bSubDir"] = ( count($tabSubDir)> 0 );
      }
      
    }
  }
  closedir($hDir);
 
  return $tabRes;
}

/**
 * @brief Retourne dans un tableau, la liste des fichiers ayant l'une des extensions
 *        présentes dans la chaine strExt
 * @param strPath   chemin physique à lire
 * @param tabExt    tableau contenant les extensions de filtre, tableau vide par défaut
 * @param bRec      false par défaut, vrai pour un parcours recursif
 * @param tabPrefix tableau contenant les préfixes de filtre, tableau vide par défaut
 * @param bAddPath  vrai pour ajouter le chemin complet pour la valeur, la clé ne contenant que le nom du fichier, =false par défaut
 * @return array
 */
function getTabFilesByDir($strPath, $tabExt=array(), $bRec=false, $tabPrefix=array(), $bAddPathValue=false, $tabSuffix=array())
{
  if( $strPath!="" && $strPath[strlen($strPath)-1] != '/' )
    $strPath .= '/';

  if( !file_exists($strPath) )
    return array();

  $tabRes = array();
  $hDir = opendir($strPath);
  while( $strFile = readdir($hDir) ) {
    if( $strFile=='.' || $strFile=='..' )
      continue;
    if( $bRec && @is_dir($strPath.$strFile) ) {
      $tabRes = array_merge($tabRes, getTabFilesByDir($strPath.$strFile, $tabExt, $bRec, $tabPrefix, $bAddPathValue));
    } elseif( @is_file($strPath.$strFile) ) {
      $bResExt  = empty($tabExt);
      $bResPref = empty($tabPrefix);
      $bResSuf  = empty($tabSuffix);
      if( !empty($tabExt) || !empty($tabPrefix) || !empty($tabSuffix) ) {
        foreach($tabExt as $strExt) {
          $bResExt = $bResExt || (mb_substr($strFile, -mb_strlen($strExt)-1) == ".".$strExt );
        }
        foreach($tabPrefix as $strPrefix) {
          $bResPref = $bResPref || (mb_substr($strFile, 0, mb_strlen($strPrefix)) == $strPrefix );
        }
        foreach($tabSuffix as $strSuffix) {
          $bResSuf = $bResSuf || (mb_substr($strFile, -mb_strlen($strSuffix)) == $strSuffix );
        }
        
      }
      if( $bResExt && $bResPref && $bResSuf )
        $tabRes[$strFile] =  ($bAddPathValue ? $strPath : "" ).$strFile;
    }
  }
  closedir($hDir);
 
  return $tabRes;
}

/**
 * @brief Supprime le répertoire dont le chemin est passé en paramètre.
 * @param strPathDir chemin d'accès au répertoire à supprimer
 * @return booleen. Retourne vrai si ok, faux sinon
 */
function supprRepertoire($strPathDir) { return delDir($strPathDir); }
function delDir($strPathDir)
{
  $bDel = false;
  if( file_exists($strPathDir) && is_dir($strPathDir) )
    $bDel = @rmdir($strPathDir) ;
  return $bDel;
}

/**
 * Retourne le type mime d'un fichier existant
 * Retourne text/plain par défaut si non trouvé.
 * @param strPathFileName chemin complet et non du fichier à analyser
 * @return string
 */
function GetTypeMime($strPathFileName)
{
  $strRes = "";
  /*if (file_exists($strPathFileName) && is_file($strPathFileName)) {
    if( class_exists("finfo") ) {
      $finfo = new finfo(FILEINFO_MIME, "/etc/magic");
      if( !is_null($finfo) && is_object($finfo) && file_exists($strPathFileName) && is_file($strPathFileName) ) {
        $strRes = @$finfo->file($strPathFileName);
      }
    }
    if( $strRes == "" && function_exists("mime_content_type") ) {
      $strRes = mime_content_type($strPathFileName);
    } 
  }*/
  if( $strRes == "" ) {
    $tabMimeTypes = array('txt' => 'text/plain',
                          'htm' => 'text/html',
                          'html'=> 'text/html',
                          'ics' => 'text/calendar', 
                          'php' => 'text/html',
                          'css' => 'text/css',
                          'js'  => 'application/javascript',
                          'json'=> 'application/json',
                          'xml' => 'application/xml',
                          'swf' => 'application/x-shockwave-flash',
                          'flv' => 'video/x-flv',
              
                          // images
                          'png' => 'image/png',
                          'jpe' => 'image/jpeg',
                          'jpeg'=> 'image/jpeg',
                          'jpg' => 'image/jpeg',
                          'gif' => 'image/gif',
                          'bmp' => 'image/bmp',
                          'ico' => 'image/vnd.microsoft.icon',
                          'tiff'=> 'image/tiff',
                          'tif' => 'image/tiff',
                          'svg' => 'image/svg+xml',
                          'svgz'=> 'image/svg+xml',
              
                          // archives
                          'zip' => 'application/zip',
                          'rar' => 'application/x-rar-compressed',
                          'exe' => 'application/x-msdownload',
                          'msi' => 'application/x-msdownload',
                          'cab' => 'application/vnd.ms-cab-compressed',
              
                          // audio/video
                          'mp3' => 'audio/mpeg',
                          'qt' => 'video/quicktime',
                          'mov' => 'video/quicktime',
              
                          // adobe
                          'pdf' => 'application/pdf',
                          'psd' => 'image/vnd.adobe.photoshop',
                          'ai' => 'application/postscript',
                          'eps' => 'application/postscript',
                          'ps' => 'application/postscript',
              
                          // ms office
                          'doc' => 'application/msword',
                          'docx' => 'application/msword',
                          'docm' => 'application/msword',
                          'rtf' => 'application/rtf',
                          'xls' => 'application/vnd.ms-excel',
                          'xlsx' => 'application/vnd.ms-excel',
                          'xlsm' => 'application/vnd.ms-excel',
                          'xlsb' => 'application/vnd.ms-excel',
                          'ppt' => 'application/vnd.ms-powerpoint',
                          'pptx' => 'application/vnd.ms-powerpoint',
                          'pptm' => 'application/vnd.ms-powerpoint',
              
                          // open office
                          'odt' => 'application/vnd.oasis.opendocument.text',
                          'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                      );

    $strSplitted = explode('.', $strPathFileName);
    $strExt = strtolower(array_pop($strSplitted));
    if( array_key_exists($strExt, $tabMimeTypes) ) {
      $strRes = $tabMimeTypes[$strExt];
    } else {
      $strRes = 'application/octet-stream';
    }  
  }
  /*if( file_exists($strPathFileName) && is_file($strPathFileName) ) {
    ob_start();
    system("file -i -b ". $strPathFileName);
    $type = ob_get_clean();
    $parts = explode(';', $type);
    $parts = explode(' ', $parts[0]);

    $strRes = trim($parts[0]);
  }*/
  return $strRes;
}

/**
 * @brief Affiche l'entete html correspondant au type du fichier
 *
 * @param strPathFileName Nom complet du fichier à téléchager
 * @param strFileName     Nom du fichier fourni à l'utilisateur qui télécharge
 * @param iSize           taille du fichier généré, =0 par défaut. calculé dynamiquement si strPathFileName!=""
 * @param strType         type mime du fichier, vide par défaut. calculé dynamiquement si strPathFileName!=""
 */
function AffHeaderFileDownload($strPathFileName, $strFileName, $iSize=0, $strType="")
{
  if( $strType=="" && $strPathFileName!="" ) { 
    $strType = GetTypeMime($strPathFileName);
  }
  if( $strPathFileName!="" && $iSize==0 ) {
    $iSize = filesize($strPathFileName);
  }
  @ob_clean();
  $bIE = ( ALK_NAV == ALK_NAV_IE8 || ALK_NAV == ALK_NAV_IE7 || ALK_NAV == ALK_NAV_IE6 );

  // tester dans le contexte préfecture
  if( $bIE ) {
    //header("Content-type: ".$strType."; charset=".ALK_EXPORT_ENCODING.";\r\n");
    header("Content-type: ".$strType."; name=\"".$strFileName."\"");
    header("Content-Transfer-Encoding: binary");
    if( $iSize > 0 ) header("Content-Length: ".$iSize);
    header("Content-Disposition: attachment; filename=\"".$strFileName."\"");
    header("Expires: ".gmdate("D, d M Y H:i:s", time()-24*60*60)." GMT");
    // HTTP/1.1
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    // HTTP/1.0
    header("Pragma: public");
  } else {
    //header("Content-type: ".$strType."; charset=".ALK_EXPORT_ENCODING.";\r\n");
    header("Content-Type: application/force-download; name=\"".$strFileName."\"");
    header("Content-Transfer-Encoding: binary");
    if( $iSize > 0 ) header("Content-Length: ".$iSize);
    header("Content-Disposition: attachment; filename=\"".$strFileName."\"");
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
  }
}

/**
 * @brief retourne l'identifiant du type du fichier, determiné en fonction de l'extension du fichier
 * @param fichier  Fichier évalué
 * @return int Type de fichier
 */
function getTypeFile($fichier)
{
  $iType = 0;
  $longueur = strlen($fichier);
  if( $longueur>2 ) {
    $i = strrpos($fichier,".");
    if( $i ) {
      $strExt = strtolower(substr($fichier,$i+1));
    
      switch( $strExt ) {
      case "doc": case "rtf":
      case "docx": case "docm":  $iType = 1; break;

      case "xls": case "csv":
      case "xlsx": case "xlsm":  $iType = 2; break;
      
      case "pdf":                $iType = 3; break;

      case "png": case "tif":
      case "jpg": case "gif":
      case "bmp": case "jpeg": $iType = 4; break;
        
      case "txt":              $iType = 5; break;
        
      case "htm": case "html": $iType = 6; break;

      case "zip": case "gz": 
      case "rar": case "tgz": 
      case "7z":               $iType = 7; break;

      case "svg": case "jsvg":
      case "shp": case "tab":
      case "mif": case "js":
      case "frm": case "style":$iType = 8; break;// couche SIG
      //iType = 9 : projet SIG par convention puisque pas de piece jointe de ce type

      case "ppt": case "pptx": 
      case "pptm":             $iType = 10; break;
      case "mdb":              $iType = 11; break;
      case "mp3": case "mp2":
      case "mp4": case "mpg": 
      case "mpeg":case "avi":  
      case "wma": case "wmv":
      case "midi":case "mid":
      case "rmi": case "wav":
      case "m3u": case "asf":  $iType = 12; break;
      case "ram": case "rm":   $iType = 13; break;
      case "mov":              $iType = 14; break;
      case "odt": case "sxw":  $iType = 15; break;
      case "ods": case "sxc":  $iType = 16; break;
      case "odp": case "sxi":  $iType = 17; break;
      case "crypt":            $iType = 18; break;
      }
    }
  }
  return ($iType);
}

/**
 * tronque puis retourne le nom d'un ficher si son nom est trop long
 * @param strFileName  Nom du fichier
 * @param iLg          longueur max, =40 par défaut avec extension
 * @return string
 */
function truncFileName($strFileName, $iLg=40)
{
  $strRes = $strFileName;
  if( mb_strlen($strFileName) > $iLg ) {
    $iPos = mb_strrpos($strFileName, ".");
    if( $iPos === false ) {
       $strRes = mb_substr($strFileName, 0, $iLg-5)."[...]";
    } else {
      $strExt = mb_substr($strFileName, $iPos);
      $strRes = mb_substr($strFileName, 0, $iLg-5-mb_strlen($strExt))."[...]".$strExt;
    }
  }
  return $strRes;
}

/**
 * @brief retourne le nom de l'image icone en fonction de l'extension du fichier
 *        Ancien nom : getPictoFile()
 * @param strFileName Fichier évalué
 * @param bCss        =false par défaut, =true pour retourner le suffixe css
 * @return string : nom du fichier icone 
 */
function getFileIcon($strFileName, $bCss=false)
{
  // icone par défaut
  $strIcon = "icon_doc_0.gif";
  $iFWidth = strlen($strFileName);
  if( $iFWidth > 2 ) {
    $iPos = strrpos($strFileName, ".");
    if( !($iPos===false) ) {
      $strExt = strtolower(substr($strFileName, $iPos+1));
      
      switch( $strExt ) {
      case "doc": case "rtf":
      case "docx": case "docm": $strIcon = "icon_doc_1.gif"; break;
      case "xls": case "csv":
      case "xlsx": case "xlsm": $strIcon = "icon_doc_2.gif"; break;
      case "pdf":               $strIcon = "icon_doc_3.gif"; break;
      case "png": case "tif":
      case "jpg": case "gif":
      case "bmp": case "jpeg":  $strIcon = "icon_doc_4.gif"; break;
      case "txt":               $strIcon = "icon_doc_5.gif"; break;
      case "htm": case "html":  $strIcon = "icon_doc_6.gif"; break;
      case "zip": case "gz": 
      case "rar": case "tgz": 
      case "7z":                $strIcon = "icon_doc_7.gif"; break;
      case "svg": case "jsvg": 
      case "shp": case "tab":
      case "mif": case "js":  
      case "frm": case "style": $strIcon = "icon_doc_8.gif"; break;
      case "ppt": case "pptm":
      case "pptx":              $strIcon = "icon_doc_10.gif"; break;
      case "mdb":               $strIcon = "icon_doc_11.gif"; break;
      case "mp3": case "mp2":
      case "mp4": case "mpg": 
      case "mpeg":case "avi":  
      case "wma": case "wmv":
      case "midi":case "mid":
      case "rmi": case "wav":
      case "m3u": case "asf":   $strIcon = "icon_doc_12.gif"; break;
      case "ram": case "rm":    $strIcon = "icon_doc_13.gif"; break;
      case "mov":               $strIcon = "icon_doc_14.gif"; break;
      case "odt": case "sxw":   $strIcon = "icon_doc_15.gif"; break;
      case "ods": case "sxc":   $strIcon = "icon_doc_16.gif"; break;
      case "odp": case "sxi":   $strIcon = "icon_doc_17.gif"; break;
      case "crypt":             $strIcon = "icon_doc_18.gif"; break;
      }
    }
  }
  if( $bCss ) {
    return strtolower(str_replace("_", "", substr($strIcon, 0, -4)));
  }
  return $strIcon;
}

/**
 * tente de récupérer les métadonnées avec ffmpeg dans le fichier passé en paramètre
 * Liste des métadonnées non exhaustive, compléter en cas de besoin (cf. : http://ffmpeg-php.sourceforge.net/doc/api/ffmpeg_movie.php)
 * @param strFilePath    emplacement du fichier (avec son nom)
 * @return tableau indexé par le nom de la métadonnée, la valeur est un tableau indexé par une clé "type" pour le type et "value" pour la valeur
 *         tableau vide si echec de la récupération des métadonnées
 */
function getMpegMetadata($strFilePath)
{
  $tabMetadata = array();
  
  if ( class_exists("ffmpeg_movie") ) {
    if ( $movie = @new ffmpeg_movie($strFilePath, false) ) {
      $tabMetadata["title"]         = array("type" => "text",     "value" => $movie->getTitle());
      $tabMetadata["desc"]          = array("type" => "text",     "value" => $movie->getComment());
      $tabMetadata["copyright"]     = array("type" => "text",     "value" => $movie->getCopyright());
      $tabMetadata["duration"]      = array("type" => "float",    "value" => $movie->getDuration());
      $tabMetadata["auteur"]        = array("type" => "text",     "value" => $movie->getAuthor());
      $tabMetadata["height"]        = array("type" => "int",      "value" => $movie->getFrameHeight());
      $tabMetadata["width"]         = array("type" => "int",      "value" => $movie->getFrameWidth());
      $tabMetadata["bitrate"]       = array("type" => "int",      "value" => $movie->getBitRate());
    }
  }
  
  return $tabMetadata;
}

/**
 * @brief vérifie si le fichier est du type multimédia
 * 
 * @param   fileName  nom du fichier
 * 
 * @return  true sir le l'extension du fichier en entrée correspont à une extension multimédia, false sinon
 */
function isFileMultimedia($strFileName)
{ 
  $bOk = false;
  $iFWidth = strlen($strFileName);
  if( $iFWidth > 2 ) {
    $iPos = strrpos($strFileName, ".");
    if( !($iPos===false) ) {
      $strExt = strtolower(substr($strFileName, $iPos+1));
    
      switch( $strExt ) {
      case "mp3": case "mp2":
      case "mp4": case "mpg": 
      case "mpeg":case "avi":  
      case "wma": case "wmv":
      case "midi":case "mid":
      case "rmi": case "wav":
      case "m3u": case "asf":  
      case "ram": case "rm":   
      case "mov":              $bOk=true; break;
      }
    }
  }
  return $bOk;
}

/**
 * @brief vérifie si le fichier est lisible par collab
 * 
 * @param   strFileName  nom du fichier
 * 
 * @return  true si le fichier est lisble par collab, false sinon
 */
function isFileReadable($strFileName){
  global $tabCollabFileExtensionReadable;
  
  if (is_array($tabCollabFileExtensionReadable)){
    if ( in_array(getFileExtension($strFileName), $tabCollabFileExtensionReadable) ){
      return true;
    }
  }
  return false;
}

/**
 * @brief retourne le code HTML d'un lecteur multimedia
 * 
 * @param   strNomlecteur nom du lecteur : MediaPlayer, QuickTime, RealPlayer
 * @param   fileName  nom du fichier
 * 
 * @return  string
 */  
function getHtmlRunMedia(&$oRes, $strFileName)
{
  $strHtml="";
  $strNomlecteur="";
  
  $iFWidth = strlen($strFileName);
  if( $iFWidth > 2 ) {
    $iPos = strrpos($strFileName, ".");
    if( !($iPos===false) ) {
      $strExt = strtolower(substr($strFileName, $iPos+1));
    
      switch( $strExt ) {
        case "xls": case "csv":
        case "xlsx": case "xlsm":    
        case "doc": case "rtf":
        case "docx": case "docm":  
        case "pdf":              
        case "png": case "tif":
        case "jpg": case "gif":
        case "bmp": case "jpeg": 
        case "txt":             
        case "htm": case "html": 
        case "zip":              
        case "svg": case "jsvg": 
        case "js":  case "frm":   
        case "style":           
        case "ppt": case "pptx":
        case "pptm":
        case "mdb":              break;
        case "mp3": case "mp2":
        case "mp4": case "mpg": 
        case "mpeg":case "avi":  
        case "wma": case "wmv":
        case "midi":case "mid":
        case "rmi": case "wav":
        case "m3u": case "asf": $strNomlecteur="MediaPlayer";break;
        case "ram": case "rm":  $strNomlecteur="RealPlayer";break; 
        case "mov":             $strNomlecteur="QuickTime";break; 
      }
    }
  }
  
  switch ($strNomlecteur) {
   case "MediaPlayer" : 
     if (ALK_NAV==ALK_NAV_IE){
       $strHtml=  
          "<object id='MediaPlayer' width='320' height='69'" .
          " classid='CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95'" .
          " codebase='http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715'" .
          " standby='Chargement...' type='application/x-oleobject'>" .
            " <param name='filename' value='".$strFileName."' /> " .
            " <param name='autostart' value='true' /> " .
            " <param name='animationatstart' value='false' /> " .
            " <param name='transparentatstart' value='false' /> " .
            " <param name='showcontrols' value='true'/> " .
            " <param name='showstatusbar' value='true'/> " .
            " <embed type='application/x-mplayer2' src='".$strFileName."' " .
            " name='MediaPlayer' width='320' height='79'" .
            " showcontrols='1' showdisplay='1' ></embed> ".
          "</object>";
     }
     else {
        $strHtml = 
        "<object classid='CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95' id='MediaPlayer' codebase='http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715' standby='Chargement...' type='application/x-oleobject'>" .
          "<embed type='application/x-mplayer2' src='".$strFileName."'" .
          "  pluginspage='http://www.microsoft.com/windows/mediaplayer/download/default.asp'" .
          "  showstatusbar='1'" .
          "  controltype='1'" .
          "  autostart='true'" .
          "  transparentatstart='true'" .
          "  animationatstart='true'" .
          "  filename='".$strFileName."'" .
          "  Displaysize='4'" .
          "  showcontrols='true'" .
          "  width='320' height='69' >" .
          "</embed>" .
       "</object>";
     }
   break;
   case "QuickTime" : 
     $strHtml=  
        "<object classid='CLSID:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B' width='320' height='240' codebase='http://www.apple.com/qtactivex/qtplugin.cab'>" .
          "<param name='controller' value='true' />" .
          "<param name='autoplay' value='true' />" .
          "<param name='src' value='".$strFileName."' />" .
          "<embed width='320' height='240' src='".$strFileName."'" .
          " type='video/quicktime' controller='true' autoplay='true'>" .
          "</embed>" .
        "</object>";
   break;
   case "RealPlayer" :
     $strHtml=  
        "<object classid='CLSID:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA' width='358' height='258'>" .
          "<param name='console' value='Clip1' />" .
          "<param name='controls' value='ImageWindow' />" .
          "<param name='autotstart' value='true' />" .
          "<param name='src' value='".$strFileName."' />" .
          "<embed width='358' height='258' src='".$strFileName."'" .
          " type='audio/x-pn-realaudio-plugin' controls='ImageWindow'" .
          " console='Clip1' autostart='true'>" .
          "</embed>" .
        "</object>" .
        "<br />" .
        "<object classid='CLSID:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA' width='358' height='30'>" .
          "<param name='console' value='Clip1' />" .
          "<param name='controls' value='controlpanel' />" .
          "<param name='autotstart' value='true' />" .
          "<param name='src' value='".$strFileName."' />" .
          "<embed width='358' height='30' src='".$strFileName."'" .
          " type='audio/x-pn-realaudio-plugin' controls='controlpanel'" .
          " console='Clip1' autostart='true'>" .
          "</embed>" .
        "</object>";
    break;
  }
  return $strHtml;
}
  
/**
 * @brief Retourne la taille du fichier à l'échelle la plus appropriée
 *        <1024   : , xx octets
 *        <1024^2 : , xx.x Ko
 *        <1024^3 : , xx.x Mo
 *
 * @param iSize Taille du fichier en octet
 * @return retourne une chaine
 */
function getFileSize($iSize, $bSep=true)
{
  $iKo = 1024;
  $iMo = 1024*1024;
  $strRes = ($bSep == true ? ", " : "");
  if( $iSize < $iKo )
    $strRes .= $iSize."&nbsp;octets";
  elseif( $iSize < $iMo) {
    $iTmp = $iSize*10;
    $iTmp = round($iTmp/$iKo);
    $iTmp = $iTmp/10;
    $strRes .= $iTmp."&nbsp;Ko";
  } else {
    $iTmp = $iSize*10;
    $iTmp = round($iTmp/$iMo);
    $iTmp = $iTmp/10;
    $strRes .= $iTmp."&nbsp;Mo";
  }
  
  return $strRes;
}

/**
 * @brief Retourne la durée du fichier à l'échelle la plus appropriée
 *        <60   : , xx sec
 *        <60^2 : , xx min xx sec
 *        <60^3 : , xx h xx min xx.xx sec
 *
 * @param iSec durée du fichier en seconde
 * @return retourne une chaine
 */
function getFileDuration($iSec, $bSep=true)
{
  $strRes = ($bSep == true ? ", " : "");
  
  $iMin = 0;
  $iH = 0;
  if ( $iSec >= 60 ) {
    $iMin = (int)($iSec / 60);
    $iSec = ($iSec % 60);
    if ( $iMin >= 60 ) {
      $iH = (int)($iMin / 60);
      $iMin = ($iMin % 60);
    }
  }
  $strSec = round($iSec, 2);
  
  $strRes = ( $iH ? "&nbsp;".$iH."&nbsp;h" : "" ).( $iMin ? "&nbsp;".$iMin."&nbsp;min" : "" ).( $iSec ? "&nbsp;".$strSec."&nbsp;sec" : "" );
  $strRes = substr($strRes, 6); // supprime le premier espace
  
  return $strRes;
}

/**
 * @brief Retourne le débit du fichier à l'échelle la plus appropriée
 *        <1024   : , xx bit/s
 *        <1024^2 : , xx.x Kbit/s
 *        <1024^3 : , xx.x Mbit/s
 *
 * @param iSize Taille du fichier en octet
 * @return retourne une chaine
 */
function getFileBitrate($iBitrate, $iKo=1024, $bSep=true)
{
  $iMo = $iKo*$iKo;
  $strRes = ($bSep == true ? ", " : "");
  if( $iBitrate < $iKo )
    $strRes .= $iBitrate."&nbsp;bit/s";
  elseif( $iBitrate < $iMo) {
    $iTmp = $iBitrate*10;
    $iTmp = round($iTmp/$iKo);
    $iTmp = $iTmp/10;
    $strRes .= $iTmp."&nbsp;Kbit/s";
  } else {
    $iTmp = $iBitrate*10;
    $iTmp = round($iTmp/$iMo);
    $iTmp = $iTmp/10;
    $strRes .= $iTmp."&nbsp;Mbit/s";
  }
  
  return $strRes;
}

/**
 * Retourne l'extension d'un fichier (sans répertoire et basée sur 1er . rencontré)
 * @param strFile     Nom du fichier (sans le chemin)
 * @param bWithPoint  retourne l'extension avec le point si vrai (default false)
 * @param iCasse       Change la casse de caract. : -1=lowercase; 1=uppercase; 0=nochange (-1 default) 
 */
function getFileExtension($strFile, $bWithPoint=false, $iCasse=-1)
{
  $tabMatch = array();
  preg_match("!^(.+)\.(.+)$!usi", $strFile, $tabMatch);
  if ( count($tabMatch)<3 ) return "";

  $strExt = ($bWithPoint ? "." : "");
  switch ( $iCasse ){
  case -1 : // lower case
    $strExt .= mb_strtolower($tabMatch[2]);
    break;
  case 0 : // no change case
    $strExt .= $tabMatch[2];
    break;
  case 1 : // upper case
    $strExt .= mb_strtoupper($tabMatch[2]);
    break;
  }
  return $strExt;
}

/**
 * Retourne le nom d'un fichier sans son extension
 * Si le nom du fichier ne possède pas d'extension, retourne le nom complet du fichier 
 * @param strFile     Nom du fichier (! la recherche est basée sur le caract. '.') 
 * @return string nom du fichier sans son extension, nom du fichier s'il ne possède pas d'extension
 */
function getFileRadical($strFile)
{
  $tabMatch = array();
  preg_match("!^(.+)\.(.+)$!usi", $strFile, $tabMatch);
  if ( count($tabMatch)<3 ) return $strFile;
  return $tabMatch[1];
}

/**
 * Convertit le numéro de version de la forme V.Vm.Vr en un entier de la forme V0vm0vr
 * Exemple : 3.2.10 retourne 3002010
 */
function convertVersionNumberToInt($strVersion, $strSep=".")
{
  $tabVersion = explode($strSep, $strVersion);
  $iNb = count($tabVersion);
  $iVersion = 0;
  for($i=0; $i<$iNb; $i++) {
    $iVersion += $tabVersion[$i]*pow(10, ($iNb-$i-1)*3);
  }
  return $iVersion;
}

/**
 * Retourne le nom d'un fichier à partir d'un chemin complet
 * @param strPathFileName  chemin complet vers le fichier
 * @return string
 */
function extractFileName($strPathFileName)
{
  $iPos = mb_strrpos($strPathFileName, "/");
  if( $iPos === false ) {
    return $strPathFileName;
  }
  return mb_substr($strPathFileName, $iPos+1); 
}

/**
 * Créé les images redimensionnées d'une image
 * en fonction du tableau $tabImageSize du fichier app_conf_alkanet
 * Remarque : la casse de l'extension est conservée
 * @param img_name nom de l'image source
 * @param str_path  chemin complet vers l'image
 * @return tableau des images redimensionnées
 */
function resizeImage($img_name, $str_path)
{
  global $tabImageSize;
  global $tabImageSizeOverride;
  
  //recherche du cont_id pour vérifier si il n'existe pas un $tabImageSizeOverride dédié
  // dans ce cas le tableau est créé dans la fichier /libconf/app_conf.php et est suffixé de l'identiofiant de l'espace
  $pattern = "#".ALK_ROOT_UPLOAD."[^/]*/([^/]*)/#is";
  preg_match_all($pattern, $str_path, $matches);
  if( isset($matches[1]) && isset($matches[1][0]) && !defined("ALK_ROOT_TEMPLATE") ){
    $oSpace = AlkFactory::getSpace($matches[1][0]);
    if( file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."app_conf_espace.php") ) { 
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_CONF."app_conf_espace.php");
    }else { 
      require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_LIB."app_conf_espace.php");
    }
    ( !defined("ALK_ROOT_TEMPLATE") && define("ALK_ROOT_TEMPLATE",  ALK_ROOT_CONF."templates/") );
    ( !defined("ALK_CSS_DIR") && define("ALK_CSS_DIR", "") );
    ( !defined("ALK_JS_DIR") && define("ALK_JS_DIR", "") );
  }
  $return = array();
  
  if ( isset($tabImageSizeOverride)==true ){
    $tabImageSize = $tabImageSizeOverride;
  }
  
  foreach($matches[1] as $cont_id){
    $tabImageSizeOverrideContId = "tabImageSizeOverride".$cont_id;
  
    if ( isset($GLOBALS[$tabImageSizeOverrideContId])==true ){
      $tabImageSize = $GLOBALS[$tabImageSizeOverrideContId];
    }
  }
  
  $img_src = $str_path.$img_name;
  
  $img_ext = getFileExtension($img_name, true, 0);
  $img_ext_lower = strtolower($img_ext);
  
  if ( $img_ext_lower==".jpg" || $img_ext_lower==".jpeg" || $img_ext_lower==".gif" || $img_ext_lower==".png" ){
  
    // Lit les dimensions de l'image
    $size = @getimagesize($img_src);  
    $src_w = $size[0]; $src_h = $size[1];  
    
    foreach($tabImageSize as $strKey => $tabParam) {  
      
      // si l'original est plus petit que la déclinaison on créé une image de même taille
      if ( $src_w<$tabParam["width"] || $src_h<$tabParam["height"] ) {
        $tabParam["width"] = $src_w;
        $tabParam["height"] = $src_h;
      } else {
        // Teste les dimensions tenant dans la zone
        $test_h = round(($tabParam["width"] / $src_w) * $src_h);
        $test_w = round(($tabParam["height"] / $src_h) * $src_w);
        // Si Height final non précisé (0)
        if(!$tabParam["height"]) $tabParam["height"] = $test_h;
        // Sinon si Width final non précisé (0)
        elseif(!$tabParam["width"]) $tabParam["width"] = $test_w;
        // Sinon teste quel redimensionnement tient dans la zone
        elseif($test_h>$tabParam["height"]) $tabParam["width"] = $test_w;
        else $tabParam["height"] = $test_h;
      }
      
      if( defined("ALK_IEDIT_UPLOAD_SEPARATLY_DECLINAISONS") && ( defined("ALK_B_MEDIA_SHARED") && ALK_B_MEDIA_SHARED ==true ? strpos($str_path, "espace") !== false : strpos($str_path, "iedit") !== false)  && ALK_IEDIT_UPLOAD_SEPARATLY_DECLINAISONS ){
        $img_dest = $str_path.ALK_IEDIT_UPLOAD_DECLINAISONS_NAME_FOLDER."/".getFileRadical($img_name)."_".$strKey.$img_ext;
      }elseif(defined("ALK_B_MEDIA_SHARED") && ALK_B_MEDIA_SHARED ==true ? strpos($str_path, "espace") !== false : strpos($str_path, "gedit") !== false){
        $img_dest = $str_path."declinaisons/".getFileRadical($img_name)."_".$strKey.$img_ext;
        if(!is_dir($str_path."declinaisons/")){
          $img_dest = $str_path.getFileRadical($img_name)."_".$strKey.$img_ext;
        }
      }else{
        $img_dest = $str_path.getFileRadical($img_name)."_".$strKey.$img_ext;
      }
      // La vignette existe ?
      $test = (@file_exists($img_dest));
      // L'original a été modifié ?
      if($test)
        $test = (@filemtime($img_dest)>@filemtime($img_src));
      // Les dimensions de la vignette sont correctes ?
      if($test) {
        $size2 = @getimagesize($img_dest);
        $test = ($size2[0]==$tabParam["width"]);
        $test = ($size2[1]==$tabParam["height"]);
      }
    
      // Créer la vignette ?
      if(!$test) {
        // Crée une image vierge aux bonnes dimensions
        // $dst_im = ImageCreate($dst_w,$dst_h);
        $dst_im = @imagecreatetruecolor($tabParam["width"],$tabParam["height"]); 
        // Copie dedans l'image initiale redimensionnée
        switch($img_ext_lower){
          case ".jpg" :
          case ".jpeg" :
            $src_im = @imagecreatefromjpeg($img_src);
            break;
          case ".gif" :
            $src_im = @imagecreatefromgif($img_src);
            break;
          case ".png" :
            $src_im = @imagecreatefrompng($img_src);
            break;
        }
         
        
         /* Check if this image is PNG or GIF, then set if Transparent*/  
        if(($size[2] == 1) OR ($size[2]==3)){
         imagealphablending($dst_im, false);
         imagesavealpha($dst_im,true);
         $transparent = imagecolorallocatealpha($dst_im, 255, 255, 255, 127);
         imagefilledrectangle($dst_im, 0, 0, $tabParam["width"], $tabParam["height"], $transparent);
        }
        
        // ImageCopyResized($dst_im,$src_im,0,0,0,0,$dst_w,$dst_h,$src_w,$src_h);
        @imagecopyresampled($dst_im,$src_im,0,0,0,0,$tabParam["width"],$tabParam["height"],$src_w,$src_h);
        // Sauve la nouvelle image
        switch($img_ext_lower){
          case ".jpg" :
          case ".jpeg" :
            @imagejpeg($dst_im,$img_dest, 80);
            break;
          case ".gif" :
            @imagegif($dst_im,$img_dest);
            break;
          case ".png" :
            @imagepng($dst_im,$img_dest, 3);
            break;
        }
          
        // Détruis les tampons
        @imagedestroy($dst_im);  
        @imagedestroy($src_im);
        
        $return[] = $img_dest;
      }
    }
  }
  
  return $return;
}

/**
 * Retourne le nom du fichier image dans le format demandé
 * 
 * @param strPathFileName nom du fichier image, pouvant contenir un chemin et éventuellement déjà un suffixe de format (small, meduim ou large)
 * @param strPattern      format souhaité de l'image : xxsmall, xsmall, small, medium ou large
 * @param bDelSuffix      booleen permettant si mis à vrai de supprimer les suffix déjà présent dans le fileName passé en paramètre
 */
function getImageNameByPattern($strPathFileName, $strPattern, $bDelSuffix=false)
{
  global $tabImageSize;
  global $tabImageSizeOverride;
  
  if ( isset($tabImageSizeOverride)==true ){
    $tabImageSize = $tabImageSizeOverride;
  }
  
  $bFormatExists = false;
  $tabKey = array_keys($tabImageSize);
  foreach($tabKey as $strSuffix) {
    $bFormatExists = $bFormatExists || ($strSuffix == $strPattern);
    if($bDelSuffix){
      $strSuffix = "_".$strSuffix.".";
      $strPathFileName = str_replace($strSuffix, ".", $strPathFileName);
      $bDelSuffix = false;
    }
  }
  
  if( $bFormatExists ) {
    $strPathFileName = getFileRadical($strPathFileName)."_".$strPattern.getFileExtension($strPathFileName, true, 0);
  }
  
 return $strPathFileName;
}

/**
 * Retourne l'url d'une information à partir des paramètres suivants:
 * 
 * @param strRootUrl   url de base de la page sélectionnée dans la langue courante, l'url commmence par un slash
 * @param strParam     liste de paramètres complémentaires à cont_id, appli_id, page_id, lg_id
 * @param bWithRootUrl false par défaut, =true pour ajouter ALK_ROOT_URL devant strRootUrl
 * @param cat_id       identifiant de la catégorie ou dossier, =-1 par défaut
 * @param data_id      identifiant de la donnée, =-1 par défaut
 * @param iMode        mode d'accès à la donnée, =-1 par défaut
 * @param cat_name     intitulée la catégorie dans la langue courante, ="" par défaut
 * @param data_name    intitulée la donnée dans la langue courante, ="" par défaut
 */
function getRewriteUrlData($strRootUrl, $strParam, $bWithRootUrl=false, $cat_id="-1", $data_id="-1", $iMode="-1", $cat_name="", $data_name="")
{
  if ($cat_id!="-1" || $data_id!="-1" || $iMode!="-1") {
    $strRootUrl .= "/".
      ( $cat_id   !="-1" ? $cat_id : "")."_".
      ( $data_id  !="-1" ? $data_id : "").
      ( $iMode    !="-1" ? "_".$iMode : "").
      ( $cat_name !=""   ? "/".verifyPageName($cat_name) : "").
      ( $data_name!=""   ? "/".verifyPageName($data_name) : "");
  }
  
  if( $strRootUrl != "" ) {
    $strRootUrl .= ( $strParam != "" ? "?".$strParam : "" );
  }
  return ( $bWithRootUrl ? ALK_ROOT_URL : "").$strRootUrl;
}

/**
 * Retourne sous la forme d'un tableau les paramètres d'une url réécrite (cat_id, data_id, iMode)
 * 
 * @param strUrl   url de la page
 */
function getDataRewriteUrl($strUrl)
{
  $tabResults = array();
  $strPattern = "/\/([0-9]+)_([0-9]*)_*([0-9]*)\/(.*)/";
  preg_match($strPattern, $strUrl, $tabMatches, PREG_OFFSET_CAPTURE);
  
  if (count($tabMatches)>0){
    $tabResults = array ("cat_id" => $tabMatches[1][0],
                         "data_id" => $tabMatches[2][0],
                         "iMode" => $tabMatches[3][0],
                         "text" => $tabMatches[4][0]);
  }
  
  return $tabResults;
}

/**
 * Encode puis retourne une adresse mail. 
 * la fonction de décodage sur trouve dans lib_js.js : getDecodeEM()
 * @param strMail   email à encoder
 * @return string
 */
function getEncodeEM($strMail)
{
  $strTmp = str_replace("@", "#", $strMail);
  $strTmp = str_replace(".", ",", $strTmp);
  $strTmp = strrev($strTmp);
  $strRes = "";
  for($i=0; $i<strlen($strTmp); $i++) {
    $strRes .= ( rand(0, 1) == 0 ? strtoupper($strTmp[$i]) : strtolower($strTmp[$i]) );
  }
  return $strRes;
}

/**
 * 
 * @param  $link lien à vérifier
 * @param  $bincludePath
 * @param  $context
 * @param  $offset
 * @param  $maxlen nbre d'octets à obtenir
 */
function verifLink($url, $timeout = 10, $maxredirs = 1) 
{
  $tabIgnore = array("javascript", "alkanet.php", "mailto");
  foreach($tabIgnore as $find){
    if(strpos($url, $find) !== false)
      return true;
  }
  
  //si le lien est #, on n'en tient pas compte
  if($url =="#")
    return true;
  
  $options = array(
      'http' => array(
      'max_redirects' => $maxredirs, // PHP 5.1.0 et +
      'method' => 'HEAD',
      'header' => '',
      'timeout' => $timeout // Effectif pour les versions 5.2.1 et supérieures
    )
  );
  $contexte = stream_context_create($options);
  $tabHeader = array();
  $ret       = "";
    
  // on ajoute le rootUrl s'il n'y en a pas  
  if(strpos($url, "http://")===false)
    $url = ALK_ALKANET_ROOT_URL.$url;
  
  $fp = @fopen($url, 'r', FALSE, $contexte);
  if (!$fp){
    $fp = @file_get_contents($url, NULL, $contexte, -1, 10);
    if (isset($http_response_header))
      $tabHeader = $http_response_header;
  }else{
    $meta = stream_get_meta_data($fp);
    $tabHeader = $meta["wrapper_data"];
    fclose($fp);
  } 
  if (preg_match('#^HTTP/1\.[01] ([0-9]{3})#m', $tabHeader[0], $m)) {
      $ret = $m[1];
  }
  //302 : pour les liens qui sont redirigés vers erreur.php (a priori ce sont des liens alkanet accessibles avec droits)
  return ($ret == 200 || $ret == 302);
  
}

/** retourne les liens cassés d'un texte donnée
 * @param $strText texte contenant des liens à analyser
 * @return tableau contenant les liens cassés
 */
function verifTextLink($strText)
{
  $matches = array();
  $bRes = false;
  $strLog="";
  $tabWrongLink = array();
 
  if ($strText != ""){
    $pattern = "#<a[^>]*href=[\"\']?([^\"\'> ]*)[\"\']?[^>]*>#is"; // récupérer toutes les url
    
    preg_match_all($pattern, $strText, $matches);
    
    foreach($matches[1] as $url){
      $url = urldecode($url);
      $bRes = verifLink($url);
      if(!$bRes){
        $tabWrongLink[] = $url;
      }
    }
  }

  return $tabWrongLink;
}

/**
 * vérifie que les images du texte existent
 * @param $strText texte à analyser
 * @return retourne les images qui n'existent pas 
 */
function verifTextImage($strText)
{
   $tabWrongLink = array();
   $pattern = "#<img[^>]*src=[\"\']?([^\"\'> ]*)[\"\']?[^>]*>#is"; // récupérer toutes les url 
   preg_match_all($pattern, $strText, $matches);
   
   foreach($matches[1] as $index=>$file_name){
     $bRes = verifLink($file_name);
     if(!$bRes){
       $tabWrongLink[] = urldecode($file_name);
     }    
   }
  return $tabWrongLink;
}

/**
 * Effectue une mise en majuscules avec suppression ou maintient des accents
 * @param string $strVal        chaine à mettre en majuscules
 * @param bool   $bWithAccent   =true par défaut pour préserver les accents et les transformer en majuscules, 
 *                              =false pour remplacer les lettres accentuées par des non accentuées.
 * @return string
 */
function getUpper($strVal, $bWithAccent=true)
{
  return strtoupper(strtr($strVal, 
                          "eéèêëaäàâuüùûiîïôoöcçEÉÈÊËAÄÀÂUÜÙÛIÎÏÔOÖCÇ", 
                          ( !$bWithAccent
                            ? "eeeeeaaaauuuuiiiooocceeeeeaaaauuuuiiiooocc"
                            : "EÉÈÊËAÄÀÂUÜÙÛIÎÏÔOÖCÇEÉÈÊËAÄÀÂUÜÙÛIÎÏÔOÖCÇ")));
}

/**
 * Effectue une mise en minusules avec suppression ou maintient des accents
 * @param string $strVal           chaine à mettre en minusules
 * @param bool   $bPreservAccent   =true par défaut pour préserver les accents et les transformer en minusules,
 *                                 =false pour remplacer les lettres accentuées par des non accentuées.
 * @return string
 */
function getLower($strVal, $bWithAccent=true)
{
  return strtolower(strtr($strVal,
                    "eéèêëaäàâuüùûiîïôoöcçEÉÈÊËAÄÀÂUÜÙÛIÎÏÔOÖCÇ",
                    ( !$bWithAccent
                        ? "eeeeeaaaauuuuiiiooocceeeeeaaaauuuuiiiooocc"
                        : "eéèêëaäàâuüùûiîïôoöcçeéèêëaäàâuüùûiîïôoöcç")));
}

?>