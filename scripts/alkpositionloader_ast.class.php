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
include_once(ALK_ALKANET_ROOT_PATH."scripts/alktrackingmonitor_ast.class.php");
include_once(ALK_ALKANET_ROOT_PATH."scripts/alkaddresslocation_ast.class.php");

/**
 * @package Alkanet_Module_JMaplink
 * @class AlkTrackingMonitorErcoGener
 *
 * Classe chargée de traiter les remontées des balises ErcoGener
 * pour la segmentation en trajet lors 
 * de chaque insertion des données en historique
 */
class AlkPositionLoaderAst
{
  public $dbConn = "";
  protected $fileLogger = null;
  
  protected $params_defaut_json = '{
    "TRACKER_GPS_PERIOD": 5,  
    "MOBILITY_SEGMENT_DURATION": 20 
  }';

  protected $inferTransportModeEnabled = false;

  /**
   * Constructeur par défaut
   * @param dbConn Classe de connection à la base
   * @param fileLogger Classe de log
   */
  public function __construct(&$dbConn, $data_id=-1, $params = null, $dataDir=null, $outDir=null, &$fileLogger = null)
  {
    $this->dbConn = $dbConn;
    $this->dataDir = $dataDir==null ? '/' : $dataDir; 
    $this->fileLogger = $fileLogger;
    $this->outDir = $outDir==null ? './out' : $outDir; 
    $this->data_id = $data_id;
    $this->params = $params!=null ? $params : json_encode($this->params_defaut_json, true);

    $this->inferTransportModeEnabled  = isset($this->params["TRANSPORTMODE_DETECTION_ENABLED"]) && $this->params["TRANSPORTMODE_DETECTION_ENABLED"];
    $this->correctActivityInfoEnabled = isset($this->params["CORRECTNESS_INFOFILE_ENABLED"]) && $this->params["CORRECTNESS_INFOFILE_ENABLED"];
  }

  public function handle($filename, $fileprefix)
  {
    $res = true;
    
    $res = $this->launchprocess('loadDataInDb', array($fileprefix, $this->data_id), 'Loading data in DB');
    $res = $res && $this->processData($this->data_id);
    
    return $res;
  }
  
  public function test()
  {
    $res = true;
    $data_id = $this->data_id;
/*    
    $res = $this->launchprocess('loadDataInDb', array($fileprefix, $this->data_id), 'Loading data in DB');
    // Step 1
    // computeSegment
    $res = $res && $this->launchProcess('computeSegments', array($data_id), "Computing segments");
    
    // Step 2
    // attach main activity info
    $res = $res && $this->launchProcess('attachActivityInfo', array($data_id), "Attaching activity main info");

    // Step 2 b
    // attach main activity info
    $res = $res && $this->launchProcess('attachMobilityInfo', array($data_id), "Attaching mobility info");
    
    // Step 2 c
    // extracting Poi Info
    $res = $res && $this->launchProcess('computePoiInfo', array($data_id), "Computing Poi info");
    
    // Step 2 d
    // Clusters ?
    $res = $res && $this->launchProcess('computeLieux', array($data_id), "Extracting lieux");
    
    $res = $res && $this->launchProcess('computeTrajets', array($data_id), "Computing trajets");
*/
    // Step 4
    // isolate lieux and micro-trajets
    $res = $res && $this->launchProcess('refineLieux', array($data_id), "Refining Lieux");
    
    // Step 5 
    // Attach Address
    $res = $res && $this->launchProcess('AttachAdress', array($data_id), "Resolving addresses");
    
    // Step 6
    // adjusting trajets
    $res = $res && $this->launchProcess('adjustTrajets', array($data_id), "Adjusting trajets");

    
    $res = $res && $this->launchProcess('processMicroStop', array($data_id), "Processing microStops...");
    
    // Step 7b
    // post-process on micro-arrets : attaching info for filtering
    $res = $res && $this->launchProcess('postProcessMicroStop', array($data_id), "Post Processing microStops...");

    // Step 8
    // building roadmap
    $res = $res && $this->launchProcess('buildRoadmap', array($data_id), "Building roadmap");
    
    // Step 9
    // mapping lieux with Poi
    $res = $res && $this->launchProcess('mapLieuWithPoi', array($data_id), "Mapping Lieu with Poi");
    
    // Step 10
    // exporting roadmap
    $res = $res && $this->launchProcess('exportToJson', array($data_id), "Exporting to JSON");
    
    //$res = $res && $this->launchProcess('inferTransportMode', array($data_id), "Infering transport mode");
    

    return $res;
  }





  protected function processData($data_id) {
    $res = true;
    
    // Step 0
    // correction decalage fichier info
    if ($this->correctActivityInfoEnabled) {
      $res = $res && $this->launchProcess('correctActivityInfo', array($data_id), "Correcting info time offsets");
    }

    // Step 1
    // computeSegment
    $res = $res && $this->launchProcess('computeSegments', array($data_id), "Computing segments");
    
    // Step 2
    // attach main activity info
    $res = $res && $this->launchProcess('attachActivityInfo', array($data_id), "Attaching activity main info");

    // Step 2 b
    // attach main activity info
    $res = $res && $this->launchProcess('attachMobilityInfo', array($data_id), "Attaching mobility info");
    //exit(-1);
    // Step 2 c
    // extracting Poi Info
    $res = $res && $this->launchProcess('computePoiInfo', array($data_id), "Computing Poi info");
    
    // Step 2 d
    // Clusters ?
    $res = $res && $this->launchProcess('computeLieux', array($data_id), "Extracting lieux");
    
    // Step 3
    // compute trajets
    $res = $res && $this->launchProcess('computeTrajets', array($data_id), "Computing trajets");
    //die();

    
    // Step 4
    // isolate lieux and micro-trajets
    $res = $res && $this->launchProcess('refineLieux', array($data_id), "Refining Lieux");
    
    // Step 5 
    // Attach Address
    $res = $res && $this->launchProcess('AttachAdress', array($data_id), "Resolving addresses");
    
    // Step 6
    // adjusting trajets
    $res = $res && $this->launchProcess('adjustTrajets', array($data_id), "Adjusting trajets");
    
    // Step 6a
    // extracting transportation info
    if ($this->inferTransportModeEnabled) {
      $res = $res && $this->launchProcess('inferTransportMode', array($data_id), "Infering transportation mode");
    }

    // Step 7a
    // process of micro-arrets : merging of micro-arret that are nearby
    // converting ma into lieux...
    $res = $res && $this->launchProcess('processMicroStop', array($data_id), "Processing microStops");
    
    // Step 7b
    // post-process on micro-arrets : attaching info for filtering
    $res = $res && $this->launchProcess('postProcessMicroStop', array($data_id), "Post Processing microStops");

    // Step 8
    // building roadmap
    $res = $res && $this->launchProcess('buildRoadmap', array($data_id), "Building roadmap");
    
    // Step 9
    // mapping lieux with Poi
    $res = $res && $this->launchProcess('mapLieuWithPoi', array($data_id), "Mapping Lieu with Poi");
    
    // Step 10
    // exporting roadmap
    $res = $res && $this->launchProcess('exportToJson', array($data_id), "Exporting to JSON");
    
    return $res;
  }
  
  
   protected function initEnquete($filename) {
    $seq_id = $this->dbConn->getSeqId('enquetes_enquete_id_seq');
    
    $strSql = "INSERT INTO enquetes(enquete_id, enquete_trackfilename) VALUES (".$seq_id .",'". $this->dbConn->analyseSql($filename) ."')";
    $res = $this->dbConn->executeSql($strSql, false);
    
    return $res ? $seq_id : null;
  }
  
  /**
   * returns info about db connection
   * @return array
   */
  protected function getDbInfo() {
    $res = null;
    if (defined('ALK_PDO_DRIVERS_CONF')) {
      $info = json_decode(ALK_PDO_DRIVERS_CONF, true);
      if ($info)
        $res = $info['default'];
    }
    return $res;
  }

  /**
   * load a CSV file into a table using a extern call to ogr2ogr
   * @param file string the full path to the file
   * @param table string the name of the table (can use schema like schema.tablename) 
   * @param mode string the name of the ogr creation mode : overwrite (default), append, update
   * @param fieldStructure array of fieldname=> ogrDbType, to cast CSV field to appropriate db type
   * @return string the exit code of the exec command
   */
  protected function ogrLoadFileIntoDb($file, $table, $mode = "overwrite", $fieldStructure=array()) {
    $res = null;
    $dbInfo = $this->getDbInfo();
    $pgString = '"PG:dbname=' . $dbInfo['dsn']['dbname'] . 
      ' host=' . $dbInfo['dsn']['host'] . 
      ' port=' . $dbInfo['dsn']['port'] . 
      ' user=' . $dbInfo['db_user'] .
      ' password=' . $dbInfo['db_password'] .
      ' active_schema=' . $dbInfo['db_schema'] . 
      '"'; 
    $nln = '-nln public.' . $table;
    $ogrSql = '';
    if (count($fieldStructure)>0) {
      $fieldsDef = array();
      foreach($fieldStructure as $field => $type) {
        array_push($fieldsDef,"CAST($field as $type)");
      }
      $layerName = basename($file,'.csv'); 
      $ogrSql = "-sql 'select " . implode(',', $fieldsDef) . " from ".'"'.$layerName.'"'."'";
    }
    $ogrmode = '-'.$mode; 
    $OGR_CMD = 'ogr2ogr ' . $ogrmode . ' -f PostgreSQL ' . $pgString . ' ' . $file . ' ' . $nln . ' ' . $ogrSql;
    $output = array();
    //echo $OGR_CMD . "\n";
    exec($OGR_CMD, $output, $res);
    return $res;
  }

  protected function loadDataInDb($fileprefix, $data_id) {
    $gpsFields = array(
      "datetime" => "timestamp",
      "timestamp" => "bigint", 
      "lat" => "float", 
      "lon" => "float", 
      "nbsat" => "integer", 
      "alt" => "integer", 
      "hdop" => "float", 
      "cap" => "integer", 
      "vit" => "integer"
    );
    $SQL_GPS_IMPORT_TEMPLATE = array("DROP TABLE IF EXISTS %TABLENAME%",
    "CREATE TABLE %TABLENAME% AS SELECT * FROM positions_tpl LIMIT 0",
    "INSERT INTO %TABLENAME%(\"time\",\"timestamp\", lat, lon, nbsat, alt, hdop, cap, vit, enq_id) 
      SELECT \"datetime\",\"timestamp\", lat, lon, nbsat, alt, hdop, cap, vit, %DATA_ID% FROM %TABLENAME%_temp",
    "DROP TABLE IF EXISTS %TABLENAME%_temp",
    "ALTER TABLE %TABLENAME% DROP COLUMN gid",
      "ALTER TABLE %TABLENAME% DROP COLUMN the_geom",
      "CREATE TABLE %TABLENAME%_temp2 AS
        SELECT 
          row_number() OVER() AS gid,
          ST_GeometryFromText('POINT('|| lon || ' ' || lat || ')',4326) as the_geom,
          rank() OVER (partition BY \"time\" ORDER BY nbsat DESC) AS num,
          *
        FROM
          %TABLENAME%
        ORDER BY time asc;",
      "DELETE FROM %TABLENAME%_temp2 where num>1;",
      "ALTER TABLE %TABLENAME%_temp2 DROP COLUMN \"num\"",
      "DROP TABLE %TABLENAME%",
      "ALTER TABLE %TABLENAME%_temp2 RENAME TO %TABLENAME%;",
      "SET timezone to 'localtime'",
      "UPDATE %TABLENAME% "
      . "SET time = (time AT TIME ZONE 'UTC')::timestamp without time zone",
      "ALTER TABLE %TABLENAME% ADD CONSTRAINT pk_%TABLENAME% PRIMARY KEY (gid)",
      "CREATE INDEX idx_geom_%TABLENAME% ON %TABLENAME% USING gist (the_geom)",
      "CREATE INDEX idx_time_%TABLENAME% ON %TABLENAME% USING btree (\"time\")",
      "CREATE INDEX idx_gid_%TABLENAME% ON %TABLENAME% USING btree (gid)",
      "CREATE INDEX idx_mb_%TABLENAME% ON %TABLENAME% USING btree (mobility_class)",
      "CREATE INDEX idx_msginfo_%TABLENAME% ON %TABLENAME% USING btree (msg_info)",
      "CREATE INDEX idx_timestamp_%TABLENAME% ON %TABLENAME% USING btree (\"timestamp\")"
      );
      
    $infoFields = array(
      "msg" => "character(255)",
      "datetime" => "timestamp"
    );  
    $SQL_INFO_IMPORT_TEMPLATE = array(
      "DROP TABLE IF EXISTS %TABLENAME%",
      "CREATE TABLE %TABLENAME% (LIKE infos_tpl INCLUDING ALL)",
      "INSERT INTO %TABLENAME%(msg, \"time\", enq_id) 
        SELECT msg, \"datetime\", %DATA_ID% FROM %TABLENAME%_temp",
      "DROP TABLE IF EXISTS %TABLENAME%_temp",
      "SET timezone to 'localtime'",  
      "UPDATE %TABLENAME% "
      . "SET time = (time AT TIME ZONE 'UTC')::timestamp without time zone",
      "CREATE INDEX idx_time_%TABLENAME% ON %TABLENAME% USING btree (\"time\")"
    );
    
    $mobilityFields = array(
      "segid" => "bigint", 
      "stepcount" => "integer", 
      "activitylevel" => "float", 
      "threshold1" => "float", 
      "threshold2" => "float",
      "threshold3" => "float",
      "threshold4" => "float",
      "time" => "bigint"
    );  
    $SQL_MOBILITY_IMPORT_TEMPLATE = 
      $this->params["MOBILITY_FILE_ERROR"]==false ?
    array(
      "DROP TABLE IF EXISTS %TABLENAME%",
      "CREATE TABLE %TABLENAME% (LIKE mobility_tpl INCLUDING ALL)",
      "INSERT INTO %TABLENAME%(segid, stepcount, activitylevel, threshold1, threshold2, threshold3, threshold4, \"timestamp\", enq_id) 
        SELECT segid, stepcount, activitylevel, threshold1, threshold2, threshold3, threshold4, \"time\"/1000, %DATA_ID% FROM %TABLENAME%_TEMP",  
      "DROP TABLE %TABLENAME%_TEMP",
      "UPDATE %TABLENAME% 
          SET time = to_timestamp(\"timestamp\")::timestamp without time zone",
      "UPDATE %TABLENAME% 
          SET mobilityclass = CASE 
            WHEN threshold1=0 AND threshold2=0 AND threshold3=0 AND threshold4=0 THEN 0 
            WHEN threshold1<100 THEN 1
            WHEN threshold1=100 AND threshold2<100 THEN 2
            WHEN threshold1=100 AND threshold2=100 AND threshold3<100 THEN 3
            WHEN threshold1=100 AND threshold2=100 AND threshold3=100 AND threshold4<100 THEN 4
            WHEN threshold1=100 AND threshold2=100 AND threshold3=100 AND threshold4=100 THEN 5
            ELSE -1
          END",
      "CREATE INDEX idx_timestamp_%TABLENAME% ON %TABLENAME% USING btree (\"timestamp\")"
    ) :
    array(
      "DROP TABLE IF EXISTS %TABLENAME%",
      "CREATE TABLE %TABLENAME% (LIKE mobility_tpl INCLUDING ALL)");
    ;



    $gpsFile   = $this->dataDir . '/' . $fileprefix . '_gps.csv';
    $infoFile  = $this->dataDir . '/' . $fileprefix . '_info.csv';
    $mobilityFile = $this->dataDir . '/' . $fileprefix . '_mobility.csv';

    if (!$this->checkFileExists($gpsFile) 
      || !$this->checkFileExists($infoFile)
      //|| !$this->checkFileExists($mobilityFile)
      )
      $this->stop();
    
    // importing gps file
    $gpsTablename = "positions_" . $data_id;
    $importGps = $this->ogrLoadFileIntoDb($gpsFile, $gpsTablename."_TEMP", 'overwrite', $gpsFields);   
    if ($importGps!=0)
      $this->stop();
    $sqlCommands = str_replace('%DATA_ID%', $data_id, str_replace('%TABLENAME%', $gpsTablename, $SQL_GPS_IMPORT_TEMPLATE));
    $res = $this->execSqlCommands($sqlCommands);

    // importing info file
    $infoTablename = "infos_" . $data_id;
    $importInfo = $this->ogrLoadFileIntoDb($infoFile, $infoTablename."_TEMP", 'overwrite', $infoFields);   
    if ($importInfo!=0)
      $this->stop();
    $sqlCommands = str_replace('%DATA_ID%', $data_id, str_replace('%TABLENAME%', $infoTablename, $SQL_INFO_IMPORT_TEMPLATE));
    $res = $res && $this->execSqlCommands($sqlCommands);

    // importing mobility file
    $mobilityTablename = "mobility_" . $data_id;
    if ($this->params["MOBILITY_FILE_ERROR"]==false) {
      $importMobility = $this->ogrLoadFileIntoDb($mobilityFile, $mobilityTablename."_TEMP", 'overwrite', $mobilityFields);   
      if ($importMobility!=0)
        $this->stop();
    }
    $sqlCommands = str_replace('%DATA_ID%', $data_id, str_replace('%TABLENAME%', $mobilityTablename, $SQL_MOBILITY_IMPORT_TEMPLATE));
    $res = $res && $this->execSqlCommands($sqlCommands);  
    
    return $res;
  }
  
  // correction decalage fichier info
  protected function correctActivityInfo($data_id) { 
    $SQL_CORRECT_ACTIVITY = array(
      "-- correction de l'avance de timer du gps_1 par rapport au ttf
      update infos_%ID% info
      set time = correctness.min_time
      from
        (select *, min(time) over (order by gid desc) as min_time from infos_%ID% order by gid asc) correctness
      where 
        info.gid = correctness.gid
      and
        info.time>=correctness.min_time
      and 
        info.msg in ('gps_1')",
      "-- correction de l'avance de timer du gps_0/inactv par rapport à gps_1/ttf par rapport au ttf
      update infos_%ID% info
      set time = correctness.min_time - interval '0.9s'
      from
        (select *, min(time) over (order by gid desc) as min_time from infos_%ID% order by gid asc) correctness
      where 
        info.gid = correctness.gid
      and
        info.time>=correctness.min_time
      and 
        info.msg in ('gps_0', 'inactv')");
    $sqlCommands = str_replace('%ID%', $data_id, $SQL_CORRECT_ACTIVITY);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;

  }
















  // compute segments joining positions
  protected function computeSegments($data_id) {
    $SQL_COMPUTE_SEGMENT = array(
      "SELECT ast_positioninfo(gid, 'positions_%ID%') FROM positions_%ID% ORDER BY time ASC"
    );
    
    $sqlCommands = str_replace('%ID%', $data_id, $SQL_COMPUTE_SEGMENT);
    //$this->printSqlCommands($sqlCommands);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }
    
  protected function attachActivityInfo($data_id) {
    $SQL_ATTACH_ACTIVITY_INFO = array(
      "UPDATE positions_%ID% p "
      . "SET msg_info = 
           (select 
              array_to_string(array_agg(msg ORDER BY gid ASC), ',')
            from 
              infos_%ID%
            where 
              (extract('epoch' from time) - extract('epoch' from p.time))<%TRACKER_GPS_PERIOD% and (extract('epoch' from time) - extract('epoch' from p.time))>=0
          ),
          time_info = 
             (select 
                array_to_string(array_agg(time ORDER BY gid asc), ',')
              from 
                infos_%ID%
              where 
                (extract('epoch' from time) - extract('epoch' from p.time))<%TRACKER_GPS_PERIOD% and (extract('epoch' from time) - extract('epoch' from p.time))>=0
              )",
        "UPDATE positions_%ID% p "
        . "set msg_info = CASE WHEN msg_info IS NULL THEN msg_infos ELSE msg_infos  || ',' ||  msg_info END,
               time_info = CASE WHEN time_info IS NULL THEN time_infos ELSE time_infos  || ',' ||  time_info END
        from
          (select 
            array_to_string(array_agg(i.time ORDER BY i.gid ASC), ',') as time_infos, 
            array_to_string(array_agg(i.msg ORDER BY i.gid ASC), ',') as msg_infos, 
            p.gid 
          from 
            infos_%ID% i, 
            (select * from positions_%ID% order by extract('epoch' from time) desc limit 1) p  
          where 
            extract('epoch' from i.time) >= (extract('epoch' from p.time)+%TRACKER_GPS_PERIOD%)
          group by p.gid) t
        where
          p.gid = t.gid",
        "UPDATE positions_%ID% p "
        . "set msg_info = CASE WHEN msg_info IS NULL THEN msg_infos ELSE msg_info || ',' || msg_infos END,
               time_info = CASE WHEN time_info IS NULL THEN time_infos ELSE time_info  || ',' ||  time_infos END
        from
          (select 
            array_to_string(array_agg(i.time ORDER BY i.gid ASC), ',') as time_infos, 
            array_to_string(array_agg(i.msg ORDER BY i.gid ASC), ',') as msg_infos, 
            p.gid 
          from 
            infos_%ID% i, 
            (select * from positions_%ID% order by extract('epoch' from time) asc limit 1) p  
          where 
            extract('epoch' from i.time) < extract('epoch' from p.time)
          group by p.gid) t
        where
          p.gid = t.gid"
        
    );
    
    $sqlCommands = $sqlCommands = str_replace( '%TRACKER_GPS_PERIOD%', $this->params["TRACKER_GPS_PERIOD"],
        str_replace('%ID%', $data_id, $SQL_ATTACH_ACTIVITY_INFO)
      );
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }
  
  /**
   * rattachement de l'information de classe de mobilité 
   * accélérométrique à chaque position
   */
  protected function attachMobilityInfo($data_id) {
    $SQL_ATTACH_MOBILITY_INFO = array(
      "UPDATE positions_%ID% p 
        SET mobility_class = m.mobilityclass
      FROM mobility_%ID% m
      WHERE 
        p.timestamp>(m.timestamp-%MOBILITY_SEGMENT_DURATION%) 
      AND 
        p.timestamp<=m.timestamp"
    );
    
    $sqlCommands = str_replace( '%MOBILITY_SEGMENT_DURATION%', $this->params["MOBILITY_SEGMENT_DURATION"],
        str_replace('%ID%', $data_id, $SQL_ATTACH_MOBILITY_INFO)
      );
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }

  /**
   * fonction d'inférence du mode de transpaort par position 
   * par rapport à la classe accélérométrique 
   * et la vitesse instantanée associée à chaque point
   */
  protected function computeTransportMode($data_id) {




  }







  protected function computeLieux($data_id) {
    $CLUSTER_DURATION_LIMIT = 300;
    $CLUSTER_DISTANCE_LIMIT = 30;
    $CLUSTER_VIT_LIMIT = 3;
    $CLUSTER_DISTANCE = 5;
    $CLUSTER_MIN_COUNT = 5;
    
    $SQL_ATTACH_START_STOP_INFO = array(
      /*"UPDATE positions_%ID% p "
      . "set pos_nearest_start_stop = 
          (select 
            gid 
          from 
            positions_%ID%
          where 
            (msg_info ilike '%inactv%' or msg_info ilike '%gps_%' or msg_info ilike '%ttf%' or msg_info ilike '%stop%' or msg_info ilike '%start%') 
          order by abs(extract(epoch from (p.time-time))::int) asc 
          limit 1)",
      "UPDATE positions_%ID% p "
      . "set duration_nearest_start_stop = 
          (select 
            abs(extract(epoch from (p.time-time))::int) 
          from 
            positions_%ID%
          where 
            gid=p.pos_nearest_start_stop
          ),
          time_nearest_start_stop = 
          (select 
            time 
          from 
            positions_%ID%
          where 
            gid=p.pos_nearest_start_stop
    )",
      "drop table if exists pos_clusters_%ID%",
      "create table pos_clusters_%ID% as 
      SELECT row_number() over () AS id,
        pos_nearest_start_stop,
        ST_NumGeometries(gc),
        gc AS geom_collection,
        ST_Centroid(gc) AS centroid,
        ST_MinimumBoundingCircle(gc) AS circle,
        sqrt(ST_Area(ST_MinimumBoundingCircle(gc)) / pi()) AS radius
      FROM (
        SELECT 
        unnest(ST_ClusterWithin(the_geom, %CLUSTER_DISTANCE%)) gc, pos_nearest_start_stop
        FROM 
        (
        select 
          st_transform(the_geom, 2154) as the_geom, pos_nearest_start_stop 
        from 
          positions_%ID% p 
        where 
          duration_nearest_start_stop=0
        or
      (		vit<=%CLUSTER_VIT_LIMIT% 
        and 
          duration_nearest_start_stop<=%CLUSTER_DURATION_LIMIT% 
        and 
          st_dwithin(st_transform(the_geom, 2154), (select st_transform(the_geom, 2154) from positions_%ID% where gid=p.pos_nearest_start_stop), %CLUSTER_DISTANCE_LIMIT%))
          ) t  
        GROUP BY
          pos_nearest_start_stop
      ) f 
      where ST_NumGeometries(gc)>%CLUSTER_MIN_COUNT%",
      "CREATE INDEX idx_geom_pos_clusters_%ID%  ON pos_clusters_%ID% USING gist(geom_collection)",
      "drop table if exists pos_clusters_aggregate_%ID%",
      "create table pos_clusters_aggregate_%ID% as 
      select
        ca.id,
        ca.the_geom--,
        --(select array_agg(distinct pos_nearest_start_stop) from  pos_clusters_%ID% where st_intersects(circle, ca.the_geom)) as pos_list
      from
        (SELECT 
          row_number() over () AS id,
          st_setsrid(st_buffer(the_geom,0),2154) as the_geom
        FROM
          (select unnest(st_clusterintersecting(circle)) as the_geom from pos_clusters_%ID%) t
        ) ca",
        "CREATE INDEX idx_geom_pos_clusters_aggregate_%ID%  ON pos_clusters_aggregate_%ID% USING gist(the_geom)",*/
        "DROP TABLE IF EXISTS lieux_%ID%",
        "CREATE TABLE lieux_%ID% (LIKE lieux_tpl INCLUDING ALL)",/*
        "INSERT INTO lieux_%ID% (gid, the_geom, lat, lon, origin) 
            SELECT 
              id, the_geom, ST_Y(centroid) as lat, ST_X(centroid) as lon, 'cluster' as origin 
            FROM 
              (SELECT
                *,
                ST_Transform(ST_Centroid(the_geom),4326) as centroid
              FROM
                pos_clusters_aggregate_%ID%
              ) t",*/
          "CREATE INDEX idx_geom_lieux_%ID%  ON lieux_%ID% USING gist(the_geom)",
          "CREATE INDEX idx_id_lieux_%ID%  ON lieux_%ID% USING btree(gid)"

      );
    
    $sqlCommands = str_replace(
      array('%ID%','%CLUSTER_DURATION_LIMIT%', '%CLUSTER_DISTANCE_LIMIT%', '%CLUSTER_VIT_LIMIT%', '%CLUSTER_DISTANCE%', '%CLUSTER_MIN_COUNT%'),
      array($data_id, $CLUSTER_DURATION_LIMIT, $CLUSTER_DISTANCE_LIMIT, $CLUSTER_VIT_LIMIT, $CLUSTER_DISTANCE, $CLUSTER_MIN_COUNT),
      $SQL_ATTACH_START_STOP_INFO);
    //print_r($sqlCommands);
    $res = $this->execSqlCommands($sqlCommands);
    
    return $res;
  }
  
  
  // compute trajets
  protected function computeTrajets($data_id) {
    $SQL_TRAJET_TEMPLATE = array(
      "DROP TABLE IF EXISTS %TABLENAME_TJ%",
      "CREATE TABLE %TABLENAME_TJ% (LIKE trajets_tpl INCLUDING ALL)",
      "DROP TABLE IF EXISTS %TABLENAME_MA%",
      "CREATE TABLE %TABLENAME_MA% (LIKE microarrets_tpl INCLUDING ALL)",
      );
    
    
    $positionTable = 'positions_' .$data_id;
    $trajetTable = 'trajets_' .$data_id;
    $microarretTable = 'microarrets_' .$data_id;
    $lieuTable = 'lieux_' .$data_id;
    $infoTable = 'infos_' .$data_id;
    $sqlCommands = str_replace(
        array('%TABLENAME_TJ%', '%TABLENAME_MA%'),
        array($trajetTable, $microarretTable),
        $SQL_TRAJET_TEMPLATE);
    $res = $this->execSqlCommands($sqlCommands);
    
    
    $trackingMonitor = new AlkTrackingMonitorAst($this->dbConn, $this->fileLogger, $positionTable, $trajetTable, $lieuTable, $microarretTable, $infoTable,
      $this->params);
    $res = $res && $trackingMonitor->run();
    
    return true;
  }
    
  
  // attach address
  protected function AttachAdress($data_id) {
    $res = true;
    /*
    $trajetTable = 'trajets_' .$data_id;
        
    $addressLocatorStart = new AlkAddressLocatorAst(
      $this->dbConn, 
      $this->fileLogger, 
      true,
      $trajetTable,
      'lat_start', 
      'lon_start', 
      'address_start', 
      'gid');
    $res = $addressLocatorStart->run();
    
    $addressLocatorEnd = new AlkAddressLocatorAst(
      $this->dbConn, 
      $this->fileLogger, 
      true,
      $trajetTable,
      'lat_end', 
      'lon_end', 
      'address_end', 
      'gid');
    $res = $res & $addressLocatorEnd->run();
    */
    $addressLocatorLieux = new AlkAddressLocatorAst(
      $this->dbConn, 
      $this->fileLogger, 
      true,
      'lieux_' .$data_id,
      'lat', 
      'lon', 
      'address', 
      'gid');
    $res = $res & $addressLocatorLieux->run();
    
    $addressLocatorMicroarrets = new AlkAddressLocatorAst(
      $this->dbConn, 
      $this->fileLogger, 
      true,
      'microarrets_'.$data_id,
      'lat', 
      'lon', 
      'address', 
      'gid');
    $res = $res & $addressLocatorMicroarrets->run();
    
    
    return true;
  }
  

  protected function refineLieux($data_id) {
    $res = true;
    $CLUSTER_REFINE_LIEU_RADIUS = $this->params["CLUSTER_REFINE_LIEU_RADIUS"]; 
    $SQL_ATTACH_TRAJETS_TO_LIEU = array(
      "UPDATE 
        lieux_%ID% li
      set
        trajets_start = lt.trajets_start,
        trajets_end = lt.trajets_end
      from
      (select 
        l.gid,
        array_to_string(array_agg(distinct t1.gid),',') as trajets_start,
        array_to_string(array_agg(distinct t2.gid),',') as trajets_end   
      from 
        lieux_%ID% l 
      left join 
        trajets_%ID% t1 
      on 
        l.gid=t1.lieu_start
      left join 
        trajets_%ID% t2 
      on 
        l.gid=t2.lieu_end
      group by
        l.gid) lt
      where
        lt.gid=li.gid	"
      );

    $SQL_CLUSTER_LIEUX = array(
      "DROP TABLE IF EXISTS lieux_cluster_%ID%",
      "CREATE TABLE lieux_cluster_%ID% as
      WITH lt AS 
       (SELECT	
          * 
        FROM 
          lieux_%ID%
        WHERE
          (trajets_start IS NOT NULL AND trajets_start!='')
        OR
         (trajets_end IS NOT NULL AND trajets_end!=''))

      SELECT 
        row_number() OVER () as gid,
        ST_CollectionExtract(geom_cluster,3) as the_geom,
        array_agg(l.gid) as lieux,
        string_to_array(string_agg(nullif(trajets_start,''),','),',') as trajets_start,
        string_to_array(string_agg(nullif(trajets_end,''),','),',') as trajets_end
      FROM
        (SELECT	
          unnest(ST_ClusterWithin(st_transform(the_geom,2154), $CLUSTER_REFINE_LIEU_RADIUS)) as geom_cluster 
        FROM 
          lt
       ) c
      LEFT JOIN
        lt l
      ON 
        ST_DWithin(ST_CollectionExtract(geom_cluster,3), st_transform(l.the_geom,2154),0.1)
      GROUP BY
        geom_cluster",
      "DROP TABLE IF EXISTS new_lieux_%ID%",
      "CREATE TABLE new_lieux_%ID% as
      SELECT
        l.*,
        st_x(st_centroid(the_geom)) as lon,
        st_y(st_centroid(the_geom)) as lat,
        greatest(array_length(trajets_start,1), array_length(trajets_end,1))-1 as recurrence,
        ''::text as address
      FROM
        (SELECT 
          gid,
          lieux,
          trajets_start,
          trajets_end,
          st_transform(st_concavehull(st_buffer(the_geom,0),0.9),4326) as the_geom
        FROM
          lieux_cluster_%ID%) l"
    );

    $SQL_UPDATE_TRAJETS = array(
      "UPDATE trajets_%ID% t
        SET
          lieu_start = nl.gid
        FROM 
          new_lieux_%ID% nl 
        WHERE t.gid::text = ANY(nl.trajets_start)",
      "UPDATE trajets_%ID% t
        SET
          lieu_end = nl.gid
        FROM 
          new_lieux_%ID% nl 
        WHERE t.gid::text = ANY(nl.trajets_end)"
    );

    $SQL_UPDATE_LIEUX = array( 
      "ALTER TABLE new_lieux_%ID% 
         DROP COLUMN lieux",
      "DROP TABLE lieux_%ID%",
      "ALTER TABLE new_lieux_%ID% RENAME TO lieux_%ID%");
    
    $strSql = array_merge($SQL_ATTACH_TRAJETS_TO_LIEU,$SQL_CLUSTER_LIEUX);
    $strSql = array_merge($strSql, $SQL_UPDATE_TRAJETS);
    $strSql = array_merge($strSql, $SQL_UPDATE_LIEUX);
    
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $strSql);
    //print_r($sqlCommands);
    $res = $this->execSqlCommands($sqlCommands);
    
    return true;
  }
  
  
  protected function adjustTrajets($data_id) {
    $res = true;
    $strSql = "select * from trajets_%ID% order by gid asc";
    
    $strSql = str_replace("%ID%", $data_id, $strSql);  
    $trajetTable = array();
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
      $trajetTable[] =array();
      foreach($dr as $key => $val) {
        $trajetTable[count($trajetTable)-1][$key] = $val;
      }
    }
    
    if (count($trajetTable)==0)
      return $res;
    
    $tbefore = $trajetTable[0];
    $toBeUpdated = array();
    for ($i=1; $i<count($trajetTable); $i++) {
      $t = $trajetTable[$i];
      //echo "Processing trajet " . $i . "\n";
      if ($t["lieu_start"] != $tbefore["lieu_end"]) {
        $trajetTable[$i]["lieu_start"] = $tbefore["lieu_end"];
        $toBeUpdated[] = $i;
        //$this->log("Reajusting Trajet " . $trajetTable[$i]["gid"] . " : lieu start " . $trajetTable[$i]["lieu_start"] ." <-- " .$tbefore["lieu_end"]);
      }
      $tbefore = $t;
    }
    $sqlCommands = array();
    for($i=0; $i<count($toBeUpdated); $i++) {
      $gid = $toBeUpdated[$i];
      $strSql = "UPDATE trajets_%ID% SET " . 
        " lieu_start = " . $trajetTable[$gid]["lieu_start"] . 
        //", address_start = '" . $this->dbConn->analyseSql($trajetTable[$gid]["address_start"]) . "'" .
        " WHERE gid = " . $gid;
      //echo $strSql . "\n";
      $sqlCommands[] = $strSql;
    }
    
    if (count($sqlCommands)>0) {
      $sqlCommands = str_replace(
        array('%ID%'),
        array($data_id),
        $sqlCommands);
      $res = $this->execSqlCommands($sqlCommands);
    }
    
    // updating address in trajets table
    $strSql = "update trajets_%ID%
      set address_start = (select address from lieux_%ID% l where l.gid=lieu_start),
          address_end = (select address from lieux_%ID% l where l.gid=lieu_end)";
    
    $sqlCommands = str_replace(
        array('%ID%'),
        array($data_id),
        array($strSql));
    $res = $this->execSqlCommands($sqlCommands);
    

    // synchro positions <-> trajets
    $res = $res && $this->syncPositionsFromTrajets($data_id);
  
    return $res;
  }
  
  
  /**
   * create as table sequence enumerating 
   * lieux and trajets chronologically
   */ 
  protected function buildRoadmap($data_id) {
    $res = true;
    $SQL_BUILD_ROADMAP = array(
    "DROP TABLE IF EXISTS sequences_%ID%",
    "CREATE TABLE sequences_%ID% AS
      SELECT 
        row_number() over() as gid,
        *
      FROM
      (
      SELECT 
       *
       FROM
      (
      SELECT
        'trajet' as type,
        gid as ref,
        time_start as time_start,
        time_end as time_end,
        null::int as duration,
        ''::text as address 	
      FROM
        trajets_%ID%
      UNION
      SELECT
       'lieu' as type,
       t1.lieu_start as ref, 
       t2.time_end as time_start,	
       t1.time_start as time_end,
       null::int as duration,
       t1.address_start as address
      FROM
        trajets_%ID% t1
      LEFT JOIN
        trajets_%ID% t2
      ON
        t1.gid-1=t2.gid
      ) t
      ORDER BY
        time_end asc,
        type desc
      ) t",
      "INSERT INTO 
        sequences_%ID%(gid,type,ref,time_start,time_end,address)
        SELECT
         COALESCE((SELECT max(gid) FROM sequences_%ID%),0)+1 as gid, 
        'lieu' as type,
         lieu_end as ref, 
         time_end as time_start,	
         null as time_end,
         address_end as address
        FROM
          trajets_%ID% t
        ORDER BY
          t.time_end desc limit 1 ",
        "UPDATE sequences_%ID%
          SET duration = extract('epoch' from(time_end -time_start))"
      );
      
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_BUILD_ROADMAP);
    $res = $this->execSqlCommands($sqlCommands);
    return true;
    
  }
  
  
  /**
   * export the sequence structure to JSON
   */ 
  protected function exportToJson($data_id) {
    // parametres
    $MICROSTOP_SCORE_MAXCOUNT = $this->params["MICROSTOP_SCORE_MAXCOUNT"];

    $SQL_TRAJETS_REC = array( 
      "drop table if exists trajets_%ID%_rec",
      "create table trajets_%ID%_rec as 
        select 
          min(gid) as gid,
          lieu_start,
          address_start,
          lieu_end,
          address_end,
          count(*) as recurrence
        from
           trajets_%ID%
        group by 
          lieu_start,
          address_start,
          lieu_end,
          address_end",
        "alter table trajets_%ID% drop column if exists gid_rec", 
        "alter table trajets_%ID% add column gid_rec integer", 
        "update trajets_%ID% t set gid_rec = r.gid from trajets_%ID%_rec r where r.lieu_start = t.lieu_start and r.lieu_end = t.lieu_end"
     );
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_TRAJETS_REC);
    $res = $this->execSqlCommands($sqlCommands);
    
    
    $SQL_TRAVEL_CHAIN = !$this->inferTransportModeEnabled ? 
    "-- travel_chain
    (select 
      array_to_json(ARRAY[]::text[])
    ) as travel_chain,
    " :
     "-- travel_chain
     (select 
       coalesce(array_to_json(array_agg(row_to_json(t))),array_to_json(ARRAY[]::text[]))
     from
     (
       select 
         CASE 
           WHEN transport_mode=5 THEN 1 
           WHEN transport_mode=3 THEN 8
         ELSE
           8
         END 	
         as transport, 
         ARRAY[]::int[] as accompagnements
       from
         sous_trajets_%ID% st
       join 
         (select traj.* from trajets_%ID% traj where traj.gid_rec= r.gid order by traj.time_start limit 1) tr
       on
         st.track = tr.gid
       order by st.subtrack_order desc
     ) t
     ) as travel_chain,"; 

    $SQL_TRAJETS = 
      "select 
        replace(
          replace(	
            replace(array_to_json(array_agg(row_to_json(t)))::text,'\',''),
            '\"{','{'
          ),'}\"','}'
        ) as trajets
      from 
      (
      select
        'trajet_' || r.gid as id,
        r.gid as gid,
        (select array_to_json(array_agg(row_to_json(t))) from	
          (select 
            t.time_start, t.time_end, 
            t.track_duration as duration, t.track_length as length,
            (select gid from sequences_%ID% where type='trajet' and ref = t.gid) as sequence
          from 
            trajets_%ID% t
          where
            gid_rec = r.gid
          order by time_start asc) t
        )  as time_intervals,

        (
          select array_to_json(array_agg(row_to_json(t))) from	
            (select 
              (select gid from sequences_%ID% where type='trajet' and ref = t.gid) as sequence,
              COALESCE(
                st_asgeojson(
                  st_transform(st_simplify(st_transform(the_geom,2154),2.0),4326)  
                ), '{ \"type\": \"LineString\", \"coordinates\": [] }'::text
              )::json as geom_json  
            from 
              trajets_%ID% t
            where
              gid_rec = r.gid
            order by time_start asc) t
        )  as details,
        --st_asgeojson((select ast_polyline_sew(st_transform(st_simplify(st_transform(st_lineMerge(the_geom),2154),1.0),4326)) 
        COALESCE(
          st_asgeojson(
            (select st_transform(st_simplify(st_transform(the_geom,2154),2.0),4326) 
             from trajets_%ID% where gid=r.gid)
          ), '{ \"type\": \"LineString\", \"coordinates\": [] }'::text
        )::json as geom_json,
        r.recurrence,
        $SQL_TRAVEL_CHAIN
        (select 
          array_to_json(array_agg(row_to_json(t)))
        from 
        (
          select 
            'ma_' || m.gid as id,
            '' as name,
            '' as type,
            m.lon as lng,
            m.lat,
            m.address,
            m.time_start,
            m.time_end,
            m.duration,
            m.score,
            m.score_order,
            (select gid_rec from trajets_%ID% where gid=m.track) as trajet,
            st_asgeojson(st_centroid(m.the_geom))::json as geom_json 
          from 
            (SELECT mar.*, mai.score, mai.score_order FROM microarrets_%ID% mar LEFT JOIN microarrets_info_%ID% mai ON mar.gid = mai.gid WHERE mai.score_order<=$MICROSTOP_SCORE_MAXCOUNT) m
          where
            m.track  in (select gid from trajets_%ID% t1 where t1.lieu_start=r.lieu_start and t1.lieu_end=r.lieu_end)
          order by m.time_start asc
        ) t) as microarrets
      from
        trajets_%ID%_rec r
      order by recurrence desc, gid asc 
      )t";
    
    $SQL_LIEUX = 
      "select 
        replace(
          replace(	
            replace(array_to_json(coalesce(array_agg(row_to_json(t)), ARRAY[]::json[]))::text,'\',''),
            '\"{','{'
          ),'}\"','}'
        ) as lieux
      from 
      (
      select * from (
        select
          'lieu_' || l.gid as id,
          name as name,
          coalesce(type::text,'') as \"type\",
          l.lon as lng,
          l.lat,
          l.address, 
          (select array_to_json(array_agg(row_to_json(t))) from (select 
            coalesce(s.time_start, s.time_end - interval '2 minutes') as \"time_start\",
            coalesce(s.time_end, s.time_start + interval '2 minutes') as \"time_end\",
            s.duration,
            s.gid as sequence
          from 
            sequences_%ID% s
          where
            s.type='lieu'
          and 
            s.ref=l.gid
          order by time_start asc) t) as  time_intervals,
          st_asgeojson(st_centroid(l.the_geom)) as geom_json,
          (select count(*) from sequences_%ID% s where s.type='lieu' and s.ref=l.gid) as recurrence
        from 
          lieux_%ID% l
         order by 
           recurrence desc,
           (select 
              min(coalesce(s.time_start, s.time_end - interval '2 minutes')) as time_first 
            from 
              sequences_%ID% s 
            where 
              s.type='lieu' and s.ref=l.gid) asc
        ) t1
        where t1.time_intervals is not null
      ) t";
    
    $SQL_MA = 
      "select 
        replace(
          replace(	
            replace(array_to_json(array_agg(row_to_json(t)))::text,'\',''),
            '\"{','{'
          ),'}\"','}'
        ) as microarrets
      from 
      (
        select 
          'ma_' || m.gid as id,
          '' as name,
          '' as type,
          m.lon as lng,
          m.lat,
          m.address,
          m.time_start,
          m.time_end,
          m.duration,
          m.score,
          m.score_order,
          (select gid_rec from trajets_%ID% where gid=m.track) as trajet,
          st_asgeojson(st_centroid(m.the_geom)) as geom_json 
        from 
        (SELECT mar.*, mai.score, mai.score_order FROM microarrets_%ID% mar LEFT JOIN microarrets_info_%ID% mai ON mar.gid = mai.gid WHERE mai.score_order<=$MICROSTOP_SCORE_MAXCOUNT ORDER BY mai.score_order ASC) m
        order by m.score_order asc, m.time_start asc
      ) t";
    $SQL_SEQ = 
      "select '[' || string_agg(row_to_json(t)::text,',') || ']' as roadmap from
        (select 
          gid as sequence_id,	
          CASE WHEN \"type\"='lieu' THEN 
             \"type\" || '_' || ref 
          ELSE
            \"type\" || '_' || (select t.gid_rec from trajets_%ID% t where t.gid=ref)
          END as id,
          CASE WHEN \"type\"='lieu' THEN
             \"type\" || '_' || ref 
          ELSE
             \"type\" || '_' || ref
          END as id_detail,
          \"type\" as \"type\",
          coalesce(time_start, time_end - interval '2 minutes')::text as start,
          coalesce(time_end, time_start + interval '2 minutes')::text as end,
          CASE WHEN \"type\"='lieu' THEN 
          'immobility'
          ELSE
          'mobility'
          END  as desc,
          '' as modality,
          CASE WHEN \"type\"='lieu' THEN 
          'immobility'
          ELSE
          'mobility'
          END  as icon
        from
          sequences_%ID%
        order by gid asc) t";
    
    $strSql = str_replace("%ID%", $data_id, $SQL_LIEUX);
    $json_lieux = $this->dbConn->getScalarSql($strSql,'[]');
    $data_lieux = json_decode($json_lieux, true);
    
    $strSql = str_replace("%ID%", $data_id, $SQL_TRAJETS);
    $json_trajets = $this->dbConn->getScalarSql($strSql,'[]');
    $data_trajets = json_decode($json_trajets, true);
    
    $strSql = str_replace("%ID%", $data_id, $SQL_MA);
    $json_ma = $this->dbConn->getScalarSql($strSql,'[]');
    $data_ma = json_decode($json_ma, true);
    
    $strSql = str_replace("%ID%", $data_id, $SQL_SEQ);
    $json_seq = $this->dbConn->getScalarSql($strSql,'[]');
    $data_seq = json_decode($json_seq, true);
    
    $data =  array (
      "roadmap" =>  $data_seq,
      "trajets" => $data_trajets,
      "lieux" => $data_lieux,
      "microStops" => $data_ma
    );
    
    $json = json_encode($data, JSON_PRETTY_PRINT);

    $RE_NORMALIZE_JSON_DATE = '/("[0-9\-]{10})T([0-9\:]{8}")/';
    $REPLACE_NORMALIZE_JSON_DATE = '\\1 \\2';
    $json_normalized = preg_replace($RE_NORMALIZE_JSON_DATE, $REPLACE_NORMALIZE_JSON_DATE, $json);
        
    //echo $json;
    //var_dump(json_decode($json, true));
    
    $filename = "roadmap_" . $data_id . ".json";
    
    $this->writeTxtFile($json_normalized, $filename, $this->outDir);
    
    
    $res = true;
    return $res;
  }
  
  


  protected function postProcessMicroStop($data_id) {
    // calcul des microarrets recurrents 
    $res = $this->computeMicroStopRec($data_id);
    // rajout des informations augmentées pour le micro arrêt
    $res =  $res && $this->augmentMicroStop($data_id);
    // calcul du score de chaque micro arret
    $res = $res && $this->computeMicroStopScore($data_id);

    return $res;
  }

  /**
   * Calcul du score de chaque micro arret
   */
  protected function computeMicroStopScore($data_id) {
    $res = true;
    // parametres du calcul
    $MICROSTOP_SCORE_RECURRENCE_WEIGHT = $this->params["MICROSTOP_SCORE_RECURRENCE_WEIGHT"];
    $MICROSTOP_SCORE_TIMESLOT_WEIGHT = $this->params["MICROSTOP_SCORE_TIMESLOT_WEIGHT"];
    $MICROSTOP_SCORE_POICOUNT_WEIGHT = $this->params["MICROSTOP_SCORE_POICOUNT_WEIGHT"];
    $MICROSTOP_SCORE_DURATION_WEIGHT = $this->params["MICROSTOP_SCORE_DURATION_WEIGHT"];
    $MICROSTOP_SCORE_INDOOR_WEIGHT = $this->params["MICROSTOP_SCORE_INDOOR_WEIGHT"];
    $MICROSTOP_SCORE_MOTIONLEVEL_WEIGHT = $this->params["MICROSTOP_SCORE_MOTIONLEVEL_WEIGHT"];
    
    $MICROSTOP_SCORE_MOTIONLEVEL_THRESHOLD = $this->params["MICROSTOP_SCORE_MOTIONLEVEL_THRESHOLD"];
    $MICROSTOP_MAX_DURATION = $this->params["MICROSTOP_MAX_DURATION"]; 
        
    $SQL_MS_SCORE = array(
      "UPDATE microarrets_info_%DATA_ID% mai
        SET 
          score = info.score
      FROM
        (SELECT 
          score,
          gid
        FROM
        (SELECT
          (--- recurrence	
          (recurrence*$MICROSTOP_SCORE_RECURRENCE_WEIGHT) +
          -- presence dans les bons horaires	
          ((2*weekend*(coalesce(array_length(poi_ids,1),0)::int-1))*$MICROSTOP_SCORE_TIMESLOT_WEIGHT) +
          ((CASE WHEN in_time_slot>0 THEN in_time_slot ELSE -1.0 END)*$MICROSTOP_SCORE_TIMESLOT_WEIGHT) +
          --presence de poi a proximite
          (coalesce(array_length(poi_ids,1),0)*$MICROSTOP_SCORE_POICOUNT_WEIGHT) +
          -- duree
          ((duration / ($MICROSTOP_MAX_DURATION * 1.0)) * $MICROSTOP_SCORE_DURATION_WEIGHT) +
          -- indoor
          (indoor * $MICROSTOP_SCORE_INDOOR_WEIGHT) +	
          -- motion level	
          (motion_level_med / ( $MICROSTOP_SCORE_MOTIONLEVEL_THRESHOLD * 1.0 )  * $MICROSTOP_SCORE_MOTIONLEVEL_WEIGHT)
          ) as score,
          gid
        FROM
          microarrets_info_%DATA_ID%
        ORDER BY score DESC) t) info
      WHERE info.gid = mai.gid",
      "UPDATE microarrets_info_%DATA_ID% mai
        -- mise a jour de l'ordre avec tris par score descendant en ne considerant que le ma qui a le core le plus elevé pour les recurrents
        -- les autres ont un ordre à null  
          SET 
            score_order = info.score_order
        FROM
          (SELECT
            m_info.gid,
            row_number() over() as score_order
          FROM
          (SELECT  
            mai.*
          FROM
          (SELECT
            DISTINCT 
            gid_rec,
            recurrence,
            --tri selon score descendant dans même classe de recurrence
            first_value(gid) over (partition by gid_rec order by score desc) as gid_selec
          FROM
            microarrets_info_%DATA_ID%) t
          JOIN
            microarrets_info_%DATA_ID% mai
          ON 
            t.gid_selec = mai.gid
          ORDER BY score DESC) m_info
          ) info
      WHERE info.gid = mai.gid",
      "UPDATE microarrets_info_%DATA_ID% mai
        -- mise à jour des l'ordre des recurrents non sélectionnes
        SET 
          score_order = info.score_order
      FROM
          (SELECT
            gid,
            score,
            score_order_max + row_number() over() as score_order
          FROM
          (select 
            t.gid,
            t.score,
            t2.score_order_max
          from 
            microarrets_info_%DATA_ID% t,
            (select score_order as score_order_max from microarrets_info_%DATA_ID% where score_order is not null order by score_order desc limit 1) t2  
          where 
            t.score_order is null 
          order by t.score desc) t2) info
      WHERE info.gid = mai.gid"
    );
    
    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_MS_SCORE);
    $res = $this->execSqlCommands($sqlCommands);

    return $res;
  }


  /**
   * rajout d'information contextuelle sur les micro-arrets
   * 
   * 
   */
  protected function augmentMicroStop($data_id) {
    // 0 Création de la table microarret info 
    $SQL_MS_INFO = array(
      "DROP TABLE IF EXISTS microarrets_info_%DATA_ID%",
      "CREATE TABLE microarrets_info_%DATA_ID% (LIKE microarrets_info_tpl INCLUDING ALL)",
      "INSERT INTO microarrets_info_%DATA_ID% (gid, duration)
        SELECT gid, duration FROM microarrets_%DATA_ID%"
    );

    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_MS_INFO);
    $res = $this->execSqlCommands($sqlCommands);

    // 1 information sur la tranche horaire/weekend
    $res = $res && $this->checkMicroStopTimeSlots($data_id); 
    
    // 2 information sur la proximité poi
    $res = $res && $this->checkMicroStopPoi($data_id);

    // 3 information sur indoor/outdoor
    $res = $res && $this->checkMicroStopIndoor($data_id);

    // 4 mediane de l'indice de mouvement 
    $res = $res && $this->checkMicroStopMotionLevel($data_id);

    // 5 recurrence spatiale
    $res = $res && $this->checkMicroStopRecurrence($data_id);


    return $res;
  }

  //
  // mise a jour info timeslot / week end
  //
  protected function checkMicroStopTimeSlots($data_id) {
    // on recherche si le debut ou la fin du ma a lieu le week end
    $SQL_WEEKEND = array(
      "UPDATE microarrets_info_%DATA_ID% mai 
        SET weekend = (EXTRACT(DOW FROM  ma.time_start) in (0,6) or EXTRACT(DOW FROM  ma.time_end) in (0,6))::int
       FROM microarrets_%DATA_ID% ma
       WHERE mai.gid = ma.gid"
    );

    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_WEEKEND);
    $res = $this->execSqlCommands($sqlCommands);

    // on calcul le pourcentage de recouvrement temporel entre
    // l'emprise temporelle du ma et les tranches horaires de selection en paramètres
    $SQL_SLOT = array();
    for ($i=0;$i<count($this->params["MICROSTOP_FILTER_TIMESLOT_WEEK"]); $i++) {
      $start  = $this->params["MICROSTOP_FILTER_TIMESLOT_WEEK"][$i]["start"];
      $end    = $this->params["MICROSTOP_FILTER_TIMESLOT_WEEK"][$i]["end"];
      array_push($SQL_SLOT,
        "greatest(extract(epoch from (least(h_end, INTERVAL '".$end."') - greatest(h_start, INTERVAL '".$start."'))),0)");
    }

    if (count($SQL_SLOT)>0) {
      $SQL_TIMESLOT = array(
        "UPDATE microarrets_info_%DATA_ID% mai 
        SET in_time_slot = ma.percent
        FROM
        (SELECT
          (" . implode("+",$SQL_SLOT) . ")/  (duration * 1.) as percent,
          gid
        FROM
          (SELECT 
          gid, 
          (time_start - date_trunc('day', time_start))::interval as h_start, 
          (time_end - date_trunc('day', time_end))::interval as h_end,
          duration
          FROM microarrets_%DATA_ID% ) ma_time ) ma
        WHERE ma.gid = mai.gid"
      );
      
      $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_TIMESLOT);
      $res = $res && $this->execSqlCommands($sqlCommands);        
    }

    return $res;
  }

  //
  // recherche de poi à proximité du ma
  //
  protected function checkMicroStopPoi($data_id) {
    $search_radius = $this->params["POI_MATCHING_RADIUS"];         
    $SQL_MAP_POI_MA = array(
      "UPDATE microarrets_info_%DATA_ID% li
          SET 
            poi_ids = t.poi_ids
        FROM  
        (SELECT 
            (SELECT 
              array_agg(id) as ids 
            FROM 
              poi_%DATA_ID% p 
            WHERE 
              st_dwithin(st_transform(l.the_geom,2154), st_transform(p.the_geom,2154), ".$search_radius.") 
            ) as poi_ids,
            l.*
          FROM 
            microarrets_%DATA_ID% l) t
        WHERE
          t.gid = li.gid"
    );
    
    $sqlCommands = str_replace(
      array('%DATA_ID%'),
      array($data_id),
      $SQL_MAP_POI_MA);
    $res = $this->execSqlCommands($sqlCommands);
    
    return $res;
  
  }

  //
  // mise a jour info indoor/ nb_sat_moy
  //
  protected function checkMicroStopIndoor($data_id) {
    // on recherche si le debut ou la fin du ma a lieu le week end
    $SQL_MA_INDOOR = array(
      "UPDATE microarrets_info_%DATA_ID% mai 
        SET nb_sat_moy = 
            (SELECT avg(nbsat) FROM positions_%DATA_ID% WHERE gid>=ma.pos_start and gid<=ma.pos_end)
       FROM microarrets_%DATA_ID% ma
       WHERE mai.gid = ma.gid",
       "UPDATE microarrets_info_%DATA_ID% mai 
        SET indoor = (SELECT count(*) FROM positions_%DATA_ID% WHERE gid>=ma.pos_start and gid<=ma.pos_end and nbsat <= ". 
                      $this->params["MICROSTOP_FILTER_INDOOR"] . ")/
        ((SELECT count(*) FROM positions_%DATA_ID% WHERE gid>=ma.pos_start and gid<=ma.pos_end)*1.0)
        FROM microarrets_%DATA_ID% ma
        WHERE mai.gid = ma.gid"
    );

    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_MA_INDOOR);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }

  
  
  //
  // mise a jour info motion_level_med
  //
  protected function checkMicroStopMotionLevel($data_id) {
    // on recherche si le debut ou la fin du ma a lieu le week end
    $SQL_MA_MOTION_LEVEL = array(
      "UPDATE microarrets_info_%DATA_ID% mai 
        SET motion_level_med = 
            (SELECT median(activitylevel::numeric) FROM mobility_%DATA_ID% mob WHERE mob.time>=ma.time_start and mob.time<=ma.time_end)
       FROM microarrets_%DATA_ID% ma
       WHERE mai.gid = ma.gid"
    );

    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_MA_MOTION_LEVEL);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }

  //
  // mise a jour info recurrence
  //
  protected function checkMicroStopRecurrence($data_id) {
    // on recherche si le debut ou la fin du ma a lieu le week end
    $SQL_MA_REC = array(
      "UPDATE microarrets_info_%DATA_ID% mai 
        SET recurrence = ma_rec.recurrence,
            gid_rec    = ma_rec.gid
       FROM microarrets_rec_%DATA_ID% ma_rec
       WHERE mai.gid = ANY(ma_rec.occurences)"
    );

    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_MA_REC);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }


  //
  // processing micro arret
  //   fusion micro-arret --> lieux
  //
  protected function processMicroStop($data_id) {
    $res = true;
    // supression des micro-arrets apparaissant dans les plages temporelles des lieux
    $res = $res && $this->removeMicroStopInLieux($data_id);

    // rattachement des micro-arrets apparaissant dans les plages temporelles de trajet 
    // au lieu de début ou de fin de trajet
    $res = $res && $this->attachMicroStopToLieux($data_id);

    // regroupement des micro-arrets proches spatio-temporellement
    // et transformation en lieux si durrée conséquente
    $res = $res && $this->mergeMicroStopIntoLieux($data_id);
    
    // synchro positions <-> trajets
    $res = $res && $this->syncPositionsFromTrajets($data_id);

    return $res;

  
  }

  // suppression des microarrets dont la plage horaire 
  // ne s'intersecte pas avec la plage horaire d'un trajet
  protected function removeMicroStopInLieux($data_id) {
    $SQL_REMOVE_MA = array("
      DELETE FROM microarrets_%DATA_ID% 
      WHERE gid IN
        (WITH ti AS
          (SELECT 
            tr.gid,
            tr.time_start,
            tr.pos_start,
            tr.lieu_start,
            tr.address_start,
            li_s.the_geom,
            tr.time_end,
            tr.pos_end,
            tr.lieu_end,
            tr.address_end,
            li_e.the_geom
          FROM 
            trajets_%DATA_ID% tr 
          LEFT JOIN 
            lieux_%DATA_ID% li_s
          ON tr.lieu_start = li_s.gid
          LEFT JOIN 
            lieux_%DATA_ID% li_e
          ON tr.lieu_end = li_e.gid)
          SELECT 
            ma.gid
          FROM
            microarrets_%DATA_ID% ma
          EXCEPT
          SELECT 
            ma.gid
          FROM 
            microarrets_%DATA_ID% ma 
          JOIN
            ti
          ON 
            (ma.time_start>=ti.time_start AND ma.time_start<=ti.time_end) 
          OR
            (ma.time_end>=ti.time_start AND ma.time_end<=ti.time_end)
          )");
    $sqlCommands = str_replace('%DATA_ID%', $data_id, $SQL_REMOVE_MA);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }

  // rattachement des microarrets aux lieux les plus proches 
  // spatio-temporellement 
  protected function attachMicroStopToLieux($data_id) {
    $res = true;
    // params
    $MICROSTOP_ATTACH_LIEU_RADIUS   = $this->params["MICROSTOP_ATTACH_LIEU_RADIUS"];    // ecart max entre un lieu et un ma pour le rattachement (en m)
    $MICROSTOP_ATTACH_LIEU_DURATION = $this->params["MICROSTOP_ATTACH_LIEU_DURATION"];  // duree max d'écart temporel (en s.) entre la fin d'un lieu/le début d'un ma ou la fin d'un ma/le debut d'un lieu pour le rattacher (25 minutes)

    $SQL_INFO_MA_LIEU = "
      WITH ti AS
        (SELECT 
          tr.gid,
          tr.time_start,
          tr.pos_start,
          tr.lieu_start,
          tr.address_start,
          li_s.the_geom as li_s_the_geom,
          tr.time_end,
          tr.pos_end,
          tr.lieu_end,
          tr.address_end,
          li_e.the_geom as li_e_the_geom
        FROM 
          trajets_%DATA_ID% tr 
        LEFT JOIN 
          lieux_%DATA_ID% li_s
        ON tr.lieu_start = li_s.gid
        LEFT JOIN 
          lieux_%DATA_ID% li_e
        ON tr.lieu_end = li_e.gid)

        SELECT 
          DISTINCT
          ti.gid as trajet_gid, 
          ti.time_start as trajet_time_start, 
          ti.time_end as trajet_time_end,
          ma.gid as ma_gid, 
          ma.time_start as ma_time_start, 
          ma.time_end as ma_time_end,
          ma.address as ma_address,

          -- distance spatio temporelle au lieu de depart
          st_distance(st_transform(li_s_the_geom, 2154), st_transform(ma.the_geom, 2154)) as dist_lieu_depart,
          greatest(0, extract(epoch from (ma.time_start - ti.time_start))) as duration_lieu_depart,
          (select max(st_distance(st_transform(po.the_geom, 2154), st_transform(li_s_the_geom, 2154))) from positions_%DATA_ID% po where po.time>=ti.time_start and po.time<=ma.time_start) as dist_max_subtrack_depart,
          -- distance spatio temporelle au lieu d'arrivee
          st_distance(st_transform(li_e_the_geom, 2154), st_transform(ma.the_geom, 2154)) as dist_lieu_arrivee,
          greatest(0, extract(epoch from (ti.time_end - ma.time_end))) as duration_lieu_arrivee,
          (select max(st_distance(st_transform(po.the_geom, 2154), st_transform(li_e_the_geom, 2154))) from positions_%DATA_ID% po where po.time>=ma.time_end and po.time<=ti.time_end) as dist_max_subtrack_arrivee
        FROM 
          microarrets_%DATA_ID% ma 
        JOIN
          ti
        ON 
          (ma.time_start>=ti.time_start AND ma.time_start<=ti.time_end) 
        OR
          (ma.time_end>=ti.time_start AND ma.time_end<=ti.time_end)
        ORDER BY ma.time_start ASC
        ";
    
    $SQL_INFO_MA_LIEU = str_replace("%DATA_ID%", $data_id, $SQL_INFO_MA_LIEU);  
    $ds = $this->dbConn->initDataSet($SQL_INFO_MA_LIEU);
    $microStopToAttachDepart = array();
    $microStopToAttachArrivee = array();
    while ($dr = $ds->getRowIter()) {
      $ma_id = intval($dr["ma_gid"]);
      $trajet_id = intval($dr["trajet_gid"]);

      $dist_lieu_depart = floatval($dr["dist_lieu_depart"]);
      $dist_max_subtrack_depart = floatval($dr["dist_max_subtrack_depart"]);
      $duration_lieu_depart = floatval($dr["duration_lieu_depart"]);
      
      $dist_lieu_arrivee = floatval($dr["dist_lieu_arrivee"]);
      $dist_max_subtrack_arrivee = floatval($dr["dist_max_subtrack_arrivee"]);
      $duration_lieu_arrivee = floatval($dr["duration_lieu_arrivee"]);

      if ($dist_lieu_depart<=$MICROSTOP_ATTACH_LIEU_RADIUS 
        && $dist_max_subtrack_depart<=$MICROSTOP_ATTACH_LIEU_RADIUS 
        && $duration_lieu_depart <= $MICROSTOP_ATTACH_LIEU_DURATION) {
          array_push($microStopToAttachDepart,
            array("trajet" => $trajet_id, "ma" => $ma_id)
          );
      }
      else if ($dist_lieu_arrivee<=$MICROSTOP_ATTACH_LIEU_RADIUS 
        && $dist_max_subtrack_arrivee<=$MICROSTOP_ATTACH_LIEU_RADIUS 
        && $duration_lieu_arrivee <= $MICROSTOP_ATTACH_LIEU_DURATION) {
        array_push($microStopToAttachArrivee,
            array("trajet" => $trajet_id, "ma" => $ma_id)
          );
      }
    }

    // rattrachement des ma aux lieux de departs
    for ($i=0; $i<count($microStopToAttachDepart); $i++) {
      // mise à jour de l'heure de départ du trajet
      // et suprresion du ma
      $sqlCommands = array(
        "UPDATE trajets_%DATA_ID% tr 
          SET time_start = (SELECT time_end FROM microarrets_%DATA_ID% WHERE gid=".$microStopToAttachDepart[$i]["ma"].")
        WHERE tr.gid = " . $microStopToAttachDepart[$i]["trajet"],
        "DELETE FROM microarrets_%DATA_ID% WHERE gid=".$microStopToAttachDepart[$i]["ma"]
      );
      $sqlCommands = str_replace('%DATA_ID%', $data_id, $sqlCommands);
      $res = $res && $this->execSqlCommands($sqlCommands);
    }

    // rattachement des ma aux lieux d'arrivée
    // rattrachement des ma aux lieux de departs
    for ($i=0; $i<count($microStopToAttachArrivee); $i++) {
      // mise à jour de l'heure de départ du trajet
      // et suprresion du ma
      $sqlCommands = array(
        "UPDATE trajets_%DATA_ID% tr 
          SET time_end = (SELECT time_start FROM microarrets_%DATA_ID% WHERE gid=".$microStopToAttachArrivee[$i]["ma"].")
        WHERE tr.gid = " . $microStopToAttachArrivee[$i]["trajet"],
        "DELETE FROM microarrets_%DATA_ID% WHERE gid=".$microStopToAttachArrivee[$i]["ma"]
      );
      $sqlCommands = str_replace('%DATA_ID%', $data_id, $sqlCommands);
      $res = $res && $this->execSqlCommands($sqlCommands);
    }
    
    return $res;
  }

  protected function computeMicroStopRec($data_id) {
    $MICROSTOP_CLUSTER_REC_RADIUS = $this->params["MICROSTOP_CLUSTER_REC_RADIUS"];
    $sqlCommands = array(
      "DROP TABLE IF EXISTS microarrets_rec_%DATA_ID%",
      "CREATE TABLE microarrets_rec_%DATA_ID% AS
        SELECT
          row_number() OVER() AS gid,
          others as occurences,
          array_length(others, 1) as recurrence 
        FROM
        (SELECT 
          m1.gid, 
          count(*) as nb_occurrences,
          array_agg(m2.gid ORDER BY m2.gid) as others 
        FROM 
          microarrets_%DATA_ID% m1 
        JOIN 
          microarrets_%DATA_ID% m2 
        ON 
          m1.address=m2.address -- jointure semantique geocodage nominatim
        OR 
          st_dwithin(st_transform(m1.the_geom,2154), st_transform(m2.the_geom, 2154), ". $MICROSTOP_CLUSTER_REC_RADIUS .") 
        GROUP BY 
          m1.gid) t
        GROUP BY others",
        "UPDATE microarrets_%DATA_ID% ma 
          SET gid_rec = rec.gid
        FROM microarrets_rec_%DATA_ID% rec
        WHERE ma.gid = ANY (rec.occurences)");
    $sqlCommands = str_replace('%DATA_ID%', $data_id, $sqlCommands);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;
  }











  /**
   * 
   * @param type $data_id
   * @return boolean
   * 
   * Fonction de traitements des micro arret en plusieurs phases :
   * 1 -> fusion des micro-arret proches spatio-temporellement
   * 2 -> transformation micro-arret long en lieu
   * 3 -> re-segmentation trajets selon ces nouveaux lieux
   * 4 -> resynchronisation données par rapport à trajets modifies
   * 
   */
  protected function mergeMicroStopIntoLieux($data_id) {
    // paramètres liés au process
    $MICROSTOP_MERGE_CLUSTER_TIME     = $this->params["MICROSTOP_MERGE_CLUSTER_TIME"];      // limite de duree maximale en s.   entre microarrets pour agregation 
    $MICROSTOP_MERGE_CLUSTER_DISTANCE = $this->params["MICROSTOP_MERGE_CLUSTER_DISTANCE"];      // limite de distance maximale en m entre microarrets pour agregation 
    $MICROSTOP_MAX_DURATION             = $this->params["MICROSTOP_MAX_DURATION"];      // duree maximale micro-arret, au dela --> lieu
    
    

    $res = true;
    
    // phase 1
    
    // chargement des microArrets en mémoire
    $strSql = "select * from microarrets_%ID% order by gid asc";
    
    $strSql = str_replace("%ID%", $data_id, $strSql);  
    $microStopTable = array();
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
      $microStopTable[] =array();
      foreach($dr as $key => $val) {
        $microStopTable[count($microStopTable)-1][$key] = $val;
      }
    }
    if (count($microStopTable)==0)
      return $res;
    
    
    // parcours des micro arrets pour regroupement
    // structure de cluster : array(
    // i_start      => indice du 1er ma dans le clsuter
    // i_end        => indice du dernier ma dans le cluster
    // pos_start    => 
    // pos_end      =>
    // time_start   =>
    // time_end     =>
    // stop_type    =>
    // track        =>
    // gids         => tableau des gid des ma initiaux constituant le cluster
    // )
    //
    
    
    // on initialise le cluster courant
    $mClust = array(
      "i_start"   => 0,
      "i_end"     => 0,
      "pos_start" => $microStopTable[0]["pos_start"],
      "pos_end"   => $microStopTable[0]["pos_end"],
      "time_start"=> $microStopTable[0]["time_start"],
      "time_end"  => $microStopTable[0]["time_end"],
      "stop_type" => $microStopTable[0]["stop_type"],
      "track"     => $microStopTable[0]["track"],
      "address"   => $microStopTable[0]["address"],
      "gids"       => array($microStopTable[0]["gid"])
    );
    $lat = $microStopTable[0]["lat"];
    $lon = $microStopTable[0]["lon"];
    $mClusters = array();
    
    for ($i=1; $i<count($microStopTable); $i++) {
      //echo "Processing micro stop " . $i . "\n";
      
      $m = $microStopTable[$i];
      // si on est sur le même trajet on peut regrouper en cluster
      if ($mClust["track"]==$m["track"]) {
        // on cherche si le microarret courant est à associer au cluster courant car proche dans le temps
        $duration = self::duration($mClust["time_end"], $m["time_start"]);
        $distance = $this->computeDistance($lat, $lon, $m["lat"], $m["lon"]);
        //echo $duration . "  " . $distance . "\n";
        if ($duration<=$MICROSTOP_MERGE_CLUSTER_TIME && 
            $distance<=$MICROSTOP_MERGE_CLUSTER_DISTANCE) {
          $mClust["i_end"]    = $i;
          $mClust["pos_end"]  = $microStopTable[$i]["pos_end"];
          $mClust["time_end"] = $microStopTable[$i]["time_end"];
          array_push($mClust["gids"], $microStopTable[$i]["gid"]);

        }
        else {
          // sinon on termine le cluster courant et on crée un nouveau cluster
          $mClusters[] = $mClust;
          $mClust = array(
            "i_start"   => $i,
            "i_end"     => $i,
            "pos_start" => $microStopTable[$i]["pos_start"],
            "pos_end"   => $microStopTable[$i]["pos_end"],
            "time_start"=> $microStopTable[$i]["time_start"],
            "time_end"  => $microStopTable[$i]["time_end"],
            "stop_type" => $microStopTable[$i]["stop_type"],
            "track"     => $microStopTable[$i]["track"],
            "address"   => $microStopTable[$i]["address"],
            "gids"       => array($microStopTable[$i]["gid"])
          );
          $lat = $microStopTable[$i]["lat"];
          $lon = $microStopTable[$i]["lon"];
        }
      }
      else {
        // sinon on termine le cluster courant et on crée un nouveau cluster
        $mClusters[] = $mClust;
        $mClust = array(
          "i_start"   => $i,
          "i_end"     => $i,
          "pos_start" => $microStopTable[$i]["pos_start"],
          "pos_end"   => $microStopTable[$i]["pos_end"],
          "time_start"=> $microStopTable[$i]["time_start"],
          "time_end"  => $microStopTable[$i]["time_end"],
          "stop_type" => $microStopTable[$i]["stop_type"],
          "track"     => $microStopTable[$i]["track"],
          "address"   => $microStopTable[$i]["address"],
          "gids"       => array($microStopTable[$i]["gid"])
        );
        $lat = $microStopTable[$i]["lat"];
        $lon = $microStopTable[$i]["lon"];
      }
      
    }
    // ajout du dernier cluster
    $mClusters[] = $mClust;
    
    //die(print_r($mClusters, true));
    
    // repartition microarret/lieux en fonction de la duree
    $microStops = array();
    $newLieux   = array();
    for ($i=0; $i<count($mClusters); $i++) {
      $time_start = $mClusters[$i]["time_start"];
      $time_end   = $mClusters[$i]["time_end"]; 
      $duration = self::duration($time_start, $time_end);
      if ($duration>$MICROSTOP_MAX_DURATION) {
        $newLieux[]     = $mClusters[$i];
      }
      else {
        $microStops[] = $mClusters[$i];
      }
    }
       
    
    // mise à jour de la table microarrets
    $SQL_UPDATE_MICROSTOPS = array(
      "DROP TABLE IF EXISTS microarrets_%ID%b",
      "CREATE TABLE microarrets_%ID%b (LIKE microarrets_%ID% INCLUDING ALL)"
    );
    for ($i=0; $i<count($microStops); $i++) {
      $pos_start  = $microStops[$i]["pos_start"];
      $pos_end    = $microStops[$i]["pos_end"];
      $time_start = $microStops[$i]["time_start"];
      $time_end   = $microStops[$i]["time_end"]; 
      $stop_type  = $microStops[$i]["stop_type"]; 
      $track      = $microStops[$i]["track"]; 

      
      $tablename_position = "positions_%ID%";
      
      $strSql = "INSERT INTO microarrets_%ID%b (gid, address, the_geom,
                  lat, lon, 
                  pos_start, time_start, pos_end, time_end, 
                  duration, radius, stop_type, track) 
                VALUES (
                  $i+1,
                  (SELECT address FROM microarrets_%ID% WHERE gid = ".$microStops[$i]["gids"][0]."),
                  (SELECT st_centroid(st_collect(the_geom)) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                  (SELECT st_y(st_centroid(st_collect(the_geom))) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                  (SELECT st_x(st_centroid(st_collect(the_geom))) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                  $pos_start,
                  '".$time_start."',
                  $pos_end,
                  '".$time_end."',
                  (SELECT extract('epoch' from('$time_end'::timestamp without time zone - '$time_start'::timestamp without time zone))),
                  (SELECT sqrt(ST_Area(ST_MinimumBoundingCircle(st_collect(st_transform(the_geom,2154)))) / pi()) AS distance FROM $tablename_position WHERE gid>=$pos_start AND gid<=$pos_end),
                  '".$stop_type."',
                  $track)";
      $SQL_UPDATE_MICROSTOPS[] = $strSql;
    }
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_UPDATE_MICROSTOPS);
    
    $res = $this->execSqlCommands($sqlCommands);
    //die(print_r($newLieux, true));
    
    // phase 2
    // ajout lieux
    $tablename_lieu     = "lieux_%ID%";
    $tablename_position = "positions_%ID%";
    
    $strSql = "SELECT coalesce(max(gid),0)+1 FROM lieux_%ID%";
    $strSql = str_replace(
      array('%ID%'),
      array($data_id),
      $strSql);
    $lieuId = intval($this->dbConn->getScalarSql($strSql,1));

    $SQL_ADD_LIEUX = array();
    /*$SQL_ADD_LIEUX = array(
      "DROP TABLE IF EXISTS lieux_%ID%b",
      "CREATE TABLE lieux_%ID%b AS SELECT * FROM lieux_%ID%");
    */
    for ($i=0; $i<count($newLieux); $i++) {
      $pos_start  = $newLieux[$i]["pos_start"];
      $pos_end    = $newLieux[$i]["pos_end"];
      $time_start = $newLieux[$i]["time_start"];
      $time_end   = $newLieux[$i]["time_end"]; 
      $stop_type  = $newLieux[$i]["stop_type"]; 
      $track      = $newLieux[$i]["track"]; 
      
      $strSql = "INSERT INTO $tablename_lieu (gid, trajets_start, trajets_end, the_geom, lat, lon, recurrence, address) 
              VALUES 
                ($lieuId, 
                ARRAY[$track],
                ARRAY[$track],
                (SELECT ST_Transform(ST_buffer(ST_Transform(
                                    (SELECT st_concaveHull(st_collect(the_geom), 0.8) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                                  2154),0.1),4326)),
                        
                (SELECT st_y(st_centroid(st_collect(the_geom))) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                (SELECT st_x(st_centroid(st_collect(the_geom))) as the_geom FROM $tablename_position WHERE gid>=$pos_start and gid<=$pos_end),
                 1,
                 (SELECT address FROM microarrets_%ID% WHERE gid = ".$newLieux[$i]["gids"][0].")  
                )";
       
       $SQL_ADD_LIEUX[] = $strSql;
       // on stocke l'identifiant du nouveau lieu pour pouvoir le reutiliser dans la segmentation
       $newLieux[$i]["gid"] = $lieuId; 
       $lieuId++;
    }
    
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_ADD_LIEUX);
    $res = $this->execSqlCommands($sqlCommands);
    
    // phase 3
    // segmentation trajets
    // principe de l'algorithme
    // -> boucle sur les lieux : 
    //   pour chaque nouveau lieu , 
    //   --> segmentation du trajet en 2 autour du lieu (a,b) : mise jour trajet a et insertion du trajet b
    //   --> et mise à jour des references dans tables lieux et microarrets

    /*
    // table pour tests
    $sqlCommands = array(
      "DROP TABLE IF EXISTS trajets_%ID%b",
      "CREATE TABLE trajets_%ID%b AS SELECT * FROM trajets_%ID%");
    $sqlCommands = str_replace(
        array('%ID%'),
        array($data_id),
        $sqlCommands);
    $res = $this->execSqlCommands($sqlCommands);
    */
    for ($i=0; $i<count($newLieux); $i++) {  
      $currentTrackId = $newLieux[$i]["track"];
      $newTrackId = $this->splitTrack($data_id, $newLieux[$i], $currentTrackId, "");
      // il est possible de devoir mettre à jour la valeur de track dans la structure en memoire car elle peut avoir changer en base
      for ($j=$i+1;$j<count($newLieux); $j++) {
        $newLieux[$j]["track"] = ($newLieux[$j]["track"]==$currentTrackId) ? $newTrackId : $newLieux[$j]["track"];
      }
    }
    
    // renumerotation gid trajet pour suivre l'ordre des trajets
    $SQL_TRAJET_REORDER_GID = array(
      "ALTER TABLE trajets_%ID% ADD COLUMN new_gid integer",
      "UPDATE trajets_%ID% tr
        SET new_gid = info.num
        FROM 
          (SELECT 
            gid, 
            (row_number() OVER ( ORDER BY time_start ASC) - 1) AS num
          FROM
            trajets_%ID%
          ORDER BY
            time_start ASC) info
        WHERE
          info.gid = tr.gid",
      "ALTER TABLE trajets_%ID% DROP COLUMN gid",
      "ALTER TABLE trajets_%ID% RENAME COLUMN new_gid to gid"
    );
    $sqlCommands = str_replace(array('%ID%'), array($data_id), $SQL_TRAJET_REORDER_GID);
    $res = $res && $this->execSqlCommands($sqlCommands);


    // mise à jour trajets_start, trajets_end dans table lieux
    $SQL_SYNCHRO_LIEUX = array(
      "UPDATE lieux_%ID% li
       SET 
         trajets_start = info.trajets_start, 
         trajets_end = info.trajets_end
      FROM
        (select l.gid, info_lieu_s.trajets_start , info_lieu_e.trajets_end
          from 
            lieux_%ID% l
          left join 
            (select lieu_start, array_agg(gid) as trajets_start from trajets_%ID% group by lieu_start) as info_lieu_s
          on 
            l.gid = info_lieu_s.lieu_start
          left join 
            (select lieu_end, array_agg(gid) as trajets_end from trajets_%ID% group by lieu_end) as info_lieu_e
          on 
            l.gid = info_lieu_e.lieu_end   
          ) info
      WHERE li.gid = info.gid"
    );
    $sqlCommands = str_replace(array('%ID%'), array($data_id), $SQL_SYNCHRO_LIEUX);
    $res = $res && $this->execSqlCommands($sqlCommands);

    // mise à jour de la table microarrets
    $SQL_FINALIZE_MICROSTOPS = array(
      "DROP TABLE IF EXISTS microarrets_%ID%",
      "ALTER TABLE microarrets_%ID%b RENAME TO microarrets_%ID%",
      "UPDATE microarrets_%ID% ma 
        SET track = (SELECT tr.gid FROM trajets_%ID% tr WHERE ma.time_start>=tr.time_start and ma.time_end<=tr.time_end LIMIT 1)"
    );
    
    $sqlCommands = str_replace(array('%ID%'), array($data_id), $SQL_FINALIZE_MICROSTOPS);
    $res = $res && $this->execSqlCommands($sqlCommands);
    return $res;
  }
  /**
   * fonction de decoupage d'un trajet
   * 
   */
  protected function splitTrack($data_id, $newLieu, $oldTrackId, $suffix="") {
    // recuperation d'un nouvel id de trajet
    $strSql = "SELECT coalesce(max(gid),0)+1 FROM trajets_%ID%".$suffix;
    $strSql = str_replace("%ID%", $data_id, $strSql);
    $newTrackId = intval($this->dbConn->getScalarSql($strSql,1));

    // recuperation des infos du trajet à decouper
    $strSql = "select * from trajets_%ID%".$suffix." where gid=$oldTrackId";
    $strSql = str_replace("%ID%", $data_id, $strSql);
    //$this->log("Spliting $oldTrackId with lieu ".$newLieu["gid"].", introducing $newTrackId"); 
    //$this->log("newLieu " . print_r($newLieu, true)); 
    $tracks = $this->loadDataIntoArray($strSql);
    $track = $tracks[0];
    
    // mise à jour du trajet existant : redefiniton de la fin du trajet
    $trackPosEnd    =  $newLieu["pos_start"]; // A reajuster ? pos - 1 et time - period ?
    $trackTimeEnd   =  $newLieu["time_start"];
    $trackLieuEnd   =  $newLieu["gid"];
    $trackComment    =  "Découpage trajet (partie 1) suite à transformation ma en lieu $trackLieuEnd;";
    $duration = $this->duration($track['time_start'], $trackTimeEnd);
    $SQL_UPDATE_OLDTRACK = array(
      "UPDATE trajets_%ID%".$suffix. " tr 
        SET pos_end  = $trackPosEnd,
            time_end =  '".$trackTimeEnd."',
            lat_end = (SELECT lat FROM positions_%ID% WHERE gid = " . $trackPosEnd ."),
            lon_end = (SELECT lon FROM positions_%ID% WHERE gid = " . $trackPosEnd ."),
            address_end = '".$this->dbConn->analyseSql($newLieu["address"])."',
            track_duration = $duration, 
            track_length = (SELECT 
                sum(coalesce(seg_length,0))::int 
              FROM 
                positions_%ID% 
              WHERE 
                gid IN (SELECT gid FROM positions_%ID% WHERE gid>" . $track['pos_start'] ." AND gid<" . $trackPosEnd .")
              ),
            the_geom = (SELECT 
              st_makeLine(the_geom) 
              FROM 
                (SELECT the_geom FROM positions_%ID% pos
                WHERE pos.gid>" . $track['pos_start'] ." AND pos.gid<" . $trackPosEnd ." ORDER BY pos.time ASC) p),   
            lieu_end = $trackLieuEnd, 
            comments = comments || '".$this->dbConn->analyseSql($trackComment)."'::text
        WHERE gid = $oldTrackId");
    $sqlCommands = str_replace('%ID%', $data_id, $SQL_UPDATE_OLDTRACK);
    $res = $this->execSqlCommands($sqlCommands);

    // insertion nouveau trajet
    $trackPosStart    =  $newLieu["pos_end"]; // A reajuster ? pos - 1 et time - period ?
    $trackTimeStart   =  $newLieu["time_end"];
    $trackLieuStart   =  $newLieu["gid"];
    $trackComment    =  "Découpage trajet (partie 2) suite à transformation ma en lieu $trackLieuEnd;";
    $duration = $this->duration($trackTimeStart, $track['time_end']);
    $SQL_INSERT_NEWTRACK = array(
      "-- insertion nouveau trajet
      INSERT INTO trajets_%ID%".$suffix. " 
        (gid, device_id, pos_start, lat_start, lon_start, time_start, 
          address_start, pos_end, lat_end, lon_end, time_end, address_end, 
          track_duration, track_length, the_geom, lieu_start, lieu_end, 
          comments, enq_id)  
        SELECT
          $newTrackId, 
          ".$track["device_id"].",
          -- pos_start
          $trackPosStart,
          -- lat_start
          (SELECT lat FROM positions_%ID% WHERE gid = " . $trackPosStart ."),   
          -- lon_end 
          (SELECT lon FROM positions_%ID% WHERE gid = " . $trackPosStart ."),
          -- time_start
          '".$trackTimeStart."',
          -- address_start
          '".$this->dbConn->analyseSql($newLieu["address"])."',
          ".$track["pos_end"].",
          ".$track["lat_end"].",
          ".$track["lon_end"].",
          '".$track["time_end"]."',
          '".$this->dbConn->analyseSql($track["address_end"])."',
          -- track_duration
          $duration,
          -- track_length
          (SELECT 
                sum(coalesce(seg_length,0))::int 
              FROM 
                positions_%ID% 
              WHERE 
                gid IN (SELECT gid FROM positions_%ID% WHERE gid>" . $trackPosStart ." AND gid<" .  $track['pos_end'] .")
          ),
          -- the_geom
          (SELECT 
              st_makeLine(the_geom) 
              FROM 
                (SELECT the_geom FROM positions_%ID% pos
                WHERE pos.gid>" . $trackPosStart ." AND pos.gid<" .  $track['pos_end'] ." ORDER BY pos.time ASC) p
          ),
          -- lieu_start
          $trackLieuStart,
          ".$track["lieu_end"].",
          -- comments
          '".$this->dbConn->analyseSql($track["comments"])."' || '".$this->dbConn->analyseSql($trackComment)."'::text,
          enq_id        
          FROM 
          trajets_%ID%".$suffix. "
        WHERE
          gid = $oldTrackId"
    );
    
    /* old version, now mise à jour globale à la fin du traitement
    "-- mise a jour trajets_start pour le nouveau lieu qui segmente l'ancien trajet 
        --UPDATE lieux_%ID%".$suffix . "
        --  SET trajets_start = array_replace(trajets_start,$oldTrackId::text,$newTrackId::text)
        --WHERE gid=".$newLieu["gid"],
        "-- mise a jour trajets_end pour les lieux destinations de l'ancien trajet 
        --UPDATE lieux_%ID%".$suffix . "
        --  SET trajets_end = array_replace(trajets_end,$oldTrackId::text,$newTrackId::text)
        --WHERE $oldTrackId::text = ANY(trajets_end)",
        "-- mise à jour référence trajet dans microarrets
        --UPDATE microarrets_%ID%b".$suffix . "
        --  SET track = $newTrackId
        --WHERE track = $oldTrackId"
    */

    $sqlCommands = str_replace('%ID%', $data_id, $SQL_INSERT_NEWTRACK);
    $res = $this->execSqlCommands($sqlCommands);

    //die();
    return $newTrackId;

  }


  /**
   * 
   */
  protected function computePoiInfo($data_id) {
    $SQL_CREATE_POI_TABLE = array(
      "DROP TABLE IF EXISTS poi_%ID%",
      "CREATE TABLE poi_%ID% (
        id bigint, 
        lat double precision, 
        lng double precision,
        name text,
        address text,
        type int,
        the_geom geometry,
        CONSTRAINT ast_poi_%ID%_pk PRIMARY KEY (id)
      ) WITH (
        OIDS=FALSE
      )"
    );
    
    $SQL_FILL_POI_TABLE = array(
      "INSERT INTO poi_%ID% 
      SELECT
        (json_populate_recordset(null::poi_%ID%, json_extract_path(enq_json::json, 'poi'))).* 
      FROM
        astrollendro.ast_enquete 
      WHERE
        enq_id=%ID%",
      "UPDATE poi_%ID% set the_geom = st_geometryfromtext('POINT(' || lng || ' ' || lat || ')',4326)"
    );

    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_CREATE_POI_TABLE);
    $res = $this->execSqlCommands($sqlCommands);
    
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_FILL_POI_TABLE);
    $res = $res && $this->execSqlCommands($sqlCommands);

    return $res;
  }

  /**
   * check if lieu are registered poi
   */ 
  protected function mapLieuWithPoi($data_id) {
    $search_radius = $this->params["POI_MATCHING_RADIUS"];
          
    $SQL_COMPLETE_LIEU = array(
      "ALTER TABLE lieux_%ID% DROP COLUMN IF EXISTS name",
      "ALTER TABLE lieux_%ID% DROP COLUMN IF EXISTS type",
      "ALTER TABLE lieux_%ID% ADD COLUMN name text",
      "ALTER TABLE lieux_%ID% ADD COLUMN type integer"
    );
    
    $SQL_MAP_LIEU = array(
      "UPDATE lieux_%ID% li
          SET 
            name = coalesce(t.poi_name, ''),
            type = t.poi_type
        FROM  
          (SELECT
            p.name as poi_name,
            p.type as poi_type,
            l1.*
          FROM
          (SELECT 
            (SELECT 
              id 
             FROM 
              poi_%ID% p 
             WHERE 
               st_dwithin(st_transform(l.the_geom,2154), st_transform(p.the_geom,2154), " . $search_radius . ") 
             ORDER BY st_distance(st_transform(l.the_geom,2154), st_transform(p.the_geom,2154)) ASC LIMIT 1) as poi_id,
             l.*
          FROM 
            lieux_%ID% l) l1
          LEFT JOIN 
            poi_%ID% p
          ON
            l1.poi_id=p.id) t
        WHERE
          t.gid = li.gid"
    );
    

    
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_COMPLETE_LIEU);
    $res = $this->execSqlCommands($sqlCommands);
    
    $sqlCommands = str_replace(
      array('%ID%'),
      array($data_id),
      $SQL_MAP_LIEU);
    $res = $res && $this->execSqlCommands($sqlCommands);
    
    return $res;
  }
  
  /**
   * Infering transportation mode
   */
  protected function inferTransportMode($data_id) {
    $res = true;
    // Pour chaque trajet, recherche des segments piéton
    // et positionnement en mode motorise pour les autres
    $strSql = "SELECT * FROM trajets_%ID% ORDER BY time_start ASC";
    $strSql = str_replace("%ID%", $data_id, $strSql);
    //$this->log("Spliting $oldTrackId with lieu ".$newLieu["gid"].", introducing $newTrackId"); 
    //$this->log("newLieu " . print_r($newLieu, true)); 
    $tracks = $this->loadDataIntoArray($strSql);
    
    $SQL_INIT = array("
          UPDATE positions_%DATA_ID% 
            SET transport_mode = NULL");
    $sqlCommands = str_replace(array('%DATA_ID%'), array($data_id), $SQL_INIT);
    $this->execSqlCommands($sqlCommands);

    $SQL_INIT_SUBTRACK = array(
      "DROP TABLE IF EXISTS sous_trajets_%DATA_ID%",
      "CREATE TABLE sous_trajets_%DATA_ID% (LIKE sous_trajets_tpl INCLUDING ALL)");
    $sqlCommands = str_replace(array('%DATA_ID%'), array($data_id), $SQL_INIT_SUBTRACK);
    $this->execSqlCommands($sqlCommands);  

    for ($i=0; $i<count($tracks); $i++) {
      //$this->log("track " . print_r($tracks[$i], true));
      $res = $res && $this->inferTransportModeTrackPedestrian($data_id, $tracks[$i]);
      $res = $res && $this->inferTransportModeTrackMotor($data_id, $tracks[$i]);
    }
    
    // mise à jour subtrack_order dans table sous_trajets
    $SQL_REORDER_SUBTRACK = array("
      UPDATE sous_trajets_%DATA_ID% str
        SET subtrack_order = info.subtrack_order
      FROM
        (SELECT 
          gid,
          row_number() OVER (PARTITION BY track ORDER BY time_start ASC) as subtrack_order
        FROM
          sous_trajets_%DATA_ID%) info
      WHERE
        str.gid = info.gid");
    $sqlCommands = str_replace(array('%DATA_ID%'), array($data_id), $SQL_REORDER_SUBTRACK);
    $res = $res && $this->execSqlCommands($sqlCommands);

    return $res;
  }

  protected function inferTransportModeTrackPedestrian($data_id, $track) {
    $res = true;
    // parametres
    $TRANSPORTMODE_PIETON_DETECTION_CLASS  = $this->params["TRANSPORTMODE_PIETON_DETECTION_CLASS"];
    $TRANSPORTMODE_PIETON_MAX_VITESSE      = $this->params["TRANSPORTMODE_PIETON_MAX_VITESSE"];
    $TRANSPORTMODE_PIETON_CLASS            = $this->params["TRANSPORTMODE_PIETON_CLASS"];
    $TRANSPORTMODE_PIETON_MIN_DURATION     = $this->params["TRANSPORTMODE_PIETON_MIN_DURATION"];

    // Pour chaque trajet, recherche des segments piéton
    // et positionnement en mode motorise pour les autres
    $found = true; //init de boucle (pas en mode invariant, mais bon...)
    while ($res && $found) {
      $SQL_FIND_POS_MODE_PIETON = "SELECT 
          po.* 
        FROM 
          positions_%DATA_ID% po,
          (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
        WHERE
        po.time>=tinfo.time_start and po.time<=tinfo.time_end".
        (count($TRANSPORTMODE_PIETON_DETECTION_CLASS)>0 ? " AND mobility_class IN (".implode(',', $TRANSPORTMODE_PIETON_DETECTION_CLASS).") " : "").
        " AND vit<=$TRANSPORTMODE_PIETON_MAX_VITESSE
        AND transport_mode IS NULL";
      $strSql = str_replace("%DATA_ID%", $data_id, $SQL_FIND_POS_MODE_PIETON); 
      $tabPos = $this->loadDataIntoArray($strSql);
      $found = count($tabPos)>0;
      if ($found) {
        $posPieton = $tabPos[0];
        //$this->log(count($tabPos) . " posPieton " . print_r($posPieton, true));
        // recherche des positions en amont 
        $SQL_EXTEND_PIETON_BEFORE = "SELECT 
            po.* 
          FROM 
            positions_%DATA_ID% po,
            (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
          WHERE
          po.time>=tinfo.time_start AND po.time<=tinfo.time_end
          AND po.time < '". $posPieton["time"]."' 
          AND vit<=$TRANSPORTMODE_PIETON_MAX_VITESSE
          AND transport_mode IS NULL
          ORDER BY po.time DESC";
        $strSql = str_replace("%DATA_ID%", $data_id, $SQL_EXTEND_PIETON_BEFORE); 
        
        $tabPosBefore = $this->loadDataIntoArray($strSql);
        $firstPos = intval($posPieton["gid"]);
        $timeFirstPos = $posPieton["time"];
        for ($i=0;$i<count($tabPosBefore); $i++) {
          if ((intval($tabPosBefore[$i]["vit"])>$TRANSPORTMODE_PIETON_MAX_VITESSE) || 
            (($firstPos-intval($tabPosBefore[$i]["gid"]))>1)){
            break;
          }
          $firstPos = intval($tabPosBefore[$i]["gid"]);
        }

        // recherche des positions en aval 
        $SQL_EXTEND_PIETON_AFTER = "SELECT 
            po.* 
          FROM 
            positions_%DATA_ID% po,
            (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
          WHERE
          po.time>=tinfo.time_start AND po.time<=tinfo.time_end
          AND po.time > '". $posPieton["time"]."' 
          AND vit<=$TRANSPORTMODE_PIETON_MAX_VITESSE
          AND transport_mode IS NULL
          ORDER BY po.time ASC";
        $strSql = str_replace("%DATA_ID%", $data_id, $SQL_EXTEND_PIETON_AFTER); 
        $tabPosAfter = $this->loadDataIntoArray($strSql);
        $lastPos = intval($posPieton["gid"]);
        $timeLastPos = $posPieton["time"];
        for ($i=0;$i<count($tabPosAfter); $i++) {
          if ((intval($tabPosAfter[$i]["vit"])>$TRANSPORTMODE_PIETON_MAX_VITESSE)  || 
          ((intval($tabPosAfter[$i]["gid"]-$lastPos))>1)){
            break;
          }
          $lastPos = intval($tabPosAfter[$i]["gid"]);
          $timeLastPos = $tabPosAfter[$i]["time"];
        }
        
        $duration = $this->duration($timeFirstPos, $timeLastPos);
        $mode = -1;
        if ($duration>$TRANSPORTMODE_PIETON_MIN_DURATION) {
          $mode = $TRANSPORTMODE_PIETON_CLASS;
        }
        //$this->log("PEDES [Trajet] " .$track["gid"] . " Mise à jour séquence " . $firstPos. " --> " . $lastPos. " (" . $duration .") with mode $mode", true);         
        // mise a jour de la colonne pos pour le segment pieton en cours
        $SQL_UPDATE_PIETON_SEQ = array("
          UPDATE positions_%DATA_ID% 
            SET transport_mode = $mode
          WHERE gid>=$firstPos AND gid<=$lastPos"          
          );
        $sqlCommands = str_replace(
            array('%DATA_ID%'),
            array($data_id),
            $SQL_UPDATE_PIETON_SEQ);
        $res = $res && $this->execSqlCommands($sqlCommands);
        if ($mode!=-1) {
          //enregistrement de la sequence pietonne comme sous trajet
          $SQL_INSERT_SUBTRACK = array("INSERT INTO sous_trajets_%DATA_ID%
              (pos_start, time_start, pos_end, time_end, duration, track, 
              enq_id, transport_mode)
            VALUES(
              $firstPos,
              '".$timeFirstPos."',
              $lastPos,
              '".$timeLastPos."',
              $duration,
              ".$track["gid"].",
              ".$posPieton["enq_id"].",
              $mode)"
            );
          $sqlCommands = str_replace(
              array('%DATA_ID%'),
              array($data_id),
              $SQL_INSERT_SUBTRACK);
          $res = $res && $this->execSqlCommands($sqlCommands);
        }
        
      } // fin if $found
    } // fin boucle while 
    
    return $res;

  }

  protected function inferTransportModeTrackMotor($data_id, $track) {
    $res = true;
    // parametres

    $TRANSPORTMODE_MOTOR_DETECTION_CLASS   = $this->params["TRANSPORTMODE_MOTOR_DETECTION_CLASS"]; 
    $TRANSPORTMODE_MOTOR_CLASS            = $this->params["TRANSPORTMODE_MOTOR_CLASS"];
    $TRANSPORTMODE_MOTOR_MIN_DETECTION_VITESSE = $this->params["TRANSPORTMODE_MOTOR_MIN_DETECTION_VITESSE"];
    $TRANSPORTMODE_MOTOR_MIN_DURATION          = $this->params["TRANSPORTMODE_MOTOR_MIN_DURATION"];


    // Pour chaque trajet, recherche des segments piéton
    // et positionnement en mode motorise pour les autres
    $found = true; //init de boucle (pas en mode invariant, mais bon...)
    while ($res && $found) {
      $SQL_FIND_POS_MODE = "SELECT 
          po.* 
        FROM 
          positions_%DATA_ID% po,
          (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
        WHERE
        po.time>=tinfo.time_start and po.time<=tinfo.time_end".
        (count($TRANSPORTMODE_MOTOR_DETECTION_CLASS)>0 ? " AND mobility_class IN (".implode(',', $TRANSPORTMODE_MOTOR_DETECTION_CLASS).") " : "").
        " AND vit>=$TRANSPORTMODE_MOTOR_MIN_DETECTION_VITESSE
        AND transport_mode IS NULL";
      $strSql = str_replace("%DATA_ID%", $data_id, $SQL_FIND_POS_MODE); 
      $tabPos = $this->loadDataIntoArray($strSql);
      $found = count($tabPos)>0;
      
      if ($found) {
        $posPieton = $tabPos[0];
        //$this->log(count($tabPos) . " posPieton " . print_r($posPieton, true));
        // recherche des positions en amont 
        $SQL_EXTEND_BEFORE = "SELECT 
            po.* 
          FROM 
            positions_%DATA_ID% po,
            (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
          WHERE
          po.time>=tinfo.time_start AND po.time<=tinfo.time_end
          AND po.time < '". $posPieton["time"]."'". 
          (count($TRANSPORTMODE_MOTOR_DETECTION_CLASS)>0 ? " AND mobility_class IN (".implode(',', $TRANSPORTMODE_MOTOR_DETECTION_CLASS).") " : "").
          " AND (transport_mode IS NULL  OR transport_mode=-1)
          ORDER BY po.time DESC";
        $strSql = str_replace("%DATA_ID%", $data_id, $SQL_EXTEND_BEFORE); 
        //die($strSql);  
        $tabPosBefore = $this->loadDataIntoArray($strSql);
        $firstPos = intval($posPieton["gid"]);
        $timeFirstPos = $posPieton["time"];
        for ($i=0;$i<count($tabPosBefore); $i++) {
          if (($firstPos-intval($tabPosBefore[$i]["gid"]))>1){
            break;
          }
          $firstPos = intval($tabPosBefore[$i]["gid"]);
        }

        // recherche des positions en aval 
        $SQL_EXTEND_AFTER = "SELECT 
            po.* 
          FROM 
            positions_%DATA_ID% po,
            (SELECT gid, time_start, time_end FROM trajets_%DATA_ID% WHERE gid = ".$track["gid"].") tinfo
          WHERE
          po.time>=tinfo.time_start AND po.time<=tinfo.time_end
          AND po.time > '". $posPieton["time"]."'". 
          (count($TRANSPORTMODE_MOTOR_DETECTION_CLASS)>0 ? " AND mobility_class IN (".implode(',', $TRANSPORTMODE_MOTOR_DETECTION_CLASS).") " : "").
          " AND (transport_mode IS NULL OR transport_mode=-1)
          ORDER BY po.time ASC";
        $strSql = str_replace("%DATA_ID%", $data_id, $SQL_EXTEND_AFTER); 
        $tabPosAfter = $this->loadDataIntoArray($strSql);
        $lastPos = intval($posPieton["gid"]);
        $timeLastPos = $posPieton["time"];
        for ($i=0;$i<count($tabPosAfter); $i++) {
          if ((intval($tabPosAfter[$i]["gid"]-$lastPos))>1){
            break;
          }
          $lastPos = intval($tabPosAfter[$i]["gid"]);
          $timeLastPos = $tabPosAfter[$i]["time"];
        }
        
        $duration = $this->duration($timeFirstPos, $timeLastPos);
        $mode = -1;
        if ($duration>$TRANSPORTMODE_MOTOR_MIN_DURATION) {
          $mode = $TRANSPORTMODE_MOTOR_CLASS;
        }
        //$this->log("MOTOR [Trajet] " .$track["gid"] . " Mise à jour séquence " . $firstPos. " --> " . $lastPos. " (" . $duration .") with mode $mode", true);         
        // mise a jour de la colonne pos pour le segment pieton en cours
        $SQL_UPDATE_SEQ = array("
          UPDATE positions_%DATA_ID% 
            SET transport_mode = $mode
          WHERE gid>=$firstPos AND gid<=$lastPos"          
          );
        $sqlCommands = str_replace(
            array('%DATA_ID%'),
            array($data_id),
            $SQL_UPDATE_SEQ);
        $res = $res && $this->execSqlCommands($sqlCommands);
        if ($mode!=-1) {
          //enregistrement de la sequence pietonne comme sous trajet
          $SQL_INSERT_SUBTRACK = array("INSERT INTO sous_trajets_%DATA_ID%
              (pos_start, time_start, pos_end, time_end, duration, track, 
              enq_id, transport_mode)
            VALUES(
              $firstPos,
              '".$timeFirstPos."',
              $lastPos,
              '".$timeLastPos."',
              $duration,
              ".$track["gid"].",
              ".$posPieton["enq_id"].",
              $mode)"
            );
          $sqlCommands = str_replace(
              array('%DATA_ID%'),
              array($data_id),
              $SQL_INSERT_SUBTRACK);
          $res = $res && $this->execSqlCommands($sqlCommands);
        }
        
      } // fin if $found
    } // fin boucle while 
    
    
    return $res;

  }


  /**
   * fonction de synchronisation de la colonne track de la table positions
   * par rapport à la table trajets
   */
  protected function syncPositionsFromTrajets($data_id) {
    //enregistrement de la sequence pietonne comme sous trajet
    $SQL_SYNC = array(
      "UPDATE positions_%DATA_ID% 
      SET track=null",
      "UPDATE 
        positions_%DATA_ID% po 
      SET
        track = tr.gid
      FROM
        trajets_%DATA_ID% tr
      WHERE
        po.gid>=tr.pos_start
      AND
        po.gid<=tr.pos_end");
    $sqlCommands = str_replace(
        array('%DATA_ID%'),
        array($data_id),
        $SQL_SYNC);
    $res = $this->execSqlCommands($sqlCommands);
    return $res;  
  }

  //////////////////////////////////
  // MISC functions
  //////////////////////////////////
  
  protected function checkFileExists($filepath, $quiet=false) {
    $res = file_exists($filepath);
    if (!$quiet && !$res)
      error_log("Can not open file " .  $filepath);
    return $res;
  }
  
  protected function stop($exitCode=-1) {
    exit($exitCode);
  }
  
  /**
   * log a message
   * @param $msg      string  the message to log
   * @param $newLine  boolean true to add newLine after the msg
   * @param $outMode  Not used yet
   * 
   * @return void
   * 
   */
  protected function log($msg, $newLine=true, $outMode=null) {
    if ($outMode==null) {
       echo $msg;
       if ($newLine) {
         echo  "\n";
       }
    }
  }
  
  
  protected function writeTxtFile($content, $filename, $path) {
    $filepath = $path . '/' . $filename;
    $h = fopen($filepath,'w+');
    if (!($h===FALSE)) {
      fwrite($h, $content);
      fclose($h);
    }
  }
  
  
  /**
   * execute an array of commands in a transaction
   * @param   $commands   array of string the list of commands   
   * 
   * @return  boolean     true if no errors, false, if sql errors
   */
  protected function execSqlCommands($commands) {
    $res = true;
    $this->dbConn->initTransaction();
    $i=0;
    while ($res && $i<count($commands)) {
      $res = $this->dbConn->executeSql($commands[$i]);
      $i++;
    }
    if ($res)
      $this->dbConn->commitTransaction();
    else
      $this->dbConn->roolbackTransaction();
    return $res;
  }
  
  
  /**
   * load data from a query in an array
   * @param   $strSql   string  sql command  
   * 
   * @return  array     the array of values
   */
  protected function loadDataIntoArray($strSql) {
    $ds = $this->dbConn->initDataSet($strSql);
    $arr = array();
    while ($dr = $ds->getRowIter()) {
      $arr[] =array();
      foreach($dr as $key => $val) {
        $arr[count($arr)-1][$key] = $val;
      }
    }
    return $arr;
  }
  
  
  /**
   * store the tracks from trajets in the db table tablename
   * @param type $trajets
   * @param type $tablename
   */
  protected function storeTracks($trajets, $tablenameTraj, $tablenamePos) {
    $commands = array(
      "DROP TABLE IF EXISTS " . $tablenameTraj,
      "CREATE TABLE " . $tablenameTraj . " (LIKE trajets_tpl INCLUDING ALL)");
    
    for ($i=0; $i<count($trajets); $i++) {
      $t = $trajets[$i];
      $duration = $this->duration($t['time_start'], $t['time_end']);
      $strSql = "INSERT INTO $tablenameTraj (
              gid,device_id, 
              pos_start, lat_start, lon_start, time_start, 
              pos_end, lat_end, lon_end, time_end,  
              track_duration, track_length, the_geom, lieu_start, lieu_end, comments)
            VALUES (" . $t["gid"] . "," . $t["device_id"] . ",
              ".$t['pos_start'].",
              (SELECT lat FROM $tablenamePos WHERE gid = " . $t['pos_start'] ."),
              (SELECT lon FROM $tablenamePos WHERE gid = " . $t['pos_start'] ."),
              '".$t['time_start']."',
              ".$t['pos_end'].",  
              (SELECT lat FROM $tablenamePos WHERE gid = " . $t['pos_end'] ."),
              (SELECT lon FROM $tablenamePos WHERE gid = " . $t['pos_end'] ."),
              '".$t['time_end']."',  
              ".$duration.",
              (SELECT 
                  sum(coalesce(seg_length,0))::int 
                FROM 
                  $tablenamePos 
                WHERE 
                  gid IN (SELECT gid FROM $tablenamePos WHERE gid>" . $t['pos_start'] ." AND gid<" . $t['pos_end'] .")
                ),
               (SELECT 
                  st_makeLine(the_geom) 
               FROM 
                (SELECT pos.the_geom FROM $tablenamePos pos
                WHERE pos.gid>" . $t['pos_start'] ." AND pos.gid<" . $t['pos_end'] ." ORDER BY pos.time ASC) p),
               ".$t['lieu_start'].", 
               ".$t['lieu_end'].", 
               '".$t['comments'] ."' 
            );";
      $commands[] = $strSql;
    }
    return $this->execSqlCommands($commands);
  }
  
  
  protected function launchProcess($processName, $params, $processDesc, $log=true) {
    $time_start = microtime(true);
    if ($log)
      $this->log($processDesc . "...", false);
    
    $res = call_user_func_array(array($this,$processName), $params);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    $time = number_format($time, 2);
    if ($log)
      $this->log(($res ? "OK" : "KO") . ' in ' . $time . ' s.');
    return $res;
  }
  
  protected function printSqlCommands($commands) {
    $strSql = implode(';\n', $commands);
    echo $strSql;
  }
  
  protected static function duration($strDateTime1, $strDateTime2) {
    $d1 = DateTime::createFromFormat('Y-m-d H:i:s', $strDateTime1);
    $d2 = DateTime::createFromFormat('Y-m-d H:i:s', $strDateTime2);
    $duration = $d2->getTimestamp() - $d1->getTimestamp();
    return $duration;
  }
  
  protected function computeDistance($lat1,$lon1,$lat2, $lon2) {
    $strSql = "SELECT ST_Distance("
                  . "ST_Transform(ST_SetSrid(ST_MakePoint($lon1,$lat1),4326),2154),"
                  . "ST_Transform(ST_SetSrid(ST_MakePoint($lon2,$lat2),4326),2154)) as distance";
    $d = $this->dbConn->getScalarSql($strSql,0);
    return $d;
  }
  
  ////////////////////////////////
  // Test functions
  ////////////////////////////////
  
  
  public function cleanDb() {
    $commands = array(
      "TRUNCATE enquetes",
      "ALTER SEQUENCE enquetes_enquete_id_seq RESTART WITH 1"
    );
    
    $strSql = "select table_name "
      . "from  information_schema.tables "
      . "where table_schema='public' and "
      . "(table_name ilike 'positions_%' and table_name!='positions_tpl')"
      . "OR "
      . "(table_name ilike 'infos_%' and table_name!='infos_tpl')"
      . "OR "
      . "(table_name ilike 'trajets_%' and (table_name!='trajets_tpl' and table_name!='trajets_rec' and  not (table_name ilike 'trajets_rec%')))"
      . "OR "
      . "(table_name ilike 'trajets_rec_%' and table_name!='trajets_rec_tpl')"
      . "OR "
      . "(table_name ilike 'lieux_%' and table_name!='lieux_tpl')"
      . "OR "
      . "(table_name ilike 'microarrets_%' and table_name!='microarrets_tpl' and table_name!='microarrets_info_tpl')"
      . "OR "
      . "(table_name ilike 'pos_clusters_%')"
      . "OR "
      . "(table_name ilike 'pos_clusters_aggregate_%')"
      . "OR "
      . "(table_name ilike 'sequences_%')";
    $ds = $this->dbConn->initDataSet($strSql);
    while ($dr = $ds->getRowIter()) {
        $tName = $dr->getValueName('table_name');
        $commands[] = "DROP TABLE IF EXISTS " .$tName . " CASCADE";
    }
    
    $res = $this->execSqlCommands($commands);  
    return $res;
  }
  
}
?>
