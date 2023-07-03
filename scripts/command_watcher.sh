#!/bin/sh
set -x

cd "$(dirname "$0")"

command=$1

if [ "${command}x" = "x" ]
then
    command=default
fi

PIDDIR=storage/pids/
PIDFILE=$PIDDIR/command_$command.pid

PID=$(cat $PIDFILE)

if ! kill -0 $PID
then
    docker exec cncnet_ladder_app php artisan $command &
    PID=$!
fi

echo $PID >$PIDFILE
