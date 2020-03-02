# Bootstrap a Kubernetes Cluster

## Kuberenetes cluster

Cluster API are used to deploy a k8s cluster on the provider AWS.

- CNI: Antrea
- CSI: AWS EFS
- Ingress Controller: NGINX

I am using macOS Catalina with pkg manager "brew" and the following tools:

```bash
Docker desktop - install

brew update && brew upgrade

brew install python3
brew install git
brew install jq
brew install base64
brew install gettext
brew install tree

brew install kubectl
brew install kind
brew install clusterawsadm
brew install kustomize

pip3 install awscli --upgrade --user
```

Pull the Git repo:

```bash
DIR=$(pwd)
GIT_REP=Kubernetes_Antrea

git pull https://github.com/ipirva/Kubernetes_Antrea.git
```

## Environment set variables

Change accordignly the variables inside generate-env.sh.

```bash
WORK_DIR=${DIR}/${GIT_REP}/examples/kubernetes/provider-aws
export WORK_DIR=$WORK_DIR

cd ${WORK_DIR}
$(where bash) generate-env.sh
```

## Set AWS configuration and credentials files (aws cli)

```bash
cat <<EOF >>~/.aws/config
[profile vmw]
region = eu-west-2
output = json
EOF

cat <<EOF >>~/.aws/credentials
[vmw]
aws_access_key_id=${AWS_ACCESS_KEY_ID}
aws_secret_access_key=${AWS_SECRET_ACCESS_KEY}
EOF
```

## Pre-requisites AWS (Mgmt console) - Phase 1

Phase 1 can be run at this very step.

1. Create Access Secret key for the AWS admin account;
2. Create a SSH Key pair (EC2 / Network & Security / Key Pairs) e.g. mycluster-ssh;

## Pre-requisites AWS (Mgmt console) - Phase 2

Phase 2 must be run right after the step _Create the managed k8s cluster on AWS / Describe the cluster_ described further below.
The reason is that the VPC AWS object (i.e.: ${CLUSTER_NAME}-vpc) must be created before.

1. Create a Security Group to allow the CNI overlay traffic to flow between the Kubernetes nodes;

In case the Overlay will be VXLAN, the security group should allow Inbound:
_Type = Custom UDP Rule / Protocol = UDP / Port range = 4789 / Source = [the SG itself] / Description = Overlay traffic_

In this example, the Security Group name is chosen to be ${CLUSTER_NAME}-custom.
This SG (i.e.: $SGROUP__OVERLAY_ID) will be applied to the Kubernetes nodes during the bootstrap process (AWSMachine, AWSMachineTemplate)

```bash
VPC_ID=$(aws --profile=vmw --region=$AWS_REGION ec2 describe-vpcs \
    --query 'Vpcs[*].{VpcId:VpcId,Name:Tags[?Key==`Name`].Value|[0],CidrBlock:CidrBlock}' \
    --filters Name=tag:Name,Values=${CLUSTER_NAME}-vpc | jq -r '.[].VpcId')

SGROUP_CUSTOM=$(aws --profile=vmw --region=$AWS_REGION ec2 create-security-group \
--group-name ${CLUSTER_NAME}-custom \
--description "Custom node traffic" \
--vpc-id $VPC_ID | jq -r ."GroupId")

aws --profile=vmw --region=$AWS_REGION ec2 authorize-security-group-ingress \
    --group-id $SGROUP_CUSTOM \
    --protocol udp \
    --port 4789 \
    --source-group $SGROUP_CUSTOM

export AWS_CUSTOM_SG=$SGROUP_CUSTOM
```

2. If the CSI driver is used, configure the storage backend (e.g. EFS for the AWS EFS CSI driver). The respective Security Group is explained here below or in the CSI driver folder.
3. If the CSI driver is used, create a Security Group to allow the k8s nodes to access the storage mount target.

In case EFS is used in the AWS environment, the nodes will mount the storage as NFS, the security group should allow Inbound:
_Type = NFS / Protocol = TCP / Port Range = 2049 / Source = [the SG itself] / Description = EFS MT access_

In the EFS CSI driver deployment example, the Security Group name is chosen to be ${CLUSTER_NAME}-efs-mt.
This SG (i.e.: $SGROUP_CSI_ID) will be applied to the Kubernetes nodes during the bootstrap process (AWSMachine, AWSMachineTemplate)

Refer to the CSI Driver content for this part.

## Use "kind" to run a local k8s CAPI controlplane cluster

```bash
kind create cluster --name=clusterapi
kubectl cluster-info --context kind-clusterapi
# kubectl apply -f https://github.com/jetstack/cert-manager/releases/download/$CERT_MANAGER_VER/cert-manager.yaml
# kubectl wait --for=condition=Available --timeout=300s apiservice v1beta1.webhook.cert-manager.io
# kubectl wait --for=condition=Available --timeout=300s apiservice v1.admissionregistration.k8s.io
kubectl create -f https://github.com/kubernetes-sigs/cluster-api/releases/download/$CAPI_VER/cluster-api-components.yaml
```

## Install Cluster API bootstrap provider Kubeadm (CAPBPK)

```bash
kubectl create -f https://github.com/kubernetes-sigs/cluster-api-bootstrap-provider-kubeadm/releases/download/$CAPBPK_VER/bootstrap-components.yaml
```

## Install Cluster API AWS provider (CAPA)

```bash
export LC_ALL="en_US.UTF-8"
export AWS_B64ENCODED_CREDENTIALS=$(clusterawsadm alpha bootstrap encode-aws-credentials)

curl -L https://github.com/kubernetes-sigs/cluster-api-provider-aws/releases/download/$CAPA_VER/infrastructure-components.yaml | envsubst | kubectl create -f -
```

## Use "clusterawsadm" to bootstrap AWS Identity and Access Management (IAM)

```bash
clusterawsadm alpha bootstrap create-stack
```

### Export env variables for the previously created IAM username "bootstrapper.cluster-api-provider-aws.sigs.k8s.io" by clusterawsadm

```bash
export AWS_CREDENTIALS=$(aws --profile=vmw iam create-access-key --user-name bootstrapper.cluster-api-provider-aws.sigs.k8s.io)
export AWS_ACCESS_KEY_ID=$(echo $AWS_CREDENTIALS | jq .AccessKey.AccessKeyId -r)
export AWS_SECRET_ACCESS_KEY=$(echo $AWS_CREDENTIALS | jq .AccessKey.SecretAccessKey -r)
```

## Create the managed k8s cluster on AWS

The k8s cluster will be bootstraped on AWS.

### Describe and deploy the cluster's provider specific objects

```bash
rm ${CLUSTER_GENERATED_FILE} 2>/dev/null
kustomize build "${PROVIDER_DIR}/cluster" | envsubst > "${CLUSTER_GENERATED_FILE}"

kubectl apply -f ${WORK_DIR}/_generated_deployment/create-cluster.yaml

# The next command should show the cluster state as "provisioned"
# You may want to look to the Security Group prerequisites before moving forward.
kubectl get clusters
```

### Deploy the control plane nodes

```bash
rm ${CONTROLPLANE_GENERATED_FILE} 2>/dev/null
kustomize build "${PROVIDER_DIR}/controlplane" | envsubst > "${CONTROLPLANE_GENERATED_FILE}"

kubectl apply -f ${WORK_DIR}/_generated_deployment/create-cluster-machine.yaml

# The next command should show the machines' state as "running"
kubectl get machines -A
```

### Deploy the worker nodes

```bash
rm ${MACHINEDEPLOYMENT_GENERATED_FILE} 2>/dev/null
kustomize build "${PROVIDER_DIR}/machinedeployment" | envsubst > "${MACHINEDEPLOYMENT_GENERATED_FILE}"

kubectl apply -f ${WORK_DIR}/_generated_deployment/create-cluster-machine-deployment.yaml

# The next command should show the machines' state as "running"
kubectl get machines -A
```

Export the kubeconfig to be used to access the k8s cluster managed on AWS:

```bash
kubectl --namespace=default get secret/${CLUSTER_NAME}-kubeconfig -o json \
  | jq -r .data.value \
  | base64 --decode \
  > ${WORK_DIR}/${CLUSTER_NAME}.kubeconfig

KUBECONFIG=${WORK_DIR}/${CLUSTER_NAME}.kubeconfig

# The following command shows the deployed k8s nodes, control plane and worker. All these are now in "NotReady" status.
kubectl --kubeconfig=$KUBECONFIG get nodes
```

## Install Antrea CNI

Install your chosen version, at the current time 0.4.1 is the latest version:

```bash
kubectl --kubeconfig=${KUBECONFIG} \
  apply -f https://github.com/vmware-tanzu/antrea/releases/download/$ANTREA_VER/antrea.yml

# The following command shows the deployed k8s nodes, control plane and worker. All these are now in "Ready" status one the CNI has been installed.
kubectl --kubeconfig=$KUBECONFIG get nodes
```

