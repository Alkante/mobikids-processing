<?php
/*licence/ 

  Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

  Nom du module : Alkanet
  Projet Alkanet.
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
 * @file app_conf.php
 * @package Alkanet_Appli
 * 
 * @brief  liste de constantes spécifiques à l'implentation de l'application
 */

//Permet d'afficher toutes les erreurs en entier
ini_set('log_errors_max_len','20000');
ini_set('memory_limit','4096M');
/** nom de la page d'identification dans scripts/ident/ */
define("ALK_INDEX_AUTH", "scripts/alkanet/index.php");

/** nom du template par défaut */
define("ALK_DEFAULT_RENDERER", "bs_alkanet");
/** nom du template par défaut */
define("ALK_DEFAULT_TEMPLATE", "bs_alkanet");
/** nom de la css par défaut */
define("ALK_DEFAULT_CSS", "alkanetbs_alkanet.css");

define("ALK_B_CSSJS_MINIMIZE", false);

/** timestamp de la date et heure la dernière mise à jour: mktime(Heure, Minute, Seconde, Mois, jour, année) */
//define("ALK_LAST_UPDATE", mktime(21, 0, 0, 6, 3, 2015));

/** utilisation d'un cookie pour mémoriser l'authentification avec délai d'expiration en minute */
define("ALK_B_COOKIE", true);
define("ALK_COOKIE_EXPIRE", 60*24*30*6);

/** Theme */
define("ALK_THEME_ALKANET",         "alkanet");
define("ALK_THEME_BS_ALKANET",      "bs_alkanet");
define("ALK_THEME_BS_SMARTADMIN",   "bs_smart_admin");
define("ALK_THEME",                 ALK_THEME_BS_ALKANET); 

define("ALK_THEME_ICON_FA",         "font-awesome");
define("ALK_THEME_ICON_GLYPHICONS", "glyphicons");
define("ALK_THEME_ICON",            ALK_THEME_ICON_FA);

/** Type d'authentification */
define("ALK_AUTH_ALKANET",     1);
define("ALK_AUTH_LDAP",        2);
define("ALK_AUTH_SSO",         3);
define("ALK_AUTH_NTLM_NAV",    4);
define("ALK_AUTH_NTLM_APACHE", 5);

/** Type d'authentification utilisé */
define ("ALK_AUTH", ALK_AUTH_ALKANET);

/** chargement des constantes SGBD */
define("ALK_B_SGBD_CONSTANT", true);

/** Type de SGBD possible */
define("ALK_SGBD_ORACLE",   "oracle");
define("ALK_SGBD_MYSQL",    "mysql");
define("ALK_SGBD_POSTGRES", "postgres");

// type SGBD spatial
define("ALK_SIGBD_ORACLESDE", ALK_SGBD_ORACLE);
define("ALK_SIGBD_MYGIS",     ALK_SGBD_MYSQL);
define("ALK_SIGBD_POSTGIS",   ALK_SGBD_POSTGRES);

/** Type de SGBD utilisé */
define("ALK_BDD_TYPE", ALK_SGBD_MYSQL);

/** Encodage client SGBD */
define("ALK_SGBD_ENCODING", "UTF-8");
define("ALK_MAIL_ENCODING", "UTF-8");

/** Utilisation d'une base spatiale : oui ou non */
define("ALK_SIGBD", false);

/** Type de SIG BD utilisé */
define("ALK_SIGBD_TYPE", ALK_SIGBD_POSTGIS);

/** paramètres de connexion Oracle 
define("ALK_ORA_LOGIN", "");
define("ALK_ORA_PWD",   "");
define("ALK_ORA_SID",   "");

/** paramètres de connexion Mysql 
define("ALK_MYSQL_LOGIN", "");
define("ALK_MYSQL_HOST",  "");
define("ALK_MYSQL_PWD",   "");
define("ALK_MYSQL_BD",    "");
define("ALK_MYSQL_PORT",  "");

/** paramètres de connexion Postgres 
define("ALK_POSTGRES_LOGIN", "****");
define("ALK_POSTGRES_HOST",  "****");
define("ALK_POSTGRES_PWD",   "****");
define("ALK_POSTGRES_BD",    "****");
define("ALK_POSTGRES_PORT",  "5432");*/

/** nom des schémas utilisés : préfixe de table */
define("ALK_SGBD_SCHEMA",   "alkanet");
define("ALK_SIGBD_SCHEMA",  "alkarto");

/** Connexion PDO */
define("ALK_PDO_DRIVERS_CONF", 
json_encode(array("default" => array("db_driver"     => "pgsql",
                                   "db_user"       => "db_mobikids_user",
                                   "db_password"   => "db_mobikids_password",
                                   "db_encoding"   => "utf8",
                                   "db_schema"     => "public",
                                   "dsn"           => array(
                                       //"host"   => "pg94.alkante.al",
                                       "host"   => "db_mobikids_host",
                                       "port"   => "db_mobikids_port",
                                       "dbname" => "db_mobikids_name",
                                       ),
                                   "db_options"    => array(
                                       //PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8",
                                       ),
                                   "db_attributes" => array(
                                       "ATTR_STATEMENT_CLASS"   => array("AlkDsPDO", array()),
                                       "ATTR_CASE"              => PDO::CASE_NATURAL,
                                       "ATTR_ERRMODE"           => PDO::ERRMODE_EXCEPTION,
                                       "ATTR_STRINGIFY_FETCHES" => true,
                                       "ATTR_DEFAULT_FETCH_MODE"=> PDO::FETCH_ASSOC,
                                       )),
               )));

define("ALK_ERRORLOG_LEVEL_ACTION", 912);  


/** Utilisation de LDAP : oui ou non, liaison fiche annuaire */
define("ALK_LDAP", false);

/** utilisation d'ACTIVEDIRECTORY comme annuaire ldap */
define("ALK_B_LDAP_AD", false);


/** Sous-répertoire contenant le si */
define("ALK_ROOT_DIR", "/");
define("ALK_VIRTUAL_DIR", "/");


/** constantes de recherche */
define("ALK_SEARCH_ALKANET", 0);

define("ALK_SEARCH", ALK_SEARCH_ALKANET); 


/** Adresse mail administrateur */
/** Adresse mail administrateur */
define("ALK_MAIL_ADMIN_MAIL",  "mobikids@alkante.com");
define("ALK_MAIL_ADMIN_NAME",  "Administrateur");
define("ALK_MAIL_ADMIN_LG",    "fr_FR");
define("ALK_MAIL_ADMIN_SIGNATURE", "");
define("ALK_MAIL_MAX_SEND", 200);
define("ALK_MAIL_SENDER", "Mobikids");
define("ALK_MAIL_NOREPLY_MAIL", "no-reply@alkante.com");
define("ALK_MAIL_NOREPLY_NAME", "Ne pas répondre svp");

// FROM : partie adresse mail de l'expéditeur : site-client@local-server.com
//        avec local-server.com qui s'obtient par : shell_exec("hostname -f") en php ou hostname -f en bash
define("ALK_MAIL_DEFAULT_FROM", "mobikids@mobikids.com");
// nom de l'expéditeur qui sera systématique
define("ALK_MAIL_DEFAULT_FROM_NAME", "Mobikids");
// ALK_MAIL_RETURN_PATH : mail qui doit exister et qui sera apte à gérer les retours d'erreur
define("ALK_MAIL_RETURN_PATH", "");

(!defined("ALK_MAIL_ASSISTANCE_MAIL") && define("ALK_MAIL_ASSISTANCE_MAIL",      "mobikids@alkante.com"));//cette adresse email est utilisé par la fonctionnalité "Contacter un administrateur"
(!defined("ALK_MAIL_ASSISTANCE_NAME") && define("ALK_MAIL_ASSISTANCE_NAME",      "Support et assistance")); //nom du correspondant en mode envoi d'une demande d'assistance

/** Chemin de base du SI */
define("ALK_ROOT_PATH", mb_ereg_replace(ALK_ROOT_DIR."libconf/app_conf.php", "", str_replace("\\", "/", __FILE__)));

/** Sous-répertoire contenant le repertoire upload */
define("ALK_ROOT_UPLOAD", "upload/");
define("ALK_ROOT_UPLOAD_TEMPLATE", "../upload/");  

/** Url et chemin de base Alkanet */
define("ALK_ALKANET_ROOT_PATH", ALK_ROOT_PATH.ALK_ROOT_DIR);
define("ALK_ALKANET_ROOT_URL",  ALK_ROOT_URL.ALK_ROOT_DIR);

/** chemin relatif de base pour les images */
define("ALK_MEDIA_PATH", ALK_ROOT_DIR."media/");

/** adresse de base pour les images */
define("ALK_MEDIA_URL", ALK_ALKANET_ROOT_URL."media/");

/** url du lecteur de flux RSS */
define("ALK_RSS_READER_URL", "http://rss.alkante.com/");

/** identifiant utilisateur spécifique */
define("ALK_USER_ID_ADMINALK", 1);
define("ALK_USER_ID_INTERNET", 2);
define("ALK_B_USER_INTERNET_EXISTS", true);
define("ALK_B_USER_INTERNET_CANCONNECT_ALKANET", false);

// ce paramètre permet à alkanet_verif.php d'authoriser l'authentification à un utilisateur
// ayant un compte valide, non autorisé au backoffice. Utile pour les extranet type internet authentifié.
// nécessite de fournir à alkanet_verif.php, le paramètre url
define("ALK_B_USER_CANCONNECT_EXTRANET", false);

/** Gestion des erreurs */
define("ALK_ERROR_B_LOG",      false);
define("ALK_ERROR_LOG_SCREEN", 1);
define("ALK_ERROR_LOG_FILE",   2);
define("ALK_ERROR_LOG_MAIL",   4);

/** Mode de gestion des erreurs */
define('ALK_ERROR_GEST', ALK_ERROR_LOG_SCREEN+ALK_ERROR_LOG_MAIL);

/** Integration de la trace d'exécution dans les rapports d'erreur
 *  0 : pas de trace d'execution de la pile
 *  1 : trace d'execution de la pile des variables 
 */
define("ALK_ERROR_DEBUG", 1);

/** Destinataire des logs : mail ou fichier */
define("ALK_ERROR_DEST_MAIL",     "support@alkante.com");
define("ALK_ERROR_DEST_PATHFILE", ALK_ROOT_PATH.ALK_ROOT_DIR."upload/log/");
define("ALK_ERROR_DEST_FILENAME", "errlog.txt");

/** Activation=true/Désactivation=false du mode débug */
define("ALK_DEBUG", false);

/** Activation=false/Désactivation=true de l'envoi de mail */
define("ALK_DEBUG_MAIL", false);

/** Activation=false/désactivation=true de tous les modes caches: memcache / smarty / css / js */
define("ALK_DEBUG_CACHE_MEMCACHE", false);
define("ALK_DEBUG_CACHE_TEMPLATE", false);
define("ALK_DEBUG_CACHE_JS", false);
define("ALK_DEBUG_CACHE_CSS", false);

/** activation des popus jQuery**/
define("ALK_B_POPUP_JQUERY", true);

/** Activation stats appli */
define("ALK_B_SPACE_STAT_APPLI", true);

/** déclaration générale des langues */
$GLOBALS["tabStrLocales"] = array("fr_FR" => "français - France", "en_GB" => "anglais - Grande Bretagne");

define("ALK_SECURITY_LETTERS", "346789ABCDEFGHJKMNPRTUVWXY");

define("ALK_B_SHOW_ALL_SHEETS", true);

////////////////////////////////////////////
// Mobikids specific constant
////////////////////////////////////////////
define("ALK_AST_NOMINATIM_URL", "http://nominatim.alkante.com/reverse.php");
define("ALK_AST_DATADIR_PATH", "/mobikids_data/");
define("ALK_AST_PROCESSING_PATH", "/mobikids_processing/");
define("ALK_AST_MOBILITY_SAMPLE_SIZE",500);
