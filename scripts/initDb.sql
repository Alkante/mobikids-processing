
DROP TABLE IF EXISTS enquetes CASCADE;

CREATE TABLE enquetes
(
  enquete_id serial NOT NULL,
  enquete_trackfilename text,
  CONSTRAINT pk_enquete PRIMARY KEY (enquete_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE enquetes
  OWNER TO user_astrollendro;


DROP TABLE IF EXISTS positions_tpl CASCADE;
CREATE TABLE positions_tpl
(
  gid integer,
  the_geom geometry(Point,4326),
  "time" timestamp without time zone,
  "timestamp" integer,
  lat double precision,
  lon double precision,
  nbsat integer,
  alt integer,
  hdop double precision,
  cap integer,
  vit integer,
  pos_nearest_start_stop integer,
  time_nearest_start_stop  timestamp without time zone,
  duration_nearest_start_stop integer,
  seg_geom geometry,
  seg_length numeric,
  seg_duration numeric,
  address_full text,
  address_light text,
  time_info text,
  msg_info text,
  trackmode int4,	
  track int4
)
WITH (
  OIDS=FALSE
);
ALTER TABLE positions_tpl
  OWNER TO user_astrollendro;

DROP TABLE IF EXISTS infos_tpl CASCADE;
CREATE TABLE infos_tpl(gid serial NOT NULL, msg text, time timestamp without time zone);
ALTER TABLE infos_tpl
  OWNER TO user_astrollendro;

DROP FUNCTION IF EXISTS ast_positioninfo(int8);
CREATE OR REPLACE FUNCTION ast_positioninfo(pos int8, tablename text)
  RETURNS numeric AS
$BODY$
declare previous_pos int8;
  begin
      previous_pos := -1;
      EXECUTE 'select gid from ' || tablename || ' where time < (select time from ' || tablename || ' where gid='|| pos || ') order by time desc limit 1' INTO previous_pos;
      
      if (previous_pos!=(-1)::int8) then
          EXECUTE 'update 
            ' || tablename || '
          set
            seg_geom = st_makeline((select the_geom from ' || tablename || ' where gid=' || previous_pos || '), the_geom),
            seg_length = st_length(st_transform(st_makeline((select the_geom from ' || tablename || ' where gid=' || previous_pos || '), the_geom), 2154)),
            seg_duration = (extract( ''epoch'' from time) - (select extract( ''epoch'' from time) from ' || tablename || ' where gid=' || previous_pos || '))::int8 
          where
            gid=' || pos || ''
        USING tablename, previous_pos, pos;
      end if;
      return previous_pos;
  end;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION ast_positioninfo(int8,text)
  OWNER TO user_astrollendro;

drop table if exists trajets_tpl CASCADE;   
CREATE TABLE trajets_tpl
(
  gid serial NOT NULL,
  device_id integer,
  pos_start bigint,
  lat_start double precision,
  lon_start double precision,
  time_start timestamp without time zone,
  address_start text,
  pos_end bigint,
  lat_end double precision,
  lon_end double precision,
  time_end timestamp without time zone,
  address_end text,
  track_duration numeric,
  track_length numeric,
  the_geom geometry,
  lieu_start int,
  lieu_end int,
  comments text;
  CONSTRAINT pk_trajets PRIMARY KEY (gid)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE trajets_tpl
  OWNER TO user_astrollendro;
CREATE INDEX idx_geom_trajets
  ON trajets_tpl
  USING gist
  (the_geom);

drop table if exists lieux_tpl CASCADE;   
CREATE TABLE lieux_tpl
(
  gid serial NOT NULL,
  pos_list int[],
  address text,
  the_geom geometry,
  lat double precision,
  lon double precision,
  origin text,
  trajets_start text,
  trajets_end text,
  CONSTRAINT pk_lieux PRIMARY KEY (gid)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE lieux_tpl
  OWNER TO user_astrollendro;
CREATE INDEX idx_geom_lieux
  ON lieux_tpl
  USING gist
  (the_geom);


drop table if exists microarrets_tpl CASCADE;   
CREATE TABLE microarrets_tpl
(
  gid serial NOT NULL,
  address text,
  the_geom geometry,
  lat double precision,
  lon double precision,
  pos_start integer,
  time_start timestamp without time zone,
  pos_end integer,
  time_end timestamp without time zone,
  duration integer,
  radius numeric,
  stop_type text,
  track integer,
  CONSTRAINT pk_microarrets PRIMARY KEY (gid)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE microarrets_tpl
  OWNER TO user_astrollendro;
CREATE INDEX idx_geom_microarrets
  ON microarrets_tpl
  USING gist
  (the_geom);



CREATE OR REPLACE FUNCTION _final_median(NUMERIC[])
   RETURNS NUMERIC AS
$$
   SELECT AVG(val)
   FROM (
     SELECT val
     FROM unnest($1) val
     ORDER BY 1
     LIMIT  2 - MOD(array_upper($1, 1), 2)
     OFFSET CEIL(array_upper($1, 1) / 2.0) - 1
   ) sub;
$$
LANGUAGE 'sql' IMMUTABLE;
 
CREATE AGGREGATE median(NUMERIC) (
  SFUNC=array_append,
  STYPE=NUMERIC[],
  FINALFUNC=_final_median,
  INITCOND='{}'
);
