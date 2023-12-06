#!/bin/bash

php -f data_processing.php clean
for f in data/*.dat
 do
        echo Processing $f
        php -f data_processing.php $f ./dataExtraction
done
