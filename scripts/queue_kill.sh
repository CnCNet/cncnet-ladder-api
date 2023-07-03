#!/bin/sh
set -x

PIDDIR=storage/pids/

for file in $(ls $PIDDIR/*.pid)
do
    kill $(cat $file)
done
