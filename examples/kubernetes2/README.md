
# Bootstrap a Kubernetes Cluster

Kubernetes cluster on AWS with Antrea CNI, Octant and Prometheus.
Use ClusterAPI (v1alpha3) with AWS provider to bootstrap the cluster.

## Kuberenetes cluster (Create)

- CNI: Antrea (no-encap)
- Ingress Controller: Contour
- Octant and Prometheus

Source the environment variables and use CloudFormation with clusterawsadm to create needed AWS IAM ressources.
The SSH keys will be created to be able later to access the worker cluster nodes.

```bash
source .envrc
```

Bootstrap the one node Kind control cluster.

```bash
make kind_create
```

Install the Cluster API components and transform the local Kubernetes kind cluster into a management cluster.
Automatically add the cluster-api core provider, the kubeadm bootstrap provider, and the kubeadm control-plane provider to the list of providers to install.

```bash
make clusterctl_init_dry
make clusterctl_init
```

Export the kubeconfig for the kind cluster.

```bash
make kubeconfig_export
export KUBECONFIG=${PWD}/.kubeconfig
```

Create a "base" cluster YAML template to serve for creating a workload cluster.
"clusterctl.yaml" contains the variables used by clusterctl.

```bash
make clusterctl_config
```

### Create the workload cluster

```bash
cd ${DIR}/clusters/mycluster
rm -rf deployment
mkdir deployment
# Source cluster specific environment variables.
source .envrc
```

Use kustomization to create the cluster deployment document from the "base" template.

```bash
cat kustomization-template.yaml | envsubst > deployment/kustomization.yaml
kustomize build deployment/ > deployment/mycluster.yaml
```

### Deploy the worker cluster

```bash
kubectl apply -f deployment/mycluster.yaml
```

Wait for the worker cluster to get "Provisioned".

```bash
kubectl get cluster -A
NAMESPACE     NAME          PHASE
mycluster01   mycluster01   Provisioning
```

Export the kubeconfig for the worker cluster.

```bash
cd ${DIR}
make kubeconfig_export
```

### SSH connectivity to worker cluster nodes

Get the Bastion IP address to access (SSH) the worker cluster nodes.

```bash
kubectl get awsclusters -A
export BASTION_HOST=$(kubectl get awsclusters -A -o json | jq -r '.items[].status.bastion.publicIp')
```

Switch Kubernetes context to the worker cluster.

```bash
kubectl config get-contexts
kubectl config use-context mycluster01-admin@mycluster01
```

Get worker nodes internal IP addresses.

```bash
kubectl get nodes -o custom-columns=NAME:.metadata.name,IP:"{.status.addresses[?(@.type=='InternalIP')].address}"
```

Connect to any of the worker nodes.

```bash
ssh -i ${CLUSTER_SSH_KEY} ubuntu@<NODE_IP> -o "ProxyCommand ssh -W %h:%p -i ${CLUSTER_SSH_KEY} ubuntu@${BASTION_HOST}"
```

### Deploy Antrea CNI

```bash
cd ${DIR}/clusters/mycluster/deployment
curl -LO https://github.com/vmware-tanzu/antrea/releases/download/$ANTREA_VER/antrea.yml
```

Modify the following:

For antrea-agent.conf:
    trafficEncapMode: hybrid
    enablePrometheusMetrics: true
    serviceCIDR: #with your service CIDR ${SERVICE_CIDR}
For antrea-controller.conf:
    enablePrometheusMetrics: true

```bash
kubectl apply -f antrea.yml
```

Change AWS environment to accept "trafficEncapMode: hybrid" (noEncap):

- create new SG to allow all IP traffic between K8s nodes
- disable source check on AWS EC2 instances (K8s nodes)

```bash
cd ${DIR}/clusters/mycluster/
# create function
/bin/bash aws_custom.sh
```

### Deploy Octant

```bash
cd ${DIR}/clusters/mycluster/deployment
# antrea-octant-yml change Service to type: ClusterIP
curl -LO https://github.com/vmware-tanzu/antrea/releases/download/$ANTREA_VER/antrea-octant.yml
# admin.conf from any of the worker control plane nodes /etc/kubernetes/admin.conf (kubeadm)
kubectl create secret generic octant-kubeconfig --from-file=admin.conf -n kube-system
kubectl apply -f antrea-octant.yml
```

### Deploy Prometheus

```bash
cd ${DIR}/clusters/mycluster/deployment
curl -LO https://raw.githubusercontent.com/vmware-tanzu/antrea/master/build/yamls/antrea-prometheus.yml
kubectl apply -f antrea-prometheus.yml
```

With Antrea 0.7.0 the following metrics are exported (Antrea Controller and Agent) to Prometheus.

**Antrea Controller Metrics.**
| Metric                                                        | Description                                          |
|---------------------------------------------------------------|------------------------------------------------------|
| antrea_controller_address_group_processed                     | The total number of address-group processed          |
| antrea_controller_address_group_sync_duration_milliseconds    | The duration of syncing address-group                |
| antrea_controller_applied_to_group_processed                  | The total number of applied-to-group processed       |
| antrea_controller_applied_to_group_sync_duration_milliseconds | The duration of syncing applied-to-group             |
| antrea_controller_length_address_group_queue                  | The length of AddressGroupQueue                      |
| antrea_controller_length_applied_to_group_queue               | The length of AppliedToGroupQueue                    |
| antrea_controller_length_network_policy_queue                 | The length of InternalNetworkPolicyQueue             |
| antrea_controller_network_policy_processed                    | The total number of internal-networkpolicy processed |
| antrea_controller_network_policy_sync_duration_milliseconds   | The duration of syncing internal-networkpolicy       |
| antrea_controller_runtime_info (labels)                       | Antrea controller runtime info                       |

**Antrea Agent Metrics.**
| Metric                       | Description                                                        |
|------------------------------|--------------------------------------------------------------------|
| antrea_agent_local_pod_count | Number of pods on local node which are managed by the Antrea Agent |
| antrea_agent_ovs_flow_table  | OVS flow table flow count                                          |
| antrea_agent_runtime_info    | Antrea agent runtime info (labels)                                 |

### Deploy Contour

```bash
cd ${DIR}/clusters/mycluster/deployment
git clone https://github.com/projectcontour/contour.git
```

Modify the following:

Choose AWS NLB in contour/examples/contour/0x-service-envoy.yaml
    # service.beta.kubernetes.io/aws-load-balancer-backend-protocol: tcp
    service.beta.kubernetes.io/aws-load-balancer-type: nlb

```bash
kubectl apply -f contour/examples/contour
```

Get the AWS NLB entry point.

```bash
kubectl get service envoy --namespace=projectcontour -o jsonpath='{.status.loadBalancer.ingress[0].hostname}'
```

### Deploy HTTPProxy contour ressource to expose the Octant and Prometheus dashboards

```bash
cd ${DIR}/clusters/mycluster/contour

kubectl apply -f contour-octant-ingress.yml
kubectl apply -f contour-prometheus-ingress.yml

kubectl get proxy -A
```

### Test app

Deploy GCP microservices [demo app](https://github.com/GoogleCloudPlatform/microservices-demo "Hipster Shop")
The app manifest is available [here](https://raw.githubusercontent.com/GoogleCloudPlatform/microservices-demo/master/release/kubernetes-manifests.yaml "App manifest").

In the Kubernetes manifest, I do not use the service type LoadBalancer to expose the frontend, contour HTTPProxy resource type is being used instead.

I separate the Redis and Paymentservice deployments from the other services, each one on its own namespace (hipstershop-db, hipstershop-payment and hipstershop-app). I create and apply the needed Kubernetes NetworkPolicy between the app services.

```bash
cd ${DIR}/
kustomize build demoapp/ | kubectl apply -f -
```

## Kuberenetes cluster (Delete)

```bash
cd ${DIR}/clusters/mycluster/
# delete function
/bin/bash aws_custom.sh

cd ${DIR}/
make clean_all
```
