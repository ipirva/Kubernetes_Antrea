#!/bin/bash

# Generate certificate for the app

set -o errexit
set -o nounset

export LC_ALL=en_US.UTF-8

SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
OUTPUT_DIR=$SOURCE_DIR/Secret/TLS
rm -rf OUTPUT_DIR=$SOURCE_DIR/Secret/TLS > /dev/null 2>&1
mkdir -p $OUTPUT_DIR > /dev/null 2>&1

openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ${OUTPUT_DIR}/tls.key -out ${OUTPUT_DIR}/tls.crt -subj "/CN=mytest.com/O=mytest.com"
