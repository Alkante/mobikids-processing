<?php

/*licence/

Module écrit, supporté par la société Alkante SAS <alkante@alkante.com>

Nom du module : Alkanet::Module::JMaplink
Module JMaplink.
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

include_once(ALK_ALKANET_ROOT_PATH."lib/app_conf_alkanet.php");
include_once(ALK_ALKANET_ROOT_PATH."classes/pattern/alkfactory.class.php");
include_once(ALK_ALKANET_ROOT_PATH."scripts/util.php");

/**
 * @package Alkanet_Module_JMaplink
 * @class AlkAlertMonitor
 *
 * Classe chargée de traiter les détections d'alerte à l'issue
 * de chaque insertion des données en historique
 * //obedel : doit être proche du patron visiteur...
 */
class AlkAddressLocatorAst
{
	public $dbConn = "";

	protected $_fileLogger = null;
	protected $_logOn = false;
  protected $tableName;

	/**
	 * Constructeur par défaut
	 * @param dbConn Classe de connection à la base
	 * @param fileLogger Classe de log
	 */
	public function __construct(&$dbConn, &$fileLogger = null, $shortAddressMode=false, $tableName = 'positions', $latField='lat', $lonField='lon', $addressField='address', $gidField='gid')
	{
		$this->dbConn = $dbConn;
    
    $this->shortAddressMode = $shortAddressMode;
    $this->tableName = $tableName;
    $this->latField = $latField;
    $this->lonField = $lonField;
    $this->addressField = $addressField;
    $this->gidField = $gidField;
    
    $this->fileLogger = $fileLogger;
  }

	/**
	 * Méthode de traitement des données venant d'être reçues
	 * @param pos_id : identifiant de la remontée
	 * @return boolean : true si le traitement s'est bien passé, false sinon
	 */
	public function handle($pos_id){
    $res = false;
		$pos = $this->getPositionFromRemontee($pos_id);
		if ($pos["lat"] != null && $pos["lon"] != null){
		  $address = $this->getAddressFromLocation($pos);
		  $res = $this->updateField($pos_id, $this->tableName, $this->gidField, $this->addressField, $address);
		}
		return $res;
	}
  
  public function run() {
    $res = true;
    $strSql = "SELECT $this->gidField as id FROM $this->tableName";
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
      $id = $dr->getValueName('id');
      $res = $this->handle($id);
      $this->fileLogger->write("[". __CLASS__ ."] Processing message $id : $res");				
    }
  }
	
	private function getAddressFromLocation($pos){
	    $address = null;
	    if ($stream = fopen(ALK_AST_NOMINATIM_URL . "?format=json&lat=".$pos['lat']."&lon=".$pos['lon']."&zoom=18&addressdetails=1&accept-language=fr", 'r')) {
	        $address = stream_get_contents($stream);	    
	        fclose($stream);	        
 	        $res = json_decode($address,true);
 	        $address = $this->shortAddressMode ? (isset($res["display_name"]) ? $res["display_name"] : null) : $address;
	        $address = str_replace("'","''",$address);
	    }
	    if ($address === null)
	        $address = "UNKNOWN ADDRESS";
	    return $address;
    }

  private function getPositionFromRemontee($pos_id){
    $pos = null;
    $strSql = "select %LAT% as lat,%LON% as lon from %TABLE% where %GID%=%POS_ID%";
    $strSql = str_replace(
      array('%LAT%', '%LON%', '%TABLE%', '%GID%', '%POS_ID%'),
      array($this->latField, $this->lonField, $this->tableName, $this->gidField, $pos_id),
      $strSql);

    $ds = $this->dbConn->initDataSet($strSql);
    if ($dr = $ds->getRowIter()){
        $lat = $dr->getValueName("lat");
        $lon = $dr->getValueName("lon");
        $pos = Array("lat"=>$lat,"lon"=>$lon);            
    }        
    return $pos;
  }
    
  protected function updateField($pos_id, $tablename, $gidField, $fieldname, $value) {
    $res = false;
    $this->dbConn->setSchema('public');
    //looking if a row already exists for pos_id
    if ($this->checkExistingRow($tablename, $gidField, $pos_id)){

        $strSQL = "UPDATE
        $tablename
        SET $fieldname = '$value'
        WHERE $gidField = $pos_id";

        $res = $this->dbConn->executeSQL($strSQL,false);
    }
  return $res;
  }
    
  protected function checkExistingRow($tablename, $gidField, $pos_id) {
    //looking if a row already exists for pos_id
    $res = false;
    $strSQL = "SELECT * FROM $tablename WHERE $gidField = $pos_id LIMIT 1";

    $ds = $this->dbConn->initDataset($strSQL);
        while($dr = $ds->getRowIter()){
        $res = true;
    }

    return $res;
  }
}

?>
