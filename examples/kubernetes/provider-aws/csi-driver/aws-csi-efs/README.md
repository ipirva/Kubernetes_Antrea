# AWS CSI EFS Kubernetes driver

## Deploy

### Configure EFS AWS service

#### Create the EFS FileSystem

```bash
FS_ID=$(aws --profile=vmw  --region=$AWS_REGION efs create-file-system \
    --creation-token ${CLUSTER_NAME}-efs-storage \
    --tags Key=Scope,Value=kubernetes | jq -r '.FileSystemId')

export FS_ID=$FS_ID
```

#### Update the Security Group

Update the existing Security Group ${CLUSTER_NAME}-custom to allow Inbound traffic from the EFS Mount Target.

```bash
VPC_ID=$(aws --profile=vmw --region=$AWS_REGION ec2 describe-vpcs \
    --query 'Vpcs[*].{VpcId:VpcId,Name:Tags[?Key==`Name`].Value|[0],CidrBlock:CidrBlock}' \
    --filters Name=tag:Name,Values=${CLUSTER_NAME}-vpc | jq -r '.[].VpcId')

SGROUP_CUSTOM=$(aws --profile=vmw --region=$AWS_REGION ec2 describe-security-groups --query "SecurityGroups[*].{ID:GroupId}" --filter Name=group-name,Values=${CLUSTER_NAME}-custom | jq -r ".[].ID")

aws --profile=vmw --region=$AWS_REGION ec2 authorize-security-group-ingress \
    --group-id $SGROUP_CUSTOM \
    --protocol tcp \
    --port 2049 \
    --source-group $SGROUP_CUSTOM
```

#### Create the EFS Mount Target

```bash
SUBNET_ID=$(aws --profile=vmw --region=$AWS_REGION ec2 describe-subnets \
    --filters "Name=tag:Name,Values=${CLUSTER_NAME}-subnet-private" \
    --query 'Subnets[*].{"SubnetId":"SubnetId"}' | jq -r '.[].SubnetId')

EFS_MT_IP=$(aws --profile=vmw --region=$AWS_REGION efs create-mount-target \
    --file-system-id $FS_ID \
    --subnet-id $SUBNET_ID \
    --security-group $SGROUP_CUSTOM | jq -r '.IpAddress')

export EFS_MT_IP=$EFS_MT_IP
```

### Prepare the Kuernetes worker nodes

To connect to each k8s worker node:

```bash
# The name of each node containes its respective private IP address
kubectl --kubeconfig=$KUBECONFIG get nodes

ssh -i $ssh_key ubuntu@[AWS EC2 private IP of the worker node] -o "ProxyCommand ssh -W %h:%p -i [SSH KEY] ubuntu@[AWS bastion EC2 public IP]"
```

```bash
sudo su -
apt-get update && apt-get install -y nfs-kernel-server
mkdir /efs-mount-point
# Replace the $EFS_MT_IP variable with the value from the previous CSI EFS Mount Target creation
mount -t nfs -o nfsvers=4.1,rsize=1048576,wsize=1048576,hard,timeo=600,retrans=2,noresvport $EFS_MT_IP:/ /efs-mount-point

echo "$EFS_MT_IP:/    /efs-mount-point    nfs4    nfsvers=4.1,rsize=1048576,wsize=1048576,hard,timeo=600,retrans=2 0 0" >> /etc/fstab
```

### Install CSI EFS driver

```bash
kubectl --kubeconfig=${KUBECONFIG} apply -k "github.com/kubernetes-sigs/aws-efs-csi-driver/deploy/kubernetes/overlays/stable/?ref=master"
```

## Clean

### On Kubernetes worker nodes EC2

```bash
sudo su -
umount /efs-mount-point
awk '!/\/efs-mount-point([ ]+|\t+)/' /etc/fstab > temp && mv temp /etc/fstab
```

### On AWS

```bash
FS_ID=$(aws --profile=vmw --region=$AWS_REGION efs describe-file-systems \
    --creation-token ${CLUSTER_NAME}-efs-storage \
    --query 'FileSystems[*].{FileSystemId:FileSystemId}' | jq -r '.[].FileSystemId')

MNT_ID=$(aws --profile=vmw --region=$AWS_REGION efs describe-mount-targets \
    --file-system-id $FS_ID --query 'MountTargets[*].{MountTargetId:MountTargetId}' | jq -r '.[].MountTargetId')

aws --profile=vmw --region=$AWS_REGION efs delete-mount-target \
    --mount-target-id $MNT_ID

aws --profile=vmw --region=$AWS_REGION efs delete-file-system \
    --file-system-id $FS_ID

aws --profile=vmw --region=$AWS_REGION ec2 delete-security-group \
    --group-name ${CLUSTER_NAME}-efs-mt
```
