
# Troubleshooting

The output here below is from a k8s cluster running fruits-v2 / fruits-library example app.

<!--  
Antrea OVS Pipeline:
https://github.com/vmware-tanzu/antrea/blob/master/docs/ovs-pipeline.md
-->

```bash
kubectl --kubeconfig=$KUBECONFIG get pods -n kube-system -l app=antrea -o wide

NAME                                 READY   STATUS             RESTARTS   AGE   IP             NODE                                       NOMINATED NODE   READINESS GATES
antrea-agent-2ddnd                   2/2     Running            0          8h    10.0.0.57      ip-10-0-0-57.eu-west-2.compute.internal    <none>           <none>
antrea-agent-hx96p                   2/2     Running            0          8h    10.0.0.229     ip-10-0-0-229.eu-west-2.compute.internal   <none>           <none>
antrea-agent-qb7wx                   2/2     Running            0          8h    10.0.0.177     ip-10-0-0-177.eu-west-2.compute.internal   <none>           <none>
antrea-controller-7fb7c4644f-smtlq   1/1     Running            0          8h    10.0.0.229     ip-10-0-0-229.eu-west-2.compute.internal   <none>           <none>

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ls -la /var/log/openvswitch/

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- tail -f /var/log/openvswitch/ovs-vswitchd.log

# ovs-vsctl : Used for configuring the ovs-vswitchd configuration database (known as ovs-db)
# ovs-ofctl : A command line tool for monitoring and administering OpenFlow switches
# ovs-dpctl : Used to administer Open vSwitch datapaths
# ovs−appctl : Used for querying and controlling Open vSwitch daemons

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ovs-vsctl show
82680234-ef7e-4b24-b5a2-6f2b43447f2f
    Bridge br-int
        Port "elastic--9246cf"
            Interface "elastic--9246cf"
        Port "tun0"
            Interface "tun0"
                type: vxlan
                options: {key=flow, remote_ip=flow}
        Port "fruitsli-91057c"
            Interface "fruitsli-91057c"
        Port "es-banan-2579b6"
            Interface "es-banan-2579b6"
        Port "gw0"
            Interface "gw0"
                type: internal
        Port "fruitsli-ba90ee"  
            Interface "fruitsli-ba90ee"
    ovs_version: "2.11.1"

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ovs-ofctl show br-int
OFPT_FEATURES_REPLY (xid=0x2): dpid:0000f22a376dca41
n_tables:254, n_buffers:0
capabilities: FLOW_STATS TABLE_STATS PORT_STATS QUEUE_STATS ARP_MATCH_IP
actions: output enqueue set_vlan_vid set_vlan_pcp strip_vlan mod_dl_src mod_dl_dst mod_nw_src mod_nw_dst mod_nw_tos mod_tp_src mod_tp_dst
 1(tun0): addr:1a:4c:9d:7f:d6:e5
     config:     0
     state:      0
     speed: 0 Mbps now, 0 Mbps max
 2(gw0): addr:ca:94:3f:e1:9c:b1
     config:     0
     state:      0
     speed: 0 Mbps now, 0 Mbps max
 16(elastic--9246cf): addr:aa:10:fb:e7:2e:19
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 70(es-banan-2579b6): addr:7a:e9:47:25:89:1e
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 78(fruitsli-ba90ee): addr:8a:15:52:75:be:2b
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 79(fruitsli-91057c): addr:16:1e:2b:85:b8:31
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
OFPT_GET_CONFIG_REPLY (xid=0x4): frags=normal miss_send_len=0

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ovs-ofctl dump-ports br-int
OFPST_PORT reply (xid=0x2): 6 ports
  port  1: rx pkts=61198, bytes=16868297, drop=?, errs=?, frame=?, over=?, crc=?
           tx pkts=72844, bytes=11258004, drop=?, errs=?, coll=?
  port 16: rx pkts=2041755, bytes=256055054, drop=0, errs=0, frame=0, over=0, crc=0
           tx pkts=1805337, bytes=871967707, drop=0, errs=0, coll=0
  port 79: rx pkts=31555, bytes=5128523, drop=0, errs=0, frame=0, over=0, crc=0
           tx pkts=27254, bytes=5437719, drop=0, errs=0, coll=0
  port 70: rx pkts=240281, bytes=124970962, drop=0, errs=0, frame=0, over=0, crc=0
           tx pkts=349582, bytes=57328553, drop=0, errs=0, coll=0
  port 78: rx pkts=5149, bytes=938952, drop=0, errs=0, frame=0, over=0, crc=0
           tx pkts=4858, bytes=854479, drop=0, errs=0, coll=0
  port  2: rx pkts=3841728, bytes=677767369, drop=0, errs=0, frame=0, over=0, crc=0
           tx pkts=3281416, bytes=1104534810, drop=0, errs=0, coll=0

kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ovs-ofctl dump-flows br-int
NXST_FLOW reply (xid=0x4):
 cookie=0x7000000000000, duration=30663.737s, table=0, n_packets=204693, n_bytes=62093284, idle_age=1, priority=200,in_port=2 actions=load:0x1->NXM_NX_REG0[0..15],resubmit(,10)
 cookie=0x7000000000000, duration=30663.735s, table=0, n_packets=59300, n_bytes=16446937, idle_age=2, priority=200,in_port=1 actions=load:0->NXM_NX_REG0[0..15],resubmit(,30)
 cookie=0x7030000000000, duration=30663.715s, table=0, n_packets=126039, n_bytes=16437068, idle_age=1, priority=190,in_port=16 actions=load:0x2->NXM_NX_REG0[0..15],resubmit(,10)
 cookie=0x7030000000000, duration=30663.713s, table=0, n_packets=9774, n_bytes=5939653, idle_age=10, priority=190,in_port=70 actions=load:0x2->NXM_NX_REG0[0..15],resubmit(,10)
 cookie=0x7030000000000, duration=30663.712s, table=0, n_packets=11969, n_bytes=2029550, idle_age=2, priority=190,in_port=79 actions=load:0x2->NXM_NX_REG0[0..15],resubmit(,10)
 cookie=0x7030000000000, duration=30663.711s, table=0, n_packets=560, n_bytes=106126, idle_age=40, priority=190,in_port=78 actions=load:0x2->NXM_NX_REG0[0..15],resubmit(,10)
 cookie=0x7000000000000, duration=30663.742s, table=0, n_packets=16, n_bytes=9709, idle_age=30663, priority=0 actions=drop
 cookie=0x7000000000000, duration=30663.737s, table=10, n_packets=203761, n_bytes=62053916, idle_age=1, priority=200,ip,in_port=2 actions=resubmit(,30)
 cookie=0x7000000000000, duration=30663.737s, table=10, n_packets=924, n_bytes=38808, idle_age=3, priority=200,arp,in_port=2,arp_spa=192.168.1.1,arp_sha=ca:94:3f:e1:9c:b1 actions=resubmit(,20)
 cookie=0x7030000000000, duration=30663.715s, table=10, n_packets=0, n_bytes=0, idle_age=30663, priority=200,arp,in_port=16,arp_spa=192.168.1.15,arp_sha=8e:a9:08:fd:80:bb actions=resubmit(,20)
 cookie=0x7030000000000, duration=30663.713s, table=10, n_packets=12, n_bytes=504, idle_age=663, priority=200,arp,in_port=70,arp_spa=192.168.1.69,arp_sha=22:22:ad:5f:b9:ad actions=resubmit(,20)
 cookie=0x7030000000000, duration=30663.712s, table=10, n_packets=75, n_bytes=3150, idle_age=3, priority=200,arp,in_port=79,arp_spa=192.168.1.78,arp_sha=e6:eb:36:d2:ad:89 actions=resubmit(,20)
 cookie=0x7030000000000, duration=30663.711s, table=10, n_packets=63, n_bytes=2646, idle_age=40, priority=200,arp,in_port=78,arp_spa=192.168.1.77,arp_sha=36:25:a1:4e:c0:91 actions=resubmit(,20)
 cookie=0x7030000000000, duration=30663.715s, table=10, n_packets=126031, n_bytes=16436508, idle_age=1, priority=200,ip,in_port=16,dl_src=8e:a9:08:fd:80:bb,nw_src=192.168.1.15 actions=resubmit(,30)
 cookie=0x7030000000000, duration=30663.713s, table=10, n_packets=9753, n_bytes=5938519, idle_age=10, priority=200,ip,in_port=70,dl_src=22:22:ad:5f:b9:ad,nw_src=192.168.1.69 actions=resubmit(,30)
 cookie=0x7030000000000, duration=30663.712s, table=10, n_packets=11886, n_bytes=2025840, idle_age=2, priority=200,ip,in_port=79,dl_src=e6:eb:36:d2:ad:89,nw_src=192.168.1.78 actions=resubmit(,30)
 cookie=0x7030000000000, duration=30663.711s, table=10, n_packets=488, n_bytes=102850, idle_age=648, priority=200,ip,in_port=78,dl_src=36:25:a1:4e:c0:91,nw_src=192.168.1.77 actions=resubmit(,30)
 cookie=0x7000000000000, duration=30663.742s, table=10, n_packets=42, n_bytes=2940, idle_age=666, priority=0 actions=drop
 cookie=0x7020000000000, duration=30663.536s, table=20, n_packets=793, n_bytes=33306, idle_age=23, priority=200,arp,arp_tpa=192.168.0.1,arp_op=1 actions=move:NXM_OF_ETH_SRC[]->NXM_OF_ETH_DST[],mod_dl_src:aa:bb:cc:dd:ee:ff,load:0x2->NXM_OF_ARP_OP[],move:NXM_NX_ARP_SHA[]->NXM_NX_ARP_THA[],load:0xaabbccddeeff->NXM_NX_ARP_SHA[],move:NXM_OF_ARP_SPA[]->NXM_OF_ARP_TPA[],load:0xc0a80001->NXM_OF_ARP_SPA[],IN_PORT
 cookie=0x7020000000000, duration=30663.536s, table=20, n_packets=1, n_bytes=42, idle_age=30662, priority=200,arp,arp_tpa=192.168.2.1,arp_op=1 actions=move:NXM_OF_ETH_SRC[]->NXM_OF_ETH_DST[],mod_dl_src:aa:bb:cc:dd:ee:ff,load:0x2->NXM_OF_ARP_OP[],move:NXM_NX_ARP_SHA[]->NXM_NX_ARP_THA[],load:0xaabbccddeeff->NXM_NX_ARP_SHA[],move:NXM_OF_ARP_SPA[]->NXM_OF_ARP_TPA[],load:0xc0a80201->NXM_OF_ARP_SPA[],IN_PORT
 cookie=0x7000000000000, duration=30663.741s, table=20, n_packets=280, n_bytes=11760, idle_age=3, priority=190,arp actions=NORMAL
 cookie=0x7000000000000, duration=30663.742s, table=20, n_packets=0, n_bytes=0, idle_age=30663, priority=0 actions=drop
 cookie=0x7000000000000, duration=30663.739s, table=30, n_packets=411219, n_bytes=103004570, idle_age=1, priority=200,ip actions=ct(table=31,zone=65520)
 cookie=0x7000000000000, duration=30663.739s, table=31, n_packets=38540, n_bytes=7868607, idle_age=2, priority=210,ct_state=-new+trk,ct_mark=0x20,ip,reg0=0x1/0xffff actions=resubmit(,40)
 cookie=0x7000000000000, duration=30663.739s, table=31, n_packets=36, n_bytes=18244, idle_age=29805, priority=200,ct_state=+inv+trk,ip actions=drop
 cookie=0x7000000000000, duration=30663.737s, table=31, n_packets=66240, n_bytes=21866895, idle_age=2, priority=200,ct_state=-new+trk,ct_mark=0x20,ip actions=load:0xca943fe19cb1->NXM_OF_ETH_DST[],resubmit(,40)
 cookie=0x7000000000000, duration=30663.742s, table=31, n_packets=306403, n_bytes=73250824, idle_age=1, priority=0 actions=resubmit(,40)
 cookie=0x7040000000000, duration=30663.734s, table=40, n_packets=136576, n_bytes=17678131, idle_age=1, priority=200,ip,nw_dst=10.96.0.0/12 actions=load:0x2->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,105)
 cookie=0x7000000000000, duration=30663.742s, table=40, n_packets=274607, n_bytes=85308195, idle_age=1, priority=0 actions=resubmit(,50)
 cookie=0x7000000000000, duration=30663.738s, table=50, n_packets=218121, n_bytes=78733902, idle_age=1, priority=210,ct_state=-new+est,ip actions=resubmit(,70)
 cookie=0x7000000000000, duration=30663.742s, table=50, n_packets=56486, n_bytes=6574293, idle_age=2, priority=0 actions=resubmit(,60)
 cookie=0x7000000000000, duration=30663.742s, table=60, n_packets=56486, n_bytes=6574293, idle_age=2, priority=0 actions=resubmit(,70)
 cookie=0x7000000000000, duration=30663.737s, table=70, n_packets=0, n_bytes=0, idle_age=30663, priority=200,ip,dl_dst=aa:bb:cc:dd:ee:ff,nw_dst=192.168.1.1 actions=mod_dl_dst:ca:94:3f:e1:9c:b1,resubmit(,80)
 cookie=0x7030000000000, duration=30663.715s, table=70, n_packets=0, n_bytes=0, idle_age=30663, priority=200,ip,dl_dst=aa:bb:cc:dd:ee:ff,nw_dst=192.168.1.15 actions=mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:8e:a9:08:fd:80:bb,dec_ttl,resubmit(,80)
 cookie=0x7030000000000, duration=30663.713s, table=70, n_packets=579, n_bytes=43305, idle_age=2, priority=200,ip,dl_dst=aa:bb:cc:dd:ee:ff,nw_dst=192.168.1.69 actions=mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:22:22:ad:5f:b9:ad,dec_ttl,resubmit(,80)
 cookie=0x7030000000000, duration=30663.712s, table=70, n_packets=1873, n_bytes=388454, idle_age=2, priority=200,ip,dl_dst=aa:bb:cc:dd:ee:ff,nw_dst=192.168.1.78 actions=mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:e6:eb:36:d2:ad:89,dec_ttl,resubmit(,80)
 cookie=0x7030000000000, duration=30663.711s, table=70, n_packets=312, n_bytes=33924, idle_age=29, priority=200,ip,dl_dst=aa:bb:cc:dd:ee:ff,nw_dst=192.168.1.77 actions=mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:36:25:a1:4e:c0:91,dec_ttl,resubmit(,80)
 cookie=0x7020000000000, duration=30663.536s, table=70, n_packets=46273, n_bytes=5826101, idle_age=2, priority=200,ip,nw_dst=192.168.0.0/24 actions=dec_ttl,mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:aa:bb:cc:dd:ee:ff,load:0x1->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],load:0xa0000e5->NXM_NX_TUN_IPV4_DST[],resubmit(,105)
 cookie=0x7020000000000, duration=30663.536s, table=70, n_packets=24539, n_bytes=5122857, idle_age=2, priority=200,ip,nw_dst=192.168.2.0/24 actions=dec_ttl,mod_dl_src:ca:94:3f:e1:9c:b1,mod_dl_dst:aa:bb:cc:dd:ee:ff,load:0x1->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],load:0xa0000b1->NXM_NX_TUN_IPV4_DST[],resubmit(,105)
 cookie=0x7000000000000, duration=30663.742s, table=70, n_packets=201031, n_bytes=73893554, idle_age=1, priority=0 actions=resubmit(,80)
 cookie=0x7000000000000, duration=30663.737s, table=80, n_packets=67224, n_bytes=21933807, idle_age=2, priority=200,dl_dst=ca:94:3f:e1:9c:b1 actions=load:0x2->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,90)
 cookie=0x7030000000000, duration=30663.715s, table=80, n_packets=101149, n_bytes=45770804, idle_age=1, priority=200,dl_dst=8e:a9:08:fd:80:bb actions=load:0x10->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,90)
 cookie=0x7030000000000, duration=30663.713s, table=80, n_packets=23384, n_bytes=4234830, idle_age=2, priority=200,dl_dst=22:22:ad:5f:b9:ad actions=load:0x46->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,90)
 cookie=0x7030000000000, duration=30663.712s, table=80, n_packets=11108, n_bytes=2254644, idle_age=2, priority=200,dl_dst=e6:eb:36:d2:ad:89 actions=load:0x4f->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,90)
 cookie=0x7030000000000, duration=30663.711s, table=80, n_packets=929, n_bytes=165078, idle_age=14, priority=200,dl_dst=36:25:a1:4e:c0:91 actions=load:0x4e->NXM_NX_REG1[],load:0x1->NXM_NX_REG0[16],resubmit(,90)
 cookie=0x7000000000000, duration=30663.742s, table=80, n_packets=1, n_bytes=74, idle_age=30663, priority=0 actions=resubmit(,90)
 cookie=0x7000000000000, duration=30663.738s, table=90, n_packets=197498, n_bytes=73893259, idle_age=1, priority=210,ct_state=-new+est,ip actions=resubmit(,105)
 cookie=0x7000000000000, duration=30663.737s, table=90, n_packets=0, n_bytes=0, idle_age=30663, priority=210,ip,nw_src=192.168.1.1 actions=resubmit(,105)
 cookie=0x7050000000000, duration=642.128s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=200,ip,nw_src=192.168.2.36 actions=conjunction(1,1/3)
 cookie=0x7050000000000, duration=642.128s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=200,ip,nw_src=192.168.1.77 actions=conjunction(1,1/3)
 cookie=0x7050000000000, duration=642.238s, table=90, n_packets=0, n_bytes=0, idle_age=642, hard_age=558, priority=200,ip,nw_src=192.168.2.10 actions=conjunction(4,1/3),conjunction(8,1/3)
 cookie=0x7050000000000, duration=642.320s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=200,ip,reg1=0x4e actions=conjunction(8,2/3),conjunction(6,2/2)
 cookie=0x7050000000000, duration=642.321s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=200,ip,reg1=0x46 actions=conjunction(1,2/3),conjunction(6,2/2)
 cookie=0x7050000000000, duration=642.320s, table=90, n_packets=0, n_bytes=0, idle_age=642, hard_age=558, priority=200,ip,reg1=0x4f actions=conjunction(4,2/3),conjunction(6,2/2)
 cookie=0x7050000000000, duration=642.128s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=200,tcp,tp_dst=9200 actions=conjunction(1,3/3)
 cookie=0x7050000000000, duration=642.238s, table=90, n_packets=0, n_bytes=0, idle_age=642, hard_age=558, priority=200,tcp,tp_dst=8080 actions=conjunction(4,3/3),conjunction(8,3/3)
 cookie=0x7050000000000, duration=642.322s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=190,conj_id=6,ip actions=resubmit(,105)
 cookie=0x7050000000000, duration=642.188s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=190,conj_id=8,ip actions=resubmit(,105)
 cookie=0x7050000000000, duration=642.129s, table=90, n_packets=0, n_bytes=0, idle_age=642, priority=190,conj_id=1,ip actions=resubmit(,105)
 cookie=0x7050000000000, duration=558.196s, table=90, n_packets=4, n_bytes=296, idle_age=68, priority=190,conj_id=4,ip actions=resubmit(,105)
 cookie=0x7000000000000, duration=30663.742s, table=90, n_packets=6199, n_bytes=458726, idle_age=2, priority=0 actions=resubmit(,100)
 cookie=0x7000000000000, duration=642.321s, table=100, n_packets=148, n_bytes=10952, idle_age=2, priority=200,ip,reg1=0x46 actions=drop
 cookie=0x7000000000000, duration=642.320s, table=100, n_packets=38, n_bytes=2812, idle_age=14, priority=200,ip,reg1=0x4e actions=drop
 cookie=0x7000000000000, duration=642.320s, table=100, n_packets=2, n_bytes=148, idle_age=558, priority=200,ip,reg1=0x4f actions=drop
 cookie=0x7000000000000, duration=30663.742s, table=100, n_packets=291, n_bytes=21534, idle_age=8, priority=0 actions=resubmit(,105)
 cookie=0x7000000000000, duration=30663.739s, table=105, n_packets=50263, n_bytes=6113791, idle_age=2, priority=200,ct_state=+new+trk,ip,reg0=0x1/0xffff actions=ct(commit,table=110,zone=65520,exec(load:0x20->NXM_NX_CT_MARK[]))
 cookie=0x7000000000000, duration=30663.739s, table=105, n_packets=54818, n_bytes=6450971, idle_age=2, priority=190,ct_state=+new+trk,ip actions=ct(commit,table=110,zone=65520)
 cookie=0x7000000000000, duration=30663.742s, table=105, n_packets=300194, n_bytes=89984372, idle_age=1, priority=0 actions=resubmit(,110)
 cookie=0x7000000000000, duration=30663.740s, table=110, n_packets=405274, n_bytes=102549060, idle_age=1, priority=200,ip,reg0=0x10000/0x10000 actions=output:NXM_NX_REG1[]
 cookie=0x7000000000000, duration=30663.742s, table=110, n_packets=1, n_bytes=74, idle_age=30663, priority=0 actions=drop


kubectl --kubeconfig=$KUBECONFIG exec -n kube-system antrea-agent-2ddnd -c antrea-ovs -- ovs-ofctl dump-ports-desc br-int
OFPST_PORT_DESC reply (xid=0x2):
 1(tun0): addr:1a:4c:9d:7f:d6:e5
     config:     0
     state:      0
     speed: 0 Mbps now, 0 Mbps max
 2(gw0): addr:ca:94:3f:e1:9c:b1
     config:     0
     state:      0
     speed: 0 Mbps now, 0 Mbps max
 16(elastic--9246cf): addr:aa:10:fb:e7:2e:19
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 70(es-banan-2579b6): addr:7a:e9:47:25:89:1e
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 78(fruitsli-ba90ee): addr:8a:15:52:75:be:2b
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
 79(fruitsli-91057c): addr:16:1e:2b:85:b8:31
     config:     0
     state:      0
     current:    10GB-FD COPPER
     speed: 10000 Mbps now, 0 Mbps max
```