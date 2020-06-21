.DEFAULT_GOAL := help
.ONESHELL:
SHELL:=/usr/bin/env bash

KIND_CLUSTER_NAME := ${KIND_CLUSTER_NAME}
KINDCFG := ${PWD}/kind/kind-configuration.yaml

KUBECONFIG := $(cat ${PWD}/kubeconfig-path)
export KUBECONFIG

.PHONY: help
help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'


.PHONY: kind
kind: ## Get Kind clusters and nodes
	kind get clusters && \
	kind get nodes

.PHONY: kind_create
kind_create: ## Install Control plane K8S
	kind get clusters | grep ${KIND_CLUSTER_NAME} || \
	kind create cluster --name ${KIND_CLUSTER_NAME} --config $(KINDCFG) && \
	kubectl cluster-info --context kind-${KIND_CLUSTER_NAME}

.PHONY: kind_delete
kind_delete: ## Delete Control plane K8S
	kind delete cluster --name ${KIND_CLUSTER_NAME} && \
	kind get clusters

define taskawsbootstrapdelete
	# delete any previously created programmatic AWS access keys for ${AWS_BOOTSTRAP_USER}
	for key in $(aws --profile=vmw --region=${AWS_REGION} iam list-access-keys --user-name ${AWS_BOOTSTRAP_USER} | jq -r '.["AccessKeyMetadata"][]."AccessKeyId"'); \
		do \ 
			aws --profile=vmw --region=${AWS_REGION} iam delete-access-key --user-name ${AWS_BOOTSTRAP_USER} --access-key-id ${key}; \
		done;\
	aws --profile=vmw --region=${AWS_REGION} cloudformation delete-stack --stack-name ${AWS_CLOUDFORMATION_STACK}
endef

.PHONY: aws_bootstrap_delete
aws_bootstrap_delete: ## Delete bootstraped AWS environment
	$(value taskawsbootstrapdelete)

.PHONY: clusterctl_init_dry
clusterctl_init_dry: ## clusterctl init DRY RUN
	clusterctl version
	clusterctl config repositories
	clusterctl init --infrastructure aws --list-images

.PHONY: clusterctl_init
clusterctl_init: ## clusterctl init
	clusterctl init --infrastructure=aws:${CAPA_VERSION} --core=cluster-api:${CAPI_VERSION} --v 10
	kubectl get pods -A

define taskclusterctlconfig
	rm -rf ${PWD}/clusters/base/cluster-template.*; \
	mkdir -p ${PWD}/clusters/base; \
	# clusterctl.yaml contains variables needed for the Cluster Template
	# clusterctl config cluster base --list-variables --infrastructure aws:${CAPA_VERSION}
	clusterctl config cluster base -n base --config clusterctl.yaml --infrastructure aws:${CAPA_VERSION} > ${PWD}/clusters/base/cluster-template.yaml
endef

.PHONY: clusterctl_config
clusterctl_config: ## clusterctl config base cluster
	$(value taskclusterctlconfig)
	
define taskcleanall
	# delete any custom aws configuration before (aws_custom.sh)
	# delete worker cluster
	# delete cloudformation stack
	# delete control plane clusters
	kubectl delete cluster ${CLUSTER_NAME} | true; \
	aws --profile=vmw --region=${AWS_REGION} cloudformation delete-stack --stack-name ${AWS_CLOUDFORMATION_STACK} | true; \
	kind get clusters | xargs -I NAME kind delete cluster --name=NAME | true; \
	rm -rf ${PWD}/kubeconfigs; \
	rm -rf ${PWD}/.kubeconfig
endef

.PHONY: clean_all
clean_all: ## Clean all the local environment
	$(value taskcleanall)

define taskkubeconfig
	rm -rf ${PWD}/kubeconfigs; \
	rm -f ${PWD}/.kubeconfig && touch ${PWD}/.kubeconfig; \
	mkdir -p ${PWD}/kubeconfigs/; \
	for k in $(kind get clusters); \
		do \
			kind get kubeconfig --name=${k} > ${PWD}/kubeconfigs/${k}-control; \
		done; \
	kubectl --namespace=${CLUSTER_NAME} get secret/${CLUSTER_NAME}-kubeconfig -o json | jq -r .data.value | base64 --decode >> ${PWD}/kubeconfigs/${k}-worker; \
	KUBECONFIG=""; \
	for k in $(ls ${PWD}/kubeconfigs); \
		do \
			KUBECONFIG=$(echo ${KUBECONFIG})${PWD}/kubeconfigs/${k}:; \
		done; \
	KUBECONFIG=${KUBECONFIG} kubectl config view --merge --flatten > ${PWD}/.kubeconfig; \
	KUBECONFIG=${PWD}/.kubeconfig kubectl config get-contexts
endef

.PHONY: kubeconfig_export
kubeconfig_export: ## Export KUBECONFIG parameters
	$(value taskkubeconfig)