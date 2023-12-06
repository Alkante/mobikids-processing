#!/bin/bash

PWD=`pwd`;
SCRIPT=`readlink -f $0`;
SCRIPT_DIR=`dirname "$SCRIPT"`;

ROOT_DATA_DIR=/mobikids_data/
COMMAND_DIR=$SCRIPT_DIR
COMMAND="trace_processing.php"
LOG_FILENAME="trace_processing.log"


echolog()                                                                                                                                                                                                                                    
{                                                                                                                                                                                                                                            
echo "`date '+%b %d %H:%M:%S'`  ${1}"                                                                                                                                                                                                        
}                                                                                                                                                                                                                                            

echolog "Starting cron"
for FILE in  $(find $ROOT_DATA_DIR -type f -mtime -3 \( -iname \*.dat -o -iname \*.zip \) -not -path "*/dat/*");
  do
    who_has_it=$(lsof $FILE)
    # on verifie que le fichier n'est pas en écriture
    if [[ -z $who_has_it ]]; then
      DATA_DIR=`dirname "$FILE"`;
      echolog "looking at $DATA_DIR" >> "${ROOT_DATA_DIR}/${LOG_FILENAME}"
      # si le fichier n'est pas traité, on lance le traitement
      if  [ ! -f $DATA_DIR/.process ] && [ ! -f $ROOT_DATA_DIR/.process ] ; 
      then
           if [ "${FILE##*.}" = "zip" ]
           then
             echolog "Found zip file $FILE. Unzipping it..."
             unzip -o $FILE -d $DATA_DIR && rm $FILE
           fi
           echolog "Processing $DATA_DIR" >> "${ROOT_DATA_DIR}/${LOG_FILENAME}"
           # on marque le process en cours
           touch $DATA_DIR/.process
           # on marque un process general en cours
           touch $ROOT_DATA_DIR/.process
           # launching process
           #echo "launching process php -f $COMMAND_DIR/$COMMAND $DATA_DIR $FILE"
           #su - www-data -c "php -f $COMMAND_DIR/$COMMAND $DATA_DIR $FILE >> ${ROOT_DATA_DIR}/${LOG_FILENAME} 2>&1 &"
           php -f $COMMAND_DIR/$COMMAND $DATA_DIR >> ${ROOT_DATA_DIR}/${LOG_FILENAME} 2>&1 &
      else echolog "$ROOT_DATA_DIR/.process exists"
      fi
    fi
  done;
echolog "Ending cron"
exit 0;


