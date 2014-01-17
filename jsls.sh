#! /bin/bash

dir="$1"
out=$(./jasperserver.php -m list -p ${dir})
echo -e "$out"