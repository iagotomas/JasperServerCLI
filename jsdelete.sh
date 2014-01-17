#! /bin/bash

dir="$1"
folder=$(basename "$dir")
parent=$(dirname "$dir")
out=$(./jasperserver.php -m delete -p ${parent} -c ${folder})
echo -e "$out"