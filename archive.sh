#!/bin/bash
#Скрипт для архивирования файлов CDR
#
#
#путь до папки с маской файлов
MASK_FILE=/srv/cucm/archive/cdr_*
for i in $MASK_FILE; do 
#echo "$i"
#если изменить -c1-7 на -c1-10 архивы будут собираться по дням
DATECREATE=`stat -c %y "$i" | cut -c1-7`
zip -9 cdr_arc"$DATECREATE".zip "$i"
if (($? == 0)); then 
rm "$i"
fi
done
