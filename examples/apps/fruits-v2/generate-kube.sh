#!/bin/bash

# Generate Kubernetes Deployment Resources

set -o errexit
set -o nounset

SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
OUTPUT_DIR=${OUTPUT_DIR:-${SOURCE_DIR}}
K8S="$OUTPUT_DIR/kubernetes"

ES="$K8S/elasticsearch"
FE="$K8S/frontend"

ES_GENERATED_FILE=${K8S}/_generated_deployment_es/elasticsearch.yaml
FE_GENERATED_FILE=${K8S}/_generated_deployment_fe/frontend.yaml

rm -rf $K8S/_generated_deployment_es > /dev/null 2>&1
rm -rf $K8S/_generated_deployment_fe > /dev/null 2>&1

mkdir -p $K8S/_generated_deployment_es > /dev/null 2>&1
mkdir -p $K8S/_generated_deployment_fe > /dev/null 2>&1

kustomize build "$ES" | envsubst >> "${ES_GENERATED_FILE}"
kustomize build "$FE" | envsubst >> "${FE_GENERATED_FILE}"