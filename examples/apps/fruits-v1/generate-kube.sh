#!/bin/bash

# Generate Kubernetes resources

set -o errexit
set -o nounset

export LC_ALL=en_US.UTF-8

SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"

kustomize build ${SOURCE_DIR}/Secret > ${SOURCE_DIR}/kubernetes/03-secret.yaml