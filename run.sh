#!/bin/bash

# to be executeable dont forget to chmod +x this file.

# Crontab may look like this
# Run backup every day a 9pm
#0 21 * * * /Users/XYZ/Sites/APP/cli/backup.sh >/dev/null 2>&1

# get the previous weekdate from the current date
DAY_OF_WEEK="date +%w"
if [ DAY_OF_WEEK = 0 ] ; then
  LOOK_BACK=2
elif [ DAY_OF_WEEK = 1 ] ; then
  LOOK_BACK=3
else
  LOOK_BACK=1
fi

PREV_DATE=`date -v -"$LOOK_BACK"d +"%Y%m%d";`

# path to the .csv file with stock data
PATH_TO_CSV="/Users/sah-comp/Downloads/TK" 

php -f index.php -- -f $PATH_TO_CSV$PREV_DATE.csv
