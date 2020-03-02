#!/bin/bash

# Generate certificate for the app

set -o errexit
set -o nounset

export LC_ALL=en_US.UTF-8

SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
OUTPUT_DIR=${OUTPUT_DIR:-${SOURCE_DIR}}

DIR=$OUTPUT_DIR/kubernetes/frontend

mkdir -p $DIR/certificate > /dev/null 2>&1

CRT=$DIR/certificate/tls.crt
KEY=$DIR/certificate/tls.key

openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout $KEY -out $CRT -subj "/CN=myfruitslibrary.com/O=myfruitslibrary.com"
