# Fruits application

Fruits is a simple application which uses NodeJS to return some POD OS details as its "hostname".
It can be used to test an Ingress Controller deployment for example.

Kubernetes resources used:

1. Namespace = fruits;
2. Deployment: "apple-app" and "banana-app";
3. Service: "apple-service" and "banana-service". The NodeJS server is exposed as TCP / port 3000;
4. Secret: TLS certificate generated for the Ingress ressource;
5. Ingress: the two Services are made accessible via two HTTP/HTTPS backends, "/apple", respectively "/banana"

No persistent storage is needed.

The Fruits app uses the fictive "mytest.com" domain. On the host used to test the app, a local host entry "[IP] mytest.com" is needed. [IP] is the DNS A entry for the LB ressource deployed in the Cloud of your choice to host the Kubernetes cluster.

```bash
kubectl --kubeconfig=${KUBECONFIG} describe ingress -n fruits fruits-ingress
```

## Fruits-v1

The app was built to run on top of a Kubernetes cluster on AWS which useses Ingress Controler NGINX.

### Prerequisites

1. Deploy the Kubernetes CNI plugin. Refer to the Kubernetes cluster deployment.
2. Deploy an Ingress Controller. Refer to the respective folder.

### Deploy the app

Pull the Git repo:

```bash
DIR=$(pwd)
GIT_REP=Kubernetes_Antrea

git pull https://github.com/ipirva/Kubernetes_Antrea.git
$SOURCE_DIR="Kubernetes_Antrea"
```

```bash
kubectl --kubeconfig=${KUBECONFIG} apply -f $SOURCE_DIR/examples/apps/fruits-v1/kubernetes
```

### Test the app

```bash
# Get the PODs in the namespace fruits
kubectl get pods --kubeconfig=${KUBECONFIG} -n fruits -o wide
# Get Deployment, Service and Ingress resources in the namespace fruits
kubectl get ingress,deploy,svc --kubeconfig=${KUBECONFIG} -n fruits -o wide
# Describe the Ingress resource
# "Adress" IP value must be associated to "mytest.com" to run the next tests
kubectl --kubeconfig=${KUBECONFIG} describe ingress -n fruits fruits-ingress

curl -k https://mytest.com/apple
# {"hostname":"apple-app-7759788985-j5r9d","uptime":5019}
# {"hostname":"apple-app-7759788985-h2htn","uptime":5017}

curl -k https://mytest.com/banana
# {"hostname":"banana-app-665fbf77df-xpm9m","uptime":5022}
````
