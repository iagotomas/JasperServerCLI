#! /bin/bash
dir="$1"
filename=$(basename "$dir")
out=$(./jasperserver.php -m get -t dummy -p ${dir} -c ${filename})
echo -e "$out"