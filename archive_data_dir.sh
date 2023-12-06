#!/bin/bash

ROOT_DATA_DIR=/mobikids_data/

PWD=`pwd`;
SCRIPT=`readlink -f $0`;
SCRIPT_DIR=`dirname "$SCRIPT"`;

echolog()                                                                                                                                                                                                                                    
{                                                                                                                                                                                                                                 
echo "`date '+%b %d %H:%M:%S'`  ${1}"                                                                                                                                                                                                        
}                                                                                                                                                                                                                                 

echolog "Starting archiving data directories"
cd $ROOT_DATA_DIR;
for DIR in  $(find . -maxdepth 1 -type d -name "ast*");
  do
    echolog "Processing $DIR";
    tar cvzf $DIR.tar.gz $DIR;
    if [ $? -ne 0 ]; then
      echo "Problem while creating archive file";
      exit -1;
    else
      echolog "Testing $DIR.tar.gz";
      tar tf $DIR.tar.gz
      if [ $? -ne 0 ]; then
        echolog "Problem in testing archive file";
        exit -1;
      else
        echolog "Removing $DIR";
        rm -rf $DIR;
      fi
    fi
  done;
echolog "Ending archiving data directories"
cd $PWD;
exit 0;

