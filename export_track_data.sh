#!/bin/bash

PG_HOST=db_mobikids_host
PG_USER=db_mobikids_user
PG_PASSWORD=db_mobikids_password
PG_PORT=db_mobikids_port
PG_DBNAME=db_mobikids_name

TIMEFORMAT='%3R'

PWD=`pwd`;
SCRIPT=`readlink -f $0`;
SCRIPT_DIR=`dirname "$SCRIPT"`;

# checking args
if [ "$#" -lt 2 ]; then
  echo "Usage : $0 outputdir numenquete" 
  exit -1
fi


OUT_DIR_BASE=`readlink -f $1`;
NUM_ENQUETE=$2;

#rm -rf out/*
LOGFILE=$OUT_DIR_BASE/`basename $0`"_error.log"
#rm $LOGFILE
echo `date` > $LOGFILE

# Iterating over id
for ID in `seq $NUM_ENQUETE $NUM_ENQUETE`
 do 
 echo "Exporting data for enquete $ID";
  OUT_DIR=$OUT_DIR_BASE/export
  rm -r $OUT_DIR 2>>$LOGFILE
  mkdir $OUT_DIR 2>>$LOGFILE
 
  # positions
  T_NAME="positions"
  NAME=$T_NAME"_"$ID
  echo " --> exporting $NAME" 
  ## cleaning 
  MASK=$OUT_DIR/$NAME".*" 
  rm $MASK 2>>$LOGFILE
  ## export Shapefile
  ogr2ogr -f "Esri shapefile" $OUT_DIR/$NAME.shp "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, time::text, timestamp, lat, lon, nbsat,alt, hdop, cap, vit, seg_length, seg_duration as seg_duree, 
  time_info::text, msg_info::text, trackmode, track, the_geom FROM $NAME" -a_srs EPSG:4326 -lco RESIZE=YES -nln $T_NAME 2>>$LOGFILE
  ## export KML
  ogr2ogr -f "KML" $OUT_DIR/$NAME.kml "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, time::text, timestamp, lat, lon, nbsat,alt, hdop, cap, vit, seg_length, seg_duration as seg_duree, 
  time_info::text, msg_info::text, trackmode, track, the_geom FROM $NAME" -nln $T_NAME 2>>$LOGFILE
  
  # lieux
  T_NAME="lieux"
  NAME=$T_NAME"_"$ID
  echo " --> exporting $NAME" 
  ## cleaning 
  MASK=$OUT_DIR/$NAME".*" 
  rm $MASK 2>>$LOGFILE
  ## export Shapefile
  ogr2ogr -f "Esri shapefile" $OUT_DIR/$NAME.shp "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT * FROM $NAME"  -lco RESIZE=YES -nln $T_NAME 2>>$LOGFILE
  ## export KML
  ogr2ogr -f "KML" $OUT_DIR/$NAME.kml "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT * FROM $NAME"  -nln $T_NAME 2>>$LOGFILE
  ## export ODS/XLS specifique pour chaque table 
  ogr2ogr -f "ODS" $OUT_DIR/$NAME.ods "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, address, lat, lon, ''::text as commentaire, ''::text as semantique from $NAME order by gid asc" -nln $T_NAME 2>>$LOGFILE

  # trajets
  T_NAME="trajets"
    NAME=$T_NAME"_"$ID
  echo " --> exporting $NAME" 
  ## cleaning 
  MASK=$OUT_DIR/$NAME".*" 
  rm $MASK 2>>$LOGFILE
  ## export Shapefile
  ogr2ogr -f "Esri shapefile" $OUT_DIR/$NAME.shp "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid,pos_start, lat_start, lon_start, time_start::text, address_start as ad_start, 
      pos_end, lat_end, lon_end, time_end::text, address_end as ad_end, 
      track_duration::int as duree_s, track_length::int as longueur_m, lieu_start, lieu_end, comments, the_geom FROM $NAME ORDER BY gid ASC" \
      -a_srs EPSG:4326 -lco RESIZE=YES -nln $T_NAME 2>>$LOGFILE
  ## export KML
  ogr2ogr -f "KML" $OUT_DIR/$NAME.kml "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, pos_start, lat_start, lon_start, time_start::text, address_start as ad_start, 
      pos_end, lat_end, lon_end, time_end::text, address_end as ad_end, 
      track_duration::int as duree_s, track_length::int as longueur_m, lieu_start, lieu_end, comments, the_geom FROM $NAME 
      order by time_start asc" -nln $T_NAME 2>>$LOGFILE
  ## export ODS/XLS specifique pour chaque table 
  ogr2ogr -f "ODS" $OUT_DIR/$NAME.ods "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, address_start as ad_start, time_start::text, address_end as ad_end, time_end::text, comments, ''::text as commentaire from $NAME 
  order by time_start asc" -nln $T_NAME 2>>$LOGFILE
  
  # microarrets
  T_NAME="microarrets"
  NAME=$T_NAME"_"$ID
  NAME_INFO=$T_NAME"_info_"$ID
  OUT_NAME="arrettransitoires_"$ID
  echo " --> exporting $NAME" 
  ## cleaning 
  MASK=$OUT_DIR/$NAME".*" 
  rm $MASK 2>>$LOGFILE
  ## export Shapefile
  ogr2ogr -f "Esri shapefile" $OUT_DIR/$OUT_NAME.shp "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT 
          ma.gid, ma.address, ma.lat, ma.lon, ma.pos_start, ma.time_start::text, ma.pos_end, ma.time_end::text, ma.track, ma.duration, ma.radius, ma.stop_type::text, ma.the_geom, 
          mai.nb_sat_moy, mai.indoor, array_length(mai.poi_ids,1) as poi_count, mai.recurrence, mai.in_time_slot, mai.weekend, mai.motion_level_med, mai.score, mai.score_order 
        FROM 
          $NAME ma
        LEFT JOIN 
          $NAME_INFO mai 
        ON 
          ma.gid = mai.gid
        ORDER BY 
          mai.score_order ASC
        " \
  -a_srs EPSG:4326 -lco RESIZE=YES -nln $T_NAME 2>>$LOGFILE
  ## export KML
  ogr2ogr -f "KML" $OUT_DIR/$OUT_NAME.kml "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT * FROM $NAME" -nln $T_NAME 2>>$LOGFILE
  ## export ODS/XLS specifique pour chaque table 
  ogr2ogr -f "ODS" $OUT_DIR/$OUT_NAME.ods "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, address, lat, lon, time_start::text, time_end::text, track, duration, radius, stop_type::text, ''::text as commentaire from $NAME order by time_start asc" -nln $T_NAME 2>>$LOGFILE
  
  # sequences
  T_NAME="sequences"
  NAME=$T_NAME"_"$ID
  echo " --> exporting $NAME" 
  ## cleaning 
  MASK=$OUT_DIR/$NAME".*" 
  rm $MASK 2>>$LOGFILE
  ## export ODS/XLS specifique pour chaque table 
  ogr2ogr -f "ODS" $OUT_DIR/$NAME.ods "PG:host=$PG_HOST user=$PG_USER password=$PG_PASSWORD dbname=$PG_DBNAME" $NAME \
  -sql "SELECT gid, type, ref, time_start::text, time_end::text, duration, address, ''::text as commentaire from $NAME order by gid asc" -nln $T_NAME 2>>$LOGFILE
  
 done;
exit 0;
