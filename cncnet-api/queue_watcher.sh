#!/bin/sh
set -x

cd "$(dirname "$0")"

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
    docker exec cncnet_ladder_app php artisan queue:work database --daemon --queue=$queue &
    PID=$!
fi

echo $PID >$PIDFILE
