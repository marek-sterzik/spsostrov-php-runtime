#!/bin/bash

if [ ! -f "/tmp/envdump.json" ]; then
    bootenv
fi

source envdump
envdump --restore

args=("$@")
if [ "$#" = 0 ]; then
    args+=(bash)
fi
if [ "$SERVER_UID" != "`id -u`" -o "$SERVER_GID" != "`id -g`" ]; then
    runas $RUNAS_ARGS --home /tmp -- "${args[@]}"
else
    export HOME="/tmp"
    "$@"
fi
