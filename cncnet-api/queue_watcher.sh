#!/bin/sh
set -x

cd "$(dirname "$0")"

if [ "${PHP}x" = "x" ]
then
   PHP=php
fi

queue=$1

if [ "${queue}x" = "x" ]
then
    queue=default
fi

PIDDIR=storage/pids/
PIDFILE=$PIDDIR/queue_$queue.pid

PID=$(cat $PIDFILE)

if ! kill -0 $PID
then
    $PHP artisan queue:work database --daemon --queue=$queue &
    PID=$!
fi

echo $PID >$PIDFILE
