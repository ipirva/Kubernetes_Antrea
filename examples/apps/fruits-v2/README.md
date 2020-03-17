# Fruits Library application

Fruits Library is a 2-tier application showing a web frontend which allows to search in an elasticsearch backend.
It can be used to test for example:

- an Ingress Controller deployment;
- Kubernetes NetworkPolicy.

The app deploys 2 tenants, "banana" and "apple", for which it deploys 2 frontends and 2 elasticsearch clusters.

Kubernetes resources used:

1. Namespace = "fruits-library";
2. Deployment: "fruitslib-afrnt" and "fruitslib-bfrnt";
3. StatefulSet: "es-apple-library-es-default" and "es-banana-library-es-default" (ES k8s operator);
4. Service: frontend, "fruitslib-afrnt" and "fruitslib-afrnt". The web frontends are exposed as TCP / port 8080; "es-apple-library-es-http" and "es-banana-library-es-http" for the ES clusters;
5. Secret: TLS certificate generated for the Ingress ressource;
6. Ingress: the two frontend Services are made accessible via two HTTP/HTTPS backends, "/apple/", respectively "/banana/"

Persistent storage is needed and configured: AWS CSI driver EFS. Refer to the cluster installation for CSI installation details.

The Fruits Library app uses the fictive "myfruitslibrary.com" domain. On the host used to test the app, a local host entry "[IP] myfruitslibrary.com" is needed. [IP] is the DNS A entry of the LB ressource.

```bash
kubectl --kubeconfig=${KUBECONFIG} get ingress -n fruits-library
```

## Fruits-v2

The app was built to run on top of a Kubernetes cluster on AWS with Ingress Controler NGINX.

### Prerequisites

1. Deploy the Kubernetes CNI plugin. Refer to the Kubernetes cluster deployment;
2. Deploy the NGINX Ingress Controller. Refer to the respective folder;
3. Export env variable FS_ID with the name of the AWS EFS filesystem id (e.g. fs-cf74eb1f);
4. Install Elastic Cloud on Kubernetes (ECK) custom resource definitions and the operator with its RBAC rules: https://www.elastic.co/guide/en/cloud-on-k8s/current/k8s-quickstart.html

```bash
kubectl --kubeconfig=${KUBECONFIG} apply -f https://download.elastic.co/downloads/eck/1.0.1/all-in-one.yaml
```

### Deploy the app

Pull the Git repo:

```bash
DIR=$(pwd)
GIT_REP=Kubernetes_Antrea

git pull https://github.com/ipirva/Kubernetes_Antrea.git
$SOURCE_DIR=$DIR/Kubernetes_Antrea
```

```bash
cd $SOURCE_DIR/examples/apps/fruits-v2
# Generate TLS certificate for the Ingress resource
/bin/bash generate-certificate.sh
# Generate Kubernetes deployment manifests
/bin/bash generate-kube.sh

kubectl --kubeconfig=${KUBECONFIG} apply -f kubernetes/00-namespace.yaml
kubectl --kubeconfig=${KUBECONFIG} apply -f kubernetes/01-storageclass.yaml

# Deploy the frontends
kubectl --kubeconfig=${KUBECONFIG} apply -f kubernetes/_generated_deployment_fe/frontend.yaml

# Deploy the elasticsearch clusters
kubectl --kubeconfig=${KUBECONFIG} apply -f kubernetes/_generated_deployment_es/elasticsearch.yaml
# Once the ES clusters deployed, get the pass for the default user "elastic"
ES_APPLE_PASS=$(kubectl --kubeconfig=${KUBECONFIG} get secret es-apple-library-es-elastic-user -o=jsonpath='{.data.elastic}' -n fruits-library | base64 --decode)
ES_BANANA_PASS=$(kubectl --kubeconfig=${KUBECONFIG} get secret es-banana-library-es-elastic-user -o=jsonpath='{.data.elastic}' -n fruits-library | base64 --decode)
# Once the ES clusters deployed, get their ClusterIPs
ES_APPLE_CIP=$(kubectl --kubeconfig=${KUBECONFIG} get svc es-apple-library-es-http -n fruits-library -o=jsonpath='{.spec.clusterIP}')
ES_BANANA_CIP=$(kubectl --kubeconfig=${KUBECONFIG} get svc es-banana-library-es-http -n fruits-library -o=jsonpath='{.spec.clusterIP}')
```

#### Insert data into Elasticsearch

The data from the two below JSON files is taken from Gettyimages.fr using a Google CSE service.
"elasticsearch-apple.json" and "elasticsearch-banana.json" are provided in the "data" folder.

```bash
# Create ES index "apple"
curl -k -u elastic:$ES_APPLE_PASS -X PUT https://$ES_APPLE_CIP:9200/apple
# Bulk insert data into ES
file="elasticsearch-apple.json"
data_size=$(cat $file | jq '.items | length' | head -1)
for i in $(seq 0 $((data_size-1))); do cat $file | jq ".items[$i]" | jq -c '. | {"index": {"_index": "apple", "_type": "library", "_id": '$i'}}, .'; done | curl -k -u elastic:$ES_APPLE_PASS -H "Content-Type: application/json" -X POST https://$ES_APPLE_CIP:9200/apple/_bulk --data-binary @-

# Create ES index "banana"
curl -k -u elastic:$ES_BANANA_PASS -X PUT https://$ES_BANANA_CIP:9200/banana
# Bulk insert data into ES
file="elasticsearch-banana.json"
data_size=$(cat $file | jq '.items | length' | head -1)
for i in $(seq 0 $((data_size-1))); do cat $file | jq ".items[$i]" | jq -c '. | {"index": {"_index": "banana", "_type": "json", "_id": '$i'}}, .'; done | curl -k -u elastic:$ES_BANANA_PASS -H "Content-Type: application/json" -X POST https://$ES_BANANA_CIP:9200/banana/_bulk --data-binary @-
```

#### Make the web page data accessbile from the Frontends

The Frontends deployments' replicas share one folder on the AWS EFS NFS mount, i.e. "fruitslib-frnt"
The Git content of the folder "web/frontend" must be copied on one of the Kubernetes workers under the NFS mount, folder "fruitslib-frnt".

The content of the file "web/frontend/deployments.json" must be personalized for your deployment.

### Test the app

#### Web browser

From a web browser, test the app: https://myfruitslibrary.com/apple/ and https://myfruitslibrary.com/banana/

#### Kuberentes

```bash
# Get the PODs in the namespace fruits
kubectl get pods --kubeconfig=${KUBECONFIG} -n fruits-library -o wide
# Get Deployment, Service and Ingress resources in the namespace fruits
kubectl get ingress,deploy,svc --kubeconfig=${KUBECONFIG} -n fruits-library -o wide
# Describe the Ingress resource
# "Adress" IP value must be associated to "myfruitslibrary.com" to run the next tests
kubectl --kubeconfig=${KUBECONFIG} describe ingress -n fruits-library fruitslib-afrnt
kubectl --kubeconfig=${KUBECONFIG} describe ingress -n fruits-library fruitslib-bfrnt

# ES clusters health
# Apple ES cluster
curl -k -u elastic:$ES_APPLE_PASS -X GET https://$ES_APPLE_CIP:9200/_cluster/health
# Banana ES cluster
curl -k -u elastic:$ES_BANANA_PASS -X GET https://$ES_BANANA_CIP:9200/_cluster/health
````
