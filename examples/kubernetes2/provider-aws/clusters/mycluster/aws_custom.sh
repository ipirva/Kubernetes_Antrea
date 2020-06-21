#! /bin/bash

# allow noEncap networking on K8s nodes
# no source check for EC2 networking interfaces
# EC2 instances are tagged with:
# sigs.k8s.io/cluster-api-provider-aws/role=node and sigs.k8s.io/cluster-api-provider-aws/role=control-plane
# custom SG to allow all IP traffic between control and worked nodes

# create custom env
create (){
    # no source check for EC2 networking interfaces
    for k in \
    $(aws ec2 --profile=vmw --region=${AWS_REGION} describe-instances --filters "Name=tag:sigs.k8s.io/cluster-api-provider-aws/role,Values=node, control-plane" | \
    jq -r '.Reservations[].Instances[] | [.InstanceId] | @tsv'); \
    do \
    aws ec2 --profile=vmw --region=${AWS_REGION} modify-instance-attribute --instance-id $k --no-source-dest-check; \
    done
    # find the VPC used for K8s nodes
    VPC_ID=$(aws --profile=vmw --region=${AWS_REGION} ec2 describe-vpcs \
        --query 'Vpcs[*].{VpcId:VpcId,Name:Tags[?Key==`Name`].Value|[0],CidrBlock:CidrBlock}' \
        --filters Name=tag:Name,Values=${CLUSTER_NAME}-vpc | jq -r '.[].VpcId')
    # create the custom SG
    SGROUP_CUSTOM=$(aws --profile=vmw --region=${AWS_REGION} ec2 create-security-group \
    --group-name ${AWS_CUSTOM_SG} \
    --description "Custom node traffic" \
    --vpc-id $VPC_ID | jq -r ."GroupId")

    aws --profile=vmw --region=${AWS_REGION} ec2 authorize-security-group-ingress \
        --group-id $SGROUP_CUSTOM \
        --protocol all \
        --source-group $SGROUP_CUSTOM

    aws --profile=vmw --region=${AWS_REGION} ec2 create-tags \
    --resources  $SGROUP_CUSTOM --tags \
    Key=Name,Value=${AWS_CUSTOM_SG}
    # apply custom SG to all K8s nodes
    # apply SG is an idem-potent action
    for k in \
    $(aws ec2 --profile=vmw --region=${AWS_REGION} describe-instances --filters "Name=tag:sigs.k8s.io/cluster-api-provider-aws/role,Values=node, control-plane" | \
    jq -r '.Reservations[].Instances[] | [.InstanceId] | @tsv'); \
    do \
    aws ec2 --profile=vmw --region=${AWS_REGION} modify-instance-attribute --instance-id $k \
    --groups $(aws ec2 --profile=vmw --region=${AWS_REGION} describe-instances --instance-ids $k \
    --query Reservations[*].Instances[*].SecurityGroups[*].GroupId \
    --output text) ${SGROUP_CUSTOM}; \
    done
}

# delete custom env
delete () {
    # get the VPC ID
    VPC_ID=$(aws --profile=vmw --region=${AWS_REGION} ec2 describe-vpcs \
        --query 'Vpcs[*].{VpcId:VpcId,Name:Tags[?Key==`Name`].Value|[0],CidrBlock:CidrBlock}' \
        --filters Name=tag:Name,Values=${CLUSTER_NAME}-vpc | jq -r '.[].VpcId')
    # get the custom SG ID
    SGROUP_CUSTOM=$(aws --profile=vmw --region=${AWS_REGION} ec2 describe-security-groups \
    --filters "Name=vpc-id,Values=$VPC_ID,Name=group-name,Values=${AWS_CUSTOM_SG}" | jq -r '.SecurityGroups[].GroupId')
    # dettach SG from the EC2 instances
    for k in \
    $(aws ec2 --profile=vmw --region=${AWS_REGION} describe-instances --filters "Name=tag:sigs.k8s.io/cluster-api-provider-aws/role,Values=node, control-plane" | \
    jq -r '.Reservations[].Instances[] | [.InstanceId] | @tsv'); \
    do \
    aws ec2 --profile=vmw --region=${AWS_REGION} modify-instance-attribute --instance-id $k \
    --groups $(aws ec2 --profile=vmw --region=${AWS_REGION} describe-instances --instance-ids $k \
    --query Reservations[*].Instances[*].SecurityGroups[*].GroupId \
    --output text | sed s/${SGROUP_CUSTOM}//g); \
    done
    # delete the custom SG
    aws ec2 --profile=vmw --region=${AWS_REGION} delete-security-group --group-id ${SGROUP_CUSTOM}
}
create
# delete

