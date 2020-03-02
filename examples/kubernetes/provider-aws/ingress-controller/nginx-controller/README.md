# Kubernetes NGINX Ingress controller (AWS NLB)

## Deploy

```comment
https://kubernetes.github.io/ingress-nginx/deploy/#aws
https://github.com/kubernetes/ingress-nginx/releases
```

```bash
kubectl --kubeconfig=${KUBECONFIG} apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/master/deploy/static/mandatory.yaml
kubectl --kubeconfig=${KUBECONFIG} apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/master/deploy/static/provider/aws/service-nlb.yaml
```

You may want to apply the configmap.yaml for the "proxy-add-original-uri-header" add-on. This will send to the backends the HTTP header "X-Original-Uri" with the original URI as requested by the browser.

```bash
kubectl --kubeconfig=${KUBECONFIG} apply -f configmap.yaml
```

## Clean

```bash
kubectl --kubeconfig=${KUBECONFIG} delete -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/master/deploy/static/mandatory.yaml
```
