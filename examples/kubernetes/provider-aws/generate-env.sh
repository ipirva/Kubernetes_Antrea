#!/bin/bash

# Generate the env variables to be used for the Cluster's creation

set -o errexit
set -o nounset

export LC_ALL=en_US.UTF-8

export SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
export OUTPUT_DIR=${OUTPUT_DIR:-${SOURCE_DIR}}
export PROVIDER_DIR="$OUTPUT_DIR/examples/kubernetes/provider-aws"

export CLUSTER_GENERATED_FILE=${PROVIDER_DIR}/_generated_deployment/create-cluster.yaml
export CONTROLPLANE_GENERATED_FILE=${PROVIDER_DIR}/_generated_deployment/create-cluster-machine.yaml
export MACHINEDEPLOYMENT_GENERATED_FILE=${PROVIDER_DIR}/_generated_deployment/create-cluster-machine-deployment.yaml

rm -rf $PROVIDER_DIR/_generated_deployment > /dev/null 2>&1
mkdir -p $PROVIDER_DIR/_generated_deployment > /dev/null 2>&1

export CLUSTER_NAME=mycluster
export CLUSTER_CIDR_BLOCK=192.168.0.0/16
export CLUSTER_AWS_REGION=eu-west-2
export CLUSTER_SSH_KEY=mycluster-ssh
export CLUSTER_K8S_VER=v1.17.2
export CLUSTER_AWS_EC2_CONTROL_INST=t3.medium
export CLUSTER_AWS_EC2_WORKER_INST=t3.medium
# Defined during the cluster bootstrap - prerequisites
# Used during the cluster creation/deployment in the Machine and MachineDeployment definitions
export AWS_CUSTOM_SG=${AWS_CUSTOM_SG:-""}
# Export components versions
export CERT_MANAGER_VER=v0.13.0
export CAPI_VER=v0.2.10
export CAPBPK_VER=v0.1.6
export CAPA_VER=v0.4.9
export ANTREA_VER=v0.4.1

# Export env variables for AWS admin account
export AWS_REGION=eu-west-2
export AWS_ACCESS_KEY_ID=xxx
export AWS_SECRET_ACCESS_KEY=xxx
