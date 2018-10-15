#!/bin/sh
set -x

cd "$(dirname "$0")"

if [ "${PHP}x" = "x" ]
then
   PHP=php
fi

PIDDIR=storage/pids/
PIDFILE=$PIDDIR/queue_runner.pid

PID=$(cat $PIDFILE)

if ! kill -0 $PID
then
    $PHP artisan queue:work database --daemon &
    PID=$!
fi

echo $PID >$PIDFILE
