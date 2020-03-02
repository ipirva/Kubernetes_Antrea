# Octant

https://github.com/vmware-tanzu/octant

## Install Octant

```bash
# https://github.com/vmware-tanzu/octant

kubectl --kubeconfig=${KUBECONFIG} create secret generic octant-kubeconfig --from-file=${KUBECONFIG} -n kube-system
cd ${DIR}/${GIT_REP}/examples/kubernetes/octant
# use the default / latest antrea-octant installation
wget -c https://github.com/vmware-tanzu/antrea/releases/download/$ANTREA_VER/antrea-octant.yml
kubectl --kubeconfig=${KUBECONFIG} apply -f antrea-octant.yml

# or use the antrea-octant_ingress.yml (based on the antrea-octant.yml for v0.4.1) which exposes Octant as a Service with Ingress resource
kubectl --kubeconfig=${KUBECONFIG} apply -f antrea-octant_ingress.yml

# if antrea-octant_ingress.yml is used, on the machine used to access the Octant web interface make a static DNS A entry mydashboard.com to the IP of the Ingress resource
# get the ingress resource ADDRESS
kubectl --kubeconfig=${KUBECONFIG} get ingress antrea-octant-ingress -n kube-system

# connect to Octant, http://mydashboard.com
```
