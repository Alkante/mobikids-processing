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
 * @package Alkanet_Library
 * Contient toute la librairie commune javascript et php Alkanet
 *
 * @file app_conf_alkanet.php
 * Initialise les constantes Alkanet
 */

/** Encodage code php */
define("ALK_HTML_ENCODING", "UTF-8");
define("ALK_TIDY_ENCODING", "UTF8");
define("ALK_EXPORT_ENCODING", "ISO-8859-1");

mb_language("uni");
mb_internal_encoding(ALK_HTML_ENCODING);
mb_http_input(ALK_HTML_ENCODING);
mb_http_output(ALK_HTML_ENCODING);
mb_regex_encoding(ALK_HTML_ENCODING);

/** Indique un champs de formulaire non pris en compte */
define("ALK_FIELD_NOT_VIEW", "notview");

/** Format par défaut des dates en format 10 */
define("ALK_FORMAT_DATE10", "jj/mm/aaaa");

/** Constantes de répertoire */
if( !defined("ALK_ROOT_ADMIN") )     define("ALK_ROOT_ADMIN",     "admin/");
if( !defined("ALK_ROOT_CLASSE") )    define("ALK_ROOT_CLASSE",    "classes/");
if( !defined("ALK_ROOT_MODULE") )    define("ALK_ROOT_MODULE",    "scripts/");
if( !defined("ALK_ROOT_CONF") )      define("ALK_ROOT_CONF",      "libconf/");
if( !defined("ALK_ROOT_LIB") )       define("ALK_ROOT_LIB",       "lib/");
if( !defined("ALK_ROOT_STYLE") )     define("ALK_ROOT_STYLE",     "styles/");
if( !defined("ALK_ROOT_UPLOAD") )    define("ALK_ROOT_UPLOAD",    "upload/");
if( !defined("ALK_ROOT_PUPLOAD") )   define("ALK_ROOT_PUPLOAD",    "protect/");
if( !defined("ALK_ROOT_SUPLOAD") )   define("ALK_ROOT_SUPLOAD",    "sync/");
if( !defined("ALK_ROOT_SERVICE") )   define("ALK_ROOT_SERVICE",   "services/");
if( !defined("ALK_ROOT_MEDIASITE") ) define("ALK_ROOT_MEDIASITE", "media/site/");
if( !defined("ALK_INDEX_AUTH") )     define("ALK_INDEX_AUTH",     ALK_ROOT_MODULE."alkanet/index.php");

/** Nom des fichiers appelé par l'application */
define("ALK_ALKANET_IDENT",    ALK_ALKANET_ROOT_URL.ALK_INDEX_AUTH);
define("ALK_ALKANET",          ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet.php");
define("ALK_ALKANET_SQL",      ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_sql.php");
define("ALK_ALKANET_DOWNLOAD", ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_download.php");
define("ALK_ALKANET_AJAX",     ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_ajax.php");
define("ALK_ALKANET_HELP",     ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_help.php");
define("ALK_ALKANET_PROCESS",  ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_process.php");
define("ALK_ALKANET_SITE",     ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_site.php");
define("ALK_ALKANET_SITE_SQL", ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_site_sql.php");
define("ALK_ALKANET_RSS",      ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_rss.php");
define("ALK_ALKANET_GIS",      ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_gis.php");
define("ALK_ALKANET_MOBILE",   ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_mobile.php");
define("ALK_ALKANET_CAPTCHA",  ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_security_image.php");
define("ALK_ALKANET_VERIF",    ALK_ALKANET_ROOT_URL.ALK_ROOT_MODULE."alkanet/alkanet_verif.php");
define("ALK_ALKANET_VIRTUAL",  (defined("ALK_VIRTUAL_DIR") ? ALK_VIRTUAL_DIR : "" ));

/** type de format de syndication */
define("ALK_RSS1", "RSS 1.0", true);
define("ALK_RSS2", "RSS 2.0", true);
define("ALK_ATOM", "ATOM", true);

/** Type d'onglet */
define("ALK_TYPESHEET_CONSULT",   1);
define("ALK_TYPESHEET_ADMIN",     2);
define("ALK_TYPESHEET_PROPRIETE", 3);
define("ALK_TYPESHEET_POPUP",     4);

/** constante sur la méthode du formulaire */
define("ALK_FORM_METHOD_GET", 0);
define("ALK_FORM_METHOD_POST", 1);

/** constante sur le mode du formulaire */
define("ALK_FORM_MODE_READ",        0);
define("ALK_FORM_MODE_ADD",         1);
define("ALK_FORM_MODE_UPDATE",      2);
define("ALK_FORM_MODE_DEL",         3);
define("ALK_FORM_MODE_DOWNLOAD",    4);
define("ALK_FORM_MODE_AJAX",	      5);
define("ALK_FORM_MODE_DEACTIVATE",  6);
define("ALK_FORM_MODE_REACTIVATE",  7);
define("ALK_FORM_MODE_RAZ",         8);
define("ALK_FORM_MODE_TRANSMETTRE", 9);
define("ALK_FORM_MODE_IMPORT",     10);

/** droit sur les controles */
define("ALK_RIGHT_NONE",    0);
define("ALK_RIGHT_READ",    1);
define("ALK_RIGHT_UPDT",    2);
define("ALK_RIGHT_ADD",     4);
define("ALK_RIGHT_DEL",     8);

/** droit sur application */
define("ALK_APPLI_RIGHT_NONE",    0);
define("ALK_APPLI_RIGHT_READ",    1);
define("ALK_APPLI_RIGHT_ADMIN",   2);
define("ALK_APPLI_RIGHT_PUBLI",   4);

/** privilège sur espace et annuaire */
define("ALK_PRIV_SPACE_NONE",     0);  /** aucun droit                    */
define("ALK_PRIV_SPACE_USER",     1);  /** simple utilisateur             */
define("ALK_PRIV_ANNU_SERV",      2);  /** admin de service               */
define("ALK_PRIV_ANNU_ALL",       4);  /** admin de tous les services     */
define("ALK_PRIV_SPACE_VIEWER",   8);  /** consult tous les espaces       */
define("ALK_PRIV_SPACE_ANIM",    16);  /** animateur espace               */
define("ALK_PRIV_SPACE_ADMIN",   32);  /** animateur de tous les espaces  */

/** Types de téléchargement de pièces jointes */
define("ALK_DOWNLOAD_MODE_DEFAULT", ($iCpt=0));
define("ALK_DOWNLOAD_MODE_MEDIA",   (++$iCpt));
define("ALK_DOWNLOAD_MODE_ZIP",     (++$iCpt));

/** type de valeurs SQL */
define("ALK_SQL_TEXT",        ($iSql=0));
define("ALK_SQL_NUMBER",      ++$iSql);
define("ALK_SQL_DATE",        ++$iSql);
define("ALK_SQL_TIME",        ++$iSql);
define("ALK_SQL_DATETIME",    ++$iSql);
define("ALK_SQL_EXPR",        ++$iSql);
define("ALK_SQL_NONE",        ++$iSql);
define("ALK_SQL_DATECUR",     ++$iSql);
define("ALK_SQL_TEXT_ENCODE", ++$iSql);
define("ALK_SQL_HTML",        ++$iSql);
define("ALK_SQL_HTML_ENCODE", ++$iSql);
define("ALK_SQL_TEXT_RAWURL_ENCODE", ++$iSql);

/** constante de verification des parametres postés */
define("ALK_VERIF_DEFAULT",     pow(2, ($iVerif=0)));
define("ALK_VERIF_NUMERIC",     pow(2, ++$iVerif));
define("ALK_VERIF_DATE",        pow(2, ++$iVerif));
define("ALK_VERIF_ARRAY",       pow(2, ++$iVerif));
define("ALK_VERIF_ARRAYINT",    pow(2, ++$iVerif));
define("ALK_VERIF_ARRAYCHECK",  pow(2, ++$iVerif));
define("ALK_VERIF_CHECK",       pow(2, ++$iVerif));

/** constante de verification des controles javascript */
// champ obligatoire
define("ALK_IS_REQUIRED",       pow(2, ($iVerif=0)));
// vérification non stricte par rapport aux bornes de validation
define("ALK_IS_NOTSTRICT",      pow(2, ++$iVerif));

define("ALK_VERIF_MEMO",        pow(2, ++$iVerif));
define("ALK_VERIF_TEXT",        pow(2, ++$iVerif));
define("ALK_VERIF_INT",         pow(2, ++$iVerif));
define("ALK_VERIF_DATE10",      pow(2, ++$iVerif));
define("ALK_VERIF_RADIO",       pow(2, ++$iVerif));
define("ALK_VERIF_SELECT",      pow(2, ++$iVerif));
define("ALK_VERIF_MAIL",        pow(2, ++$iVerif));
define("ALK_VERIF_CHECKGROUP",  pow(2, ++$iVerif));
define("ALK_VERIF_TEXTNUM",     pow(2, ++$iVerif));
define("ALK_VERIF_TEXTALPHA",   pow(2, ++$iVerif));
define("ALK_VERIF_TEXTFILENAME",pow(2, ++$iVerif));
define("ALK_VERIF_HEURE5",      pow(2, ++$iVerif));
define("ALK_VERIF_TOKEN",       pow(2, ++$iVerif));
define("ALK_VERIF_REGEXP",      pow(2, ++$iVerif));

/** action cron, valeur équivalente en base (SIT_CRON_ACTION) */
define("ALK_CRONACTION_ARCHIVAUTO",     1);
define("ALK_CRONACTION_PURGEAUTO",      2);
define("ALK_CRONACTION_RAPPELRDV",      3);
define("ALK_CRONACTION_RAPPELTACHE",    4);
define("ALK_CRONACTION_RAPPELEVENT",    5);
define("ALK_CRONACTION_GETSTATS",       7);

/** actions logs, valeur équivalente en base (SIT_LOG_ACTION) */
define("ALK_LOGACTION_NONE",        0);
define("ALK_LOGACTION_CONNECT",     1);
define("ALK_LOGACTION_CONSULT",     2);
define("ALK_LOGACTION_DOWNLOAD",    3);
define("ALK_LOGACTION_ADMIN",       4);
define("ALK_LOGACTION_PDF",         5);

/** actions statistiques, valeur équivalente en base (SIT_STAT_ACTION) */
define("ALK_STATACTION_NONE",       0);
define("ALK_STATACTION_CONSULT",    1);
define("ALK_STATACTION_MODIF",      2);
define("ALK_STATACTION_SUPPR",      3);
define("ALK_STATACTION_DOWNLOAD",   4);

/** types de données, valeur équivalente en base (SIT_DATATYPE) */
define("ALK_SIT_DATATYPE_ACTU_CAT",     1);
define("ALK_SIT_DATATYPE_ACTU",         2);
define("ALK_SIT_DATATYPE_ACTU_PJ",      3);
define("ALK_SIT_DATATYPE_FAQS_CAT",     4);
define("ALK_SIT_DATATYPE_FAQS",         5);
define("ALK_SIT_DATATYPE_FAQS_PJ",      6);
define("ALK_SIT_DATATYPE_GLOSS_CAT",    7);
define("ALK_SIT_DATATYPE_GLOSS",        8);
define("ALK_SIT_DATATYPE_GLOSS_PJ",     9);
define("ALK_SIT_DATATYPE_LIEN_CAT",     10);
define("ALK_SIT_DATATYPE_LIEN",         11);
define("ALK_SIT_DATATYPE_LIEN_PJ",      12);
define("ALK_SIT_DATATYPE_DOC_CAT",      13);
define("ALK_SIT_DATATYPE_DOC",          14);
define("ALK_SIT_DATATYPE_DOC_PJ",       15);
define("ALK_SIT_DATATYPE_RDV_CAT",      16);
define("ALK_SIT_DATATYPE_RDV",          17);
define("ALK_SIT_DATATYPE_RDV_PJ",       18);
define("ALK_SIT_DATATYPE_EVENT_CAT",    19);
define("ALK_SIT_DATATYPE_EVENT",        20);
define("ALK_SIT_DATATYPE_EVENT_PJ",     21);
define("ALK_SIT_DATATYPE_COMMENT",      22);
define("ALK_SIT_DATATYPE_COMMENT_PJ",   23);
define("ALK_SIT_DATATYPE_FORUM",        24);
define("ALK_SIT_DATATYPE_FORUM_PJ",     25);
define("ALK_SIT_DATATYPE_GEDIT",        26);

/** Module Alkanet toujours disponible */
define("ALK_B_ATYPE_ALKANET", true);

/** type d'application */
define("ALK_ATYPE_ID_ESPACE",       0);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ESPACE,        "Espace");
define("ALK_ATYPE_ID_ANNU",         1);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ANNU,          "Annu");
define("ALK_ATYPE_ID_ALKANET",      2);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ALKANET,       "Alkanet");
define("ALK_ATYPE_ID_BUDGET",       3);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BUDGET,        "Budget");
define("ALK_ATYPE_ID_FDOC",         4);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FDOC,          "FDoc");
define("ALK_ATYPE_ID_GENEA",        5);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GENEA,         "Genea");
define("ALK_ATYPE_ID_LSDIF",        6);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_LSDIF,         "LsDif");
define("ALK_ATYPE_ID_PRODIGEDIST",  7);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PRODIGEDIST,   "ProdigeDist");
define("ALK_ATYPE_ID_SIGADMIN",     8);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SIGADMIN,      "SigAdmin");
define("ALK_ATYPE_ID_HBGT",         9);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_HBGT,          "Hbgt");
define("ALK_ATYPE_ID_BEF",         10);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BEF,           "Bef");
define("ALK_ATYPE_ID_TIF",         11);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TIF,           "Tif");
define("ALK_ATYPE_ID_FE",          12);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FE,            "Fe");
define("ALK_ATYPE_ID_RAA",         13);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_RAA,           "RAA");
define("ALK_ATYPE_ID_ASPICC",      14);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ASPICC,        "Aspicc");
define("ALK_ATYPE_ID_GESTPROJ",    15);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GESTPROJ,      "GestProj");
define("ALK_ATYPE_ID_OPENLAYERS",  16);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_OPENLAYERS,    "OpenLayers");
define("ALK_ATYPE_ID_CIPASYNTHESE",17);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CIPASYNTHESE,  "CipaSynthese");
define("ALK_ATYPE_ID_CIPABILAN",   18);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CIPABILAN,     "CipaBilan");
define("ALK_ATYPE_ID_BDALK",       19);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BDALK,         "BDAlk");
define("ALK_ATYPE_ID_EDITEUR",     20);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_EDITEUR,       "Editeur");
define("ALK_ATYPE_ID_ACTU",        21);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ACTU,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_ACTU, "Actu");
define("ALK_ATYPE_ID_LIEN",        22);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_LIEN,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_LIEN, "Lien");
define("ALK_ATYPE_ID_GEDIT",       23);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GEDIT,         "GEdit");
define("ALK_ATYPE_ID_GLOS",        24);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GLOS,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_GLOS, "Glos");
define("ALK_ATYPE_ID_FAQS",        25);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FAQS,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_FAQS, "Faqs");
define("ALK_ATYPE_ID_SYND",        26);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SYND,          "Synd");
define("ALK_ATYPE_ID_NEWSLETTER",  27);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_NEWSLETTER,    "Newsletter");
define("ALK_ATYPE_ID_DISPASTREINTE", 28);     define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DISPASTREINTE, "DispAstreinte");
define("ALK_ATYPE_ID_TRAD",        29);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TRAD,          "Trad");
define("ALK_ATYPE_ID_SIGZA",       30);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SIGZA,         "Sigza");
define("ALK_ATYPE_ID_RECETTE",     31);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_RECETTE,       "Recette");
define("ALK_ATYPE_ID_FORM",        32);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FORM,          "Form");
define("ALK_ATYPE_ID_UPLOADER",    33);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_UPLOADER,      "Uploader");
define("ALK_ATYPE_ID_FONCIER",     34);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FONCIER,       "Foncier");    
define("ALK_ATYPE_ID_DROITS",      35);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DROITS,        "Droits");
define("ALK_ATYPE_ID_DISPANNUAIRE",36);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DISPANNUAIRE,  "DispAnnuaire");
define("ALK_ATYPE_ID_GISVIEWER",   37);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GISVIEWER,     "GisViewer");
define("ALK_ATYPE_ID_GISADMIN",    38);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GISADMIN,      "GisAdmin");
define("ALK_ATYPE_ID_CEDIT",       39);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CEDIT,         "CEdit");
define("ALK_ATYPE_ID_GISATLAS",    40);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GISATLAS,      "GisAtlas");
define("ALK_ATYPE_ID_EXPLORER",    41);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_EXPLORER,      "Explorer");
define("ALK_ATYPE_ID_ASPIC",       42);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ASPIC,         "Aspic");
define("ALK_ATYPE_ID_FORMATION",   43);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FORMATION,     "Formation");
define("ALK_ATYPE_ID_FORUM",       44);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_FORUM,         "Forum");
define("ALK_ATYPE_ID_MTRACK",      45);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MTRACK,        "MTrack");
define("ALK_ATYPE_ID_O2IINVEST",   46);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_O2IINVEST,     "O2iInvest");
define("ALK_ATYPE_ID_GCOEDIT",     47);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GCOEDIT,       "GCoEdit");
define("ALK_ATYPE_ID_PELERINAGE",  48);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PELERINAGE,    "Pelerinage");
define("ALK_ATYPE_ID_BILLETTERIE", 49);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BILLETTERIE,   "Billetterie");
define("ALK_ATYPE_ID_GALAXIA",     50);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GALAXIA,        "Galaxia");
define("ALK_ATYPE_ID_FDOC_DIST",   51);
define("ALK_ATYPE_ID_COMMPRESS",   52);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_COMMPRESS,     "CommPress");
define("ALK_ATYPE_ID_ATLAS",       53);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ATLAS,         "Atlas");
define("ALK_ATYPE_ID_BDC",         54);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BDC,           "Bdc");
define("ALK_ATYPE_ID_GEOLOC",      55);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GEOLOC,        "Geoloc");
define("ALK_ATYPE_ID_GESTINST",    56);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GESTINST,      "GestInst");
define("ALK_ATYPE_ID_GRR",         57);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRR,           "GRR");
define("ALK_ATYPE_ID_GID",         58);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GID,           "Gid");
define("ALK_ATYPE_ID_CRV",         59);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CRV,           "Crv");
define("ALK_ATYPE_ID_ASIGS",       60);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ASIGS,         "site");
define("ALK_ATYPE_ID_MDATA",       61);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MDATA,         "MData");
define("ALK_ATYPE_ID_PROFITSOFT",  62);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PROFITSOFT,    "Profitsoft");
define("ALK_ATYPE_ID_SEARCH",      63);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SEARCH,        "Search");
define("ALK_ATYPE_ID_DICO",        64);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DICO,          "Dico");
define("ALK_ATYPE_ID_WEBSERV",     65);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_WEBSERV,       "WebServ");
define("ALK_ATYPE_ID_IEDIT",       66);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_IEDIT,         "IEdit");
define("ALK_ATYPE_ID_OEDIPE",      67);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_OEDIPE,        "Oedipe");
define("ALK_ATYPE_ID_RDV",         68);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_RDV,           "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_RDV, "Rdv");
define("ALK_ATYPE_ID_SIGSITE",     69);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SIGSITE,       "SigSite");         define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_SIGSITE, constant("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SIGADMIN));
/** @creator DMA @project CRCI */
define("ALK_ATYPE_ID_REB",         70);    	  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_REB,           "Reb");    
/** @creator ASA @project CC-PRF */
define("ALK_ATYPE_ID_COMMENT",     71);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_COMMENT,       "Comment");
/** @creator BF @project GIPSA */
define("ALK_ATYPE_ID_GEOSOURCE",   72);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GEOSOURCE,     "Geosource");
define("ALK_ATYPE_ID_OLAP",        73);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_OLAP,          "Olap");
define("ALK_ATYPE_ID_MAPLINK",     74);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MAPLINK,       "MapLink");
define("ALK_ATYPE_ID_INTEGCRES",   75);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_INTEGCRES,     "IntegCres");
define("ALK_ATYPE_ID_GWF",         76);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GWF,           "Gwf");
define("ALK_ATYPE_ID_COUV",        77);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_COUV,          "Couvertures");
define("ALK_ATYPE_ID_CADASTRE",    78);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CADASTRE,      "Cadastre");
define("ALK_ATYPE_ID_CNCRESEVENT", 79);       define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CNCRESEVENT,   "Cncresevent");
define("ALK_ATYPE_ID_PNRLATBLOGCONTACT",80);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PNRLATBLOGCONTACT, "PnrlatBlogContact");
define("ALK_ATYPE_ID_RETRAIT",      81);      define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_RETRAIT,        "Retrait");          // projet SI17
define("ALK_ATYPE_ID_DEPOT",        82);      define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DEPOT,          "Depot");            // projet SI17
define("ALK_ATYPE_ID_STOCKAGE",     83);      define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_STOCKAGE,       "Stockage");         // projet SI17
define("ALK_ATYPE_ID_GEOSOURCEADMIN", 84);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GEOSOURCEADMIN, "GeosourceAdmin");  // projet SI17
define("ALK_ATYPE_ID_PERIMETREADMIN", 85);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PERIMETREADMIN, "PerimetreAdmin");  // projet SI17
define("ALK_ATYPE_ID_ALKCVS",         86);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ALKCVS,         "AlkCVS");
define("ALK_ATYPE_ID_COLLAB",         87);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_COLLAB,         "Collab");
define("ALK_ATYPE_ID_DOC",            88);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_DOC,            "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_DOC, "Doc");
define("ALK_ATYPE_ID_EVENT",          89);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_EVENT,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_EVENT, "Event");
define("ALK_ATYPE_ID_WIKIDIST",       90);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_WIKIDIST,       "WikiDist");
define("ALK_ATYPE_ID_JMAPLINK",       91);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_JMAPLINK,       "JMaplink");
define("ALK_ATYPE_ID_ID4CARIMPORT",   92);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ID4CARIMPORT, 	 "Id4carImport");
define("ALK_ATYPE_ID_ID4CARANNU",     93);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ID4CARANNU,     "Id4carAnnu");
define("ALK_ATYPE_ID_SITEFONCIER",    94);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SITEFONCIER,    "SiteFoncier");             // application propre aux projets SIG Foncier
define("ALK_ATYPE_ID_GRAPHE",         95);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GRAPHE,         "Graphe");
define("ALK_ATYPE_ID_IFREMERALERTE",  96);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_IFREMERALERTE,  "IfremerAlerte");             
define("ALK_ATYPE_ID_TACHE",          97);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TACHE,          "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_TACHE, "Tache");
define("ALK_ATYPE_ID_AMP",            98);    define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_AMP,            "AMP");       
define("ALK_ATYPE_ID_ALKAGIS_GISADMIN",99);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ALKAGIS_GISADMIN, "AlkGisAdmin");
define("ALK_ATYPE_ID_SMSADMIN"  ,     100);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SMSADMIN,       "smsAdmin");  
define("ALK_ATYPE_ID_SMSCONSULT"  ,   101);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SMSCONSULT,     "smsConsult");          
define("ALK_ATYPE_ID_PNRAEVAL",       102);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_PNRAEVAL,       "Eval");
define("ALK_ATYPE_ID_CDGADMINGED",    103);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CDGADMINGED,    "CDGAdminGed");
define("ALK_ATYPE_ID_GEOCLIPDIST"  ,  104);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GEOCLIPDIST,    "GeoclipDist");
define("ALK_ATYPE_ID_TDBMEPARAMETRE",  105);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TDBMEPARAMETRE, "TdbmeParametre");
define("ALK_ATYPE_ID_TDBMETRAVAUX" ,   106);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TDBMETRAVAUX,   "TdbmeTravaux");
define("ALK_ATYPE_ID_TDBMEDEPENSE"  ,  107);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TDBMEDEPENSE,   "TdbmeDepense");
define("ALK_ATYPE_ID_TAD"          ,   108);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_TAD,            "TAD");
define("ALK_ATYPE_ID_BDCOMRESP"    ,   109);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BDCOMRESP,      "BDComResp");
define("ALK_ATYPE_ID_ACL"          ,   110);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ACL,            "ACL");
define("ALK_ATYPE_ID_LINK"         ,   111);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_LINK,           "Link");
define("ALK_ATYPE_ID_CRBN_CAT",        112);  define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CRBN_CAT,       "CRBN_Cat");
define("ALK_ATYPE_ID_BILLING",        113);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_BILLING,        "Billing");
define("ALK_ATYPE_ID_MLCENTIGON"   ,  114);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MLCENTIGON,     "MLCentigon");
define("ALK_ATYPE_ID_MLCENTIGONSECU", 115);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MLCENTIGONSECU, "MLCentigonSecu");
define("ALK_ATYPE_ID_MLCENTIGONMAINT",116);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MLCENTIGONMAINT,"MLCentigonMaint");
define("ALK_ATYPE_ID_MLCENTIGONPROD", 117);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MLCENTIGONPROD, "MLCentigonProd");
define("ALK_ATYPE_ID_CUSTOM",         118);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_CUSTOM,         "IEdit");           define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_CUSTOM, "Custom");
define("ALK_ATYPE_ID_MAPLINKPOI",     119);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MAPLINKPOI,     "MapLinkPOI");
define("ALK_ATYPE_ID_ANTHRACO",       120);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ANTHRACO,       "Anthraco");
define("ALK_ATYPE_ID_GOP",            121);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_GOP,            "GOp");
define("ALK_ATYPE_ID_MAPLINKTRACKING",122);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MAPLINKTRACKING,"MapLinkTracking");
define("ALK_ATYPE_ID_OBSOIS",         123);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_OBSOIS,         "ObsOis");
define("ALK_ATYPE_ID_MAPLINKTPMS",    124);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MAPLINKTPMS,    "MaplinkTPMS");
define("ALK_ATYPE_ID_MAPLINKLITE",    125);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_MAPLINKLITE,    "MaplinkLite");
define("ALK_ATYPE_ID_LEAFLET",        126);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_LEAFLET,        "Leaflet");
define("ALK_ATYPE_ID_SABORDSPI",      127);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SABORDSPI,      "SabordSPI");
define("ALK_ATYPE_ID_SUIVIACTIVITE",  128);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_SUIVIACTIVITE,  "SuiviActivite");
define("ALK_ATYPE_ID_ESPACECLIENT",   129);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_ESPACECLIENT,   "EspaceClient");
define("ALK_ATYPE_ID_KALISPORT",   		130);   define("ALK_ATYPE_ABREV_".ALK_ATYPE_ID_KALISPORT,   		"Kalisport"); define("ALK_SATYPE_ABREV_".ALK_ATYPE_ID_KALISPORT, "Kalisport");

/** liste des applications n'ayant pas de atype_id en base de donneés
 *  et nécessitant un traitement dans la boucle des traitement oSpace : ajout/suppression utilisateur, etc... */
define("ALK_LIST_ATYPE_ID_SPECIAL", serialize(array(ALK_ATYPE_ID_SYND, ALK_ATYPE_ID_CRV)));

$cptCst = -1; 
/** Onglet null */                                       define("ALK_SHEET_NONE",           $cptCst++);
/** Onglet d'accueil en consultation */                  define("ALK_SHEET_CONSULTATION",   $cptCst++);//[WSPACE]ACCUEIL, [FAQS], [ACTU], [GLOS], ...
/** Onglet "Administration" et équivalents */            define("ALK_SHEET_ADMINISTRATION", $cptCst++);
/** Onglet "Propriétés" et équivalents */                define("ALK_SHEET_PROPRIETE",      $cptCst++);

/** Onglet "Recherche" et équivalents */                 define("ALK_SHEET_RECHERCHE",      $cptCst++);
/** Onglet "Résultats" et équivalents */                 define("ALK_SHEET_RESULTAT",       $cptCst++);
/** Onglet "Fiche" et équivalents */                     define("ALK_SHEET_FICHE",          $cptCst++);//[ANNU]MAFICHE
/** Onglet "Ajouter" et équivalents */                   define("ALK_SHEET_AJOUTER",        $cptCst++);//[ANNU]ADDFICHE
/** Onglet "Modifier" et équivalents */                  define("ALK_SHEET_MODIFIER",       $cptCst++);//[ANNU]MAJFICHE
/** Onglet "Corbeille" et équivalents */                 define("ALK_SHEET_CORBEILLE",      $cptCst++);
/** Onglet "Publier" et équivalents */                   define("ALK_SHEET_PUBLIER",        $cptCst++);

/** Onglet "Forum" et équivalents */                     define("ALK_SHEET_FORUM",          $cptCst++);
/** Onglet "Forum"/"Abonnements */                       define("ALK_SHEET_FORUMABONNEMENT",$cptCst++);//Gestion des abonnements depuis la gestion des forums
/** Onglet "Article" et équivalents */                   define("ALK_SHEET_ARTICLE",        $cptCst++);
/** Onglet "Discussion" et équivalents */                define("ALK_SHEET_DISCUSSION",     $cptCst++);
/** Onglet "Theme"/"Thématique" et équivalents */        define("ALK_SHEET_THEME",          $cptCst++);//THEMATIQUE
/** Onglet "Rubrique" et équivalents */                  define("ALK_SHEET_RUBRIQUE",       $cptCst++);
/** Onglet "Nouveau(té)" et équivalents */               define("ALK_SHEET_NOUVEAUTE",      $cptCst++);

/** Onglet "Arbo./Organ./Plan/Explor." et équivalents */ define("ALK_SHEET_ARBORESCENCE",   $cptCst++);//[FDOC]EXPLORER, [ANNU]ORGA, [WSPACE]PLAN
/** Onglet "Import/Export" et équivalents */             define("ALK_SHEET_IMPORTEXPORT",   $cptCst++);
/** Onglet "Statistiques" et équivalents */              define("ALK_SHEET_STATS",          $cptCst++);
/** Onglet "Statistiques" et équivalents */              define("ALK_SHEET_STATSAPPLI",     $cptCst++);
/** Onglet "Logs" et équivalents */                      define("ALK_SHEET_LOGS",           $cptCst++);

/** Onglet "En cours" et équivalents */                  define("ALK_SHEET_ENCOURS",        $cptCst++);
/** Onglet "A venir" et équivalents */                   define("ALK_SHEET_AVENIR",         $cptCst++);
/** Onglet "Périmés" et équivalents */                   define("ALK_SHEET_PASSES",         $cptCst++);
/** Onglet "A valider" et équivalents */                 define("ALK_SHEET_AVALIDER",       $cptCst++);
/** Onglet "En cours de validaton */                     define("ALK_SHEET_VALIDATION",     $cptCst++);
/** Popup de l'edition de formulaire */                  define("ALK_SHEET_CONT_FORM",      $cptCst++);
/** Popup de l'apercu de formulaire */                   define("ALK_SHEET_APERCU",         $cptCst++);

/** Onglet "Boite de réception" et équivalents */        define("ALK_SHEET_RECEPTION",      $cptCst++);
/** Onglet "Boite d'envoi" et équivalents */             define("ALK_SHEET_ENVOYES",        $cptCst++);
/** Onglet "Mes listes" et équivalents */                define("ALK_SHEET_MESLISTES",      $cptCst++);
/** Onglet "Modèles" et équivalents */                   define("ALK_SHEET_MODELES",        $cptCst++);
/** Onglet "Lettres" et équivalents */                   define("ALK_SHEET_LETTRES",        $cptCst++);

/** Onglet "Service" et équivalents */                   define("ALK_SHEET_SERVICE",        $cptCst++);
/** Onglet "Fonction" et équivalents */                  define("ALK_SHEET_FONCTION",       $cptCst++);
/** Onglet "Profil" et équivalents */                    define("ALK_SHEET_PROFIL",         $cptCst++);
/** Onglet "Mission" et équivalents */                   define("ALK_SHEET_MISSION",        $cptCst++);
/** Onglet "Accès carto" et équivalents */               define("ALK_SHEET_ACCESCARTO",     $cptCst++);

/** popup fiche "Abonné" et équivalents */               define("ALK_SHEET_ABONNE",         $cptCst++);
/** Onglet "Abonnés valides" et équivalents */           define("ALK_SHEET_ABONNELISTE",    $cptCst++);
/** Onglet "Abonnés valides" et équivalents */           define("ALK_SHEET_ABONNEFORM",     $cptCst++);
/** Onglet "Abonnés valides" et équivalents */           define("ALK_SHEET_ABONNEVALIDE",   $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_ABONNENONVALIDE",$cptCst++); 
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_CLASSIFICATION", $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_ABONNE_LETTER",  $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_LETTER_ABONNE",  $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_CLASSIF_LETTER", $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_LETTER_CLASSIF", $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_ABONNE_CLASSIF", $cptCst++);
/** Onglet "Abonnés non valides" et équivalents */       define("ALK_SHEET_CLASSIF_ABONNE", $cptCst++);
/** Onglet "Abonnés " et équivalents */                  define("ALK_SHEET_IMPORT_EXPORT_ABONNE",  $cptCst++);

/** Onglet "Calendrier" et équivalents */                define("ALK_SHEET_CALENDRIER",     $cptCst++); 
/** Onglet "Ressources" et équivalents */                define("ALK_SHEET_RESSOURCES",     $cptCst++); 
/** Onglet "Type Réservation" et équivalents */          define("ALK_SHEET_TYPERESERV",     $cptCst++); 
/** Onglet "Jours Fériés" et équivalents */              define("ALK_SHEET_JRFERIES",       $cptCst++);

/** Onglet "Application" et équivalents */               define("ALK_SHEET_APPLICATION",    $cptCst++); 
/** Onglet "Utilisateurs" et équivalents */              define("ALK_SHEET_UTILISATEUR",    $cptCst++); 
/** Onglet "Animateurs" et équivalents */                define("ALK_SHEET_ANIMATEUR",      $cptCst++); 
/** Onglet "Links" et équivalents */                     define("ALK_SHEET_LINK",           $cptCst++); 
/** Onglet "Planification" et équivalents */             define("ALK_SHEET_PLANIF",         $cptCst++); 

/** Page de Liste */                                     define("ALK_SHEET_LIST",           $cptCst++); 
/** Page de Formulaire */                                define("ALK_SHEET_FORM",           $cptCst++); 
/** Page de Mots clés */                                 define("ALK_SHEET_PONDERATION",    $cptCst++); 
/** Export service */                                    define("ALK_SHEET_EXPORT_SERVICE", $cptCst++); 
/** Export Mission */                                    define("ALK_SHEET_EXPORT_MISSION", $cptCst++); 
/** Export profil */                                     define("ALK_SHEET_EXPORT_PROFIL",  $cptCst++); 
/** Export agent */                                      define("ALK_SHEET_EXPORT_AGENT",   $cptCst++);
/** Export de courriels */                               define("ALK_SHEET_EXPORT_MAIL",    $cptCst++);
/** Import de service */                                 define("ALK_SHEET_IMPORT_SERV",    $cptCst++);
/** Import d'informations sur les agents' */             define("ALK_SHEET_IMPORT_USER",    $cptCst++);
/** Import de courriels' */                              define("ALK_SHEET_IMPORT_MAIL",    $cptCst++);
 
/** selection multiple utilisateur   */                  define("ALK_SHEET_USERS_LIST",     $cptCst++); 
/** selection individuel utilisateur */                  define("ALK_SHEET_USER_LIST",      $cptCst++); 
/** fiche utilisateur */                                 define("ALK_SHEET_USER",           $cptCst++); 
/** gestion de droit */                                  define("ALK_SHEET_RIGHT",          $cptCst++); 
/** gestion de droit sur utilisateur */                  define("ALK_SHEET_USERRIGHT",      $cptCst++); 
/** gestion de droit sur profil */                       define("ALK_SHEET_PROFILRIGHT",    $cptCst++); 
/** popup de création de compte et mot de passe oublié */define("ALK_SHEET_CREATEUSER",     $cptCst++); 
/** popup de modif mot de passe */                       define("ALK_SHEET_CHANGEPWD",      $cptCst++);
/** popup contact admin */                               define("ALK_SHEET_CONTACT",        $cptCst++);
/** popup contact motivation */                          define("ALK_SHEET_USER_MOTIVATION",$cptCst++);

/** Subsheet des domaines ds le grr */                   define("ALK_SHEET_DOMAINE",        $cptCst++);
/** Subsheet confirmation des réservations ds le grr */  define("ALK_SHEET_CONFIRM_RESA",   $cptCst++);
/** popup du crv */                                      define("ALK_SHEET_CRV",            $cptCst++);
/** popup du creation etape 1 crv */                     define("ALK_SHEET_ETAPE1",         $cptCst++);
/** popup du creation etape 2 crv */                     define("ALK_SHEET_ETAPE2",         $cptCst++);
/** popup du creation etape  crv */                      define("ALK_SHEET_ETAPE",          $cptCst++);
/** popup de relance du crv  */                          define("ALK_SHEET_RELANCE",        $cptCst++);  
/** popup de finalisdation du crcuit du crv  */          define("ALK_SHEET_VALID_CIRC",     $cptCst++);                
/** popup de tableau de bord crv cote consult  */        define("ALK_SHEET_CRV_CONSULT",    $cptCst++);                
/** popup de affichage contributeur consult  */          define("ALK_SHEET_CONTRIB",        $cptCst++);
/** popup d'affichage du formulaire d'envoi de mail */   define("ALK_SHEET_MAIL",           $cptCst++);

/** Fonctionnalités AJAX ou JS */                        define("ALK_SHEET_AJAX",           $cptCst++); 

/** SIGADMIN : onglet propriétés */                      define("ALK_SHEET_PROPERTY",       $cptCst++);
/** SIGADMIN : onglet symbologie */                      define("ALK_SHEET_SYMBOLOGY",      $cptCst++);
/** SIGADMIN : onglet étiquettes */                      define("ALK_SHEET_LABELS",         $cptCst++);
/** SIGADMIN : onglet droits d'affichage */              define("ALK_SHEET_RIGHTS",         $cptCst++);
/** SIGADMIN : onglet gestion des jointures */           define("ALK_SHEET_JOINTURES",      $cptCst++);
/** SIGADMIN : onglet Tooltip */                         define("ALK_SHEET_TOOLTIP",        $cptCst++);
/** SIGADMIN : gestion d'une couches dans projet */      define("ALK_SHEET_COUCHE",         $cptCst++);
/** SIGADMIN : analyse d'une couche dans projet */       define("ALK_SHEET_ANALYSE",        $cptCst++);
/** SIGADMIN : gestion des styles SIG */                 define("ALK_SHEET_STYLE",          $cptCst++);
/** SIGADMIN : bibliothèque de symboles SIG */           define("ALK_SHEET_LIBRARY",        $cptCst++);
/** SIGADMIN : définition d'une classe d'analyse */      define("ALK_SHEET_CLASSE",         $cptCst++);
/** SIGADMIN : gestion des extents disponibles */        define("ALK_SHEET_EXTENT",         $cptCst++);
/** SIGADMIN : options des projets */                    define("ALK_SHEET_OPTIONS",        $cptCst++);
/** SIGADMIN : serveurs des projets */                   define("ALK_SHEET_SERVER",         $cptCst++);

/** GISADMIN : onglet de gestion des cartes */           define("ALK_SHEET_CARTE",          $cptCst++);
/** GISADMIN : onglet de gestion des fonds de plan */    define("ALK_SHEET_FDP",            $cptCst++);
/** GISADMIN : onglet de gestion des vignettes */        define("ALK_SHEET_VIGNETTE",       $cptCst++);
/** GISADMIN : onglet convertisseur*/                    define("ALK_SHEET_CONV",           $cptCst++);
/** GISADMIN : gestion des accès SGBD*/                  define("ALK_SHEET_SGBD",           $cptCst++);

/** BDALK : onglet table */                              define("ALK_SHEET_TABLE",          $cptCst++);
/** BDALK : onglet requete */                            define("ALK_SHEET_QUERY",          $cptCst++);

/** onglet rss */                                        define("ALK_SHEET_RSS",            $cptCst++);

/** Onglet Commentaire */                                define("ALK_SHEET_COMMENT",        $cptCst++);
/** Onglet Autorisation */                               define("ALK_SHEET_AUTORISATION",   $cptCst++);
/** Onglet Moderation */                                 define("ALK_SHEET_MODERATION",     $cptCst++);
/** Onglet Dictionnaire */                               define("ALK_SHEET_DICTIONNAIRE",   $cptCst++);
/** Onglet Cadastre - Territoire */                      define("ALK_SHEET_TERRITOIRE",     $cptCst++);
/** Onglet Cadastre - Compétence */                      define("ALK_SHEET_COMPETENCE",     $cptCst++);
/** Onglet Cadastre - Majic2 */                          define("ALK_SHEET_MAJIC2",         $cptCst++);
/** Onglet Cadastre - Edigeo */                          define("ALK_SHEET_EDIGEO",         $cptCst++);
/** Onglet Cadastre - Servitude */                       define("ALK_SHEET_SERVITUDE",      $cptCst++);


/** Onglet DISP ETAB */                                 define("ALK_SHEET_ETAB",            $cptCst++);
/** Onglet DISP SPIP */                                 define("ALK_SHEET_DISP",            $cptCst++);
/** Onglet GIS VIEWER */                                define("ALK_SHEET_VIEWER",          $cptCst++);
/** Onglet GIS TILE */                                  define("ALK_SHEET_TILE",            $cptCst++);
/** Onglet GIS PRINT */                                 define("ALK_SHEET_PRINT",           $cptCst++);
/** Onglet GIS GEOLOC */                                define("ALK_SHEET_GEOLOC",          $cptCst++);
/** Onglet GIS GEOLOC - Admin */												define("ALK_SHEET_ADM_GEOLOC",		$cptCst++);

/** Onglet Internet : traitements internet */           define("ALK_SHEET_INTERNET",        $cptCst++);

/** Onglet O2I INVEST */                                define("ALK_SHEET_INVEST_BRETONS",  $cptCst++);
/** Onglet O2I INVEST */                                define("ALK_SHEET_INVEST_ETRANGER", $cptCst++);
/** Onglet O2I INVEST */                                define("ALK_SHEET_INVEST_RECHERCHE_ETRANGER", $cptCst++);
/** Onglet O2I INVEST */                                define("ALK_SHEET_INVEST_RECHERCHE_BRETON", $cptCst++);

/** Onglet liste des appretance */                      define("ALK_SHEET_LIST_APPARTENANCE", $cptCst++);

if( !defined("ALK_SHEET_TASK") ) {
/** Onglet tache planifiée */                      			define("ALK_SHEET_TASK",            $cptCst++);
}
/** Onglet JMAPLINK Administration des alertes */       define("ALK_SHEET_ALERTGEO",     		$cptCst++);
/** Onglet JMAPLINK Administration des alertes */       define("ALK_SHEET_ALERTS",          $cptCst++);
/** Onglet JMAPLINK Administration des sociétés */      define("ALK_SHEET_FIRMS",           $cptCst++);
/** Onglet JMAPLINK Administration de tous les wirtracks */     define("ALK_SHEET_ALLWIRTRACKS", $cptCst++);
/** Onglet JMAPLINK Administration des wirtracks du site */     define("ALK_SHEET_WIRTRACKS", $cptCst++);
/** Onglet Graphe                  */                   define("ALK_SHEET_GRAPHE",          $cptCst++);
/** Onglet source de données         */                 define("ALK_SHEET_DATA",            $cptCst++);
/** Onglet Gestion des sites        */                  define("ALK_SHEET_SITE",            $cptCst++);
/** Onglet Gestion des boites à lettres       */        define("ALK_SHEET_BAL",             $cptCst++);
/** Onglet Gestion des base de données        */        define("ALK_SHEET_BDD",             $cptCst++);
/** Onglet Gestion des comptes FTP            */        define("ALK_SHEET_FTP",             $cptCst++);
/** Onglet Gestion des comptes Affaires GestProj */     define("ALK_SHEET_AFFAIRE",         $cptCst++);
/** Onglet Carto JMAPLINK */                            define("ALK_SHEET_CARTO",           $cptCst++);

/** Fonctionnalités mobiles */                          define("ALK_SHEET_MOBILE",          $cptCst++);
/** paramétrage des constantes */                       define("ALK_SHEET_PARAMETRE",       $cptCst++);
/** FileManager **/                                     define("ALK_SHEET_FILEMANAGER",     $cptCst++);

/** EVAL : onget charte */                              define("ALK_SHEET_CHARTE",          $cptCst++);
/** EVAL : onget axe */                                 define("ALK_SHEET_AXE",             $cptCst++);
/** EVAL : onget orientation opérationnelle */          define("ALK_SHEET_OP",              $cptCst++);
/** EVAL : onget projet */                              define("ALK_SHEET_PROJET",          $cptCst++);
/** EVAL : onget budget */                              define("ALK_SHEET_BUDGET",          $cptCst++);
/** EVAL : onget note */                                define("ALK_SHEET_NOTE",            $cptCst++);

/** onglet SMS Admin **/
/** onglet Liste des utilisateurs*/                     define("ALK_SHEET_SMSUSERS",    $cptCst++);
/** onglet liste des expéditeurs */                     define("ALK_SHEET_QUOTA",  $cptCst++);

/** onglet jmaplink itinéraires */                      define("ALK_SHEET_ITINERAIRES", $cptCst++);
/** onglet jmaplink itinéraires carto */                define("ALK_SHEET_ITINERAIRES_CARTO", $cptCst++);

/** onglet jmaplink sdi */                              define("ALK_SHEET_ALLFLEET", $cptCst++);
/** onglet jmaplink sdi */                              define("ALK_SHEET_FLEET", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_NAVIGATION", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_HISTORIQUE", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_SYNTHESE", $cptCst++);

/** onglet jmaplink sdi */								define("ALK_SHEET_AJAX_SEARCH", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_AJAX_LIST", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_AJAX_MAP", $cptCst++);
/** onglet jmaplink sdi */								define("ALK_SHEET_AJAX_GRAPH", $cptCst++);

/** onglet Application Sécurité */				define("ALK_SHEET_APPLICATION_SECURITY", $cptCst++);
/** onglet Application Maintenance */			define("ALK_SHEET_APPLICATION_MAINTENANCE", $cptCst++);
/** onglet Application Productivité */		define("ALK_SHEET_APPLICATION_PRODUCTIVITY", $cptCst++);

/** popup pour afficher informations balises */		define("ALK_SHEET_INFOS_BALISE", $cptCst++);
/** popup pour afficher informations balises */		define("ALK_SHEET_ADD_ALERT", $cptCst++);
/** popup pour afficher informations pois */		define("ALK_SHEET_INFOS_POI", $cptCst++);
/** popup pour afficher informations remontees poi */		define("ALK_SHEET_INFOS_POI_REMONTEE", $cptCst++);
/** popup pour afficher la legende */		define("ALK_SHEET_LEGEND", $cptCst++);

/** popup Geoloc : interface des lieux connus */	define("ALK_SHEET_LIEUX_CONNUS", $cptCst++);
																									define("ALK_SHEET_MODIF_POI", $cptCst++);
																									define("ALK_SHEET_IMPORT_POI", $cptCst++);
																									define("ALK_SHEET_ADMIN_POI", $cptCst++);

/** onglet jmaplink sdi commun flotte et balise */ define("ALK_SHEET_FLEETWIRTRACK", $cptCst++);
/** subSheet balise et flotte différente */				 define("ALK_SHEET_FORM_FLEET", $cptCst++);
														                       define("ALK_SHEET_FORM_WIRTRACK", $cptCst++);
														                       define("ALK_SHEET_FORM_IMPORT_POI", $cptCst++);
														                       define("ALK_SHEET_FORM_RFTRACK", $cptCst++);
														                       define("ALK_SHEET_FORM_SENSOR_CONTROL", $cptCst++);
														
/** onglet import export balises */						     define("ALK_SHEET_IMPORT_EXPORT_WIRTRACKS", $cptCst++);
/** Import de balises */								           define("ALK_SHEET_IMPORT_BALISE", $cptCst++);
/** Onglet RAZ */                                  define("ALK_SHEET_FORM_RAZ", $cptCst++);		
/** Onglet organisation geoderis */                define("ALK_SHEET_ORGANISATION", $cptCst++);

// ressource type
define("RESSTYPE_JMAPLINK_FLOTTE", 1);
define("RESSTYPE_JMAPLINK_ALERTE", 3);		
define("RESSTYPE_GEOLOC", 4);	
define("RESSTYPE_JMAPLINK_MODELEBALISE", 5);		
define("RESSTYPE_JMAPLINK_POI", 6);	
define("RESSTYPE_JMAPLINK_MODELEPOI", 7);

// role type
define("ROLE_JMAPLINK_NO_RIGHT", 0);

define("ROLE_JMAPLINK_FOLLOWING" , 11); 
define("ROLE_JMAPLINK_CREATION"	 , 12);
define("ROLE_JMAPLINK_EDITION"	 , 13);
define("ROLE_JMAPLINK_DELETION"	 , 14);
define("ROLE_JMAPLINK_UNDELETION", 15);
define("ROLE_JMAPLINK_MANAGEMENT", 16);
define("ROLE_JMAPLINK_MASTER"	 , 17);

define("ROLE_GEOLOC_FOLLOWING"	 , 21);
define("ROLE_GEOLOC_CREATION"		 , 22);
define("ROLE_GEOLOC_EDITION"	 , 23);
define("ROLE_GEOLOC_DELETION"	 , 24);
define("ROLE_GEOLOC_UNDELETION", 25);
define("ROLE_GEOLOC_MANAGEMENT", 26);
define("ROLE_GEOLOC_MASTER"	 	 , 27);

// type de navigateur
define("ALK_NAV_IE6",  "IE6");
define("ALK_NAV_IE7",  "IE7");
define("ALK_NAV_IE8",  "IE8");
define("ALK_NAV_IE9",  "IE9"); 
define("ALK_NAV_IE10", "IE10"); 
define("ALK_NAV_IE11", "IE11"); 

define("ALK_NAV_FF1",  "FF1");
define("ALK_NAV_FF2",  "FF2");
define("ALK_NAV_FF3",  "FF3");
define("ALK_NAV_FF35", "FF35");
define("ALK_NAV_FF36", "FF36");
define("ALK_NAV_FF4",  "FF4");
define("ALK_NAV_FF5",  "FF5");
define("ALK_NAV_FF6",  "FF6");
define("ALK_NAV_FF7",  "FF7");
define("ALK_NAV_FFx",  "FFx");

define("ALK_NAV_CHROME7",  "CH7");
define("ALK_NAV_CHROME8",  "CH8");
define("ALK_NAV_CHROME9",  "CH9");
define("ALK_NAV_CHROME10", "CH10");
define("ALK_NAV_CHROMEx",  "CHx");

define("ALK_NAV_SAFARI4",  "SA4");
define("ALK_NAV_SAFARI5",  "SA5");
define("ALK_NAV_SAFARIx",  "SAx");

define("ALK_NAV_OPERAx",  "OPx");
define("ALK_NAV_OTHER",   "OTHER");
define("ALK_NAV_CLI",     "CLI"); // phpcli => famille Firefox et css ALK

// famille de navigateurs
define("ALK_NAVFAM_IEXPLORER", "IE");
define("ALK_NAVFAM_FIREFOX",   "FF");
define("ALK_NAVFAM_CHROME",    "CH");
define("ALK_NAVFAM_SAFARI",    "SA");
define("ALK_NAVFAM_OPERA",     "OP");
define("ALK_NAVFAM_OTHER",    "AUTRE");

// famille de navigateurs pour css
define("ALK_NAVCSS_FF",  "CSS_FF");
define("ALK_NAVCSS_IE",  "CSS_IE");
define("ALK_NAVCSS_IE7", "CSS_IE7");
define("ALK_NAVCSS_IE6", "CSS_IE6");

// niveau de html
define("ALK_NAVHTML4", "html4");
define("ALK_NAVHTML5", "html5");

/** 
 * Detection du navigateur client 
 * Ce fait maintenant par AlkFactory::getNavigator() 
 * dans le lib_session.php après chargement de AlkFactory.class.php
 * 
 * Cette méthode définit les constantes suivants :
 *   ALK_NAV, ALK_NAVFAM, ALK_NAVCSS et ALK_NAVHTML 
 */

/** 
 * Liste des fichiers non concernés par la vérif de connexion
 * Attention : les fichiers inscrits dans ce tableau se doivent d'initialiser une session utilisateur 
 */
if( !isset($tabFileSession) ) {
  $tabFileSession = array("/alkanet/alkanet_verif.php",
                          "/alkanet_verif.php");
}

/** 
 * tableau de déclinaison des images 
 * 
 * Ce tableau peut être déclaré au préalable dans le fichier app_conf en créant le tableau $tabImageSize
 * Seul les fichiers *.jpg, *.gif et *.png peuvent être redimendionnés
 * A chaque fois qu'une image va être uploadée elle va être déclinée en autant de version
 * ex: l'image uplopadée se nomme image.jpg
 *     elle va être déclinée en image_xxsmall.jpg, image_xsmall.jpg, image_small.jpg, image_medium.jpg et image_large.jpg 
 *     les proportions de l'image sont conservées et l'application essaye de générer l'image la plus grande possible 
 *     en fonction des dimensions renseignées
 */ 
if( !isset($tabImageSize) ) {
  $tabImageSize = array("xxsmall" => array("width"=> "50", "height"=> "50"),
                        "xsmall"  => array("width"=>"100", "height"=>"100"),
                        "small"   => array("width"=>"150", "height"=>"150"),
                        "medium"  => array("width"=>"300", "height"=>"300"),
                        "large"   => array("width"=>"600", "height"=>"600"));
}            

if( file_exists(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."gedit") && is_dir(ALK_ALKANET_ROOT_PATH.ALK_ROOT_MODULE."gedit") ) {
  require_once(ALK_ALKANET_ROOT_PATH.ALK_ROOT_LIB."app_conf_gedit.php");
}
?>