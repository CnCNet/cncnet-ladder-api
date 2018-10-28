#!/bin/sh
set -x

queue=$1

if [ "${queue}x" = "x" ]
then
    queue=default
fi

PIDDIR=storage/pids/
PIDFILE=$PIDDIR/queue_$queue.pid

rm -f $PIDFILE
