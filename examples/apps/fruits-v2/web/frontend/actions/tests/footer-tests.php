<?php
    # Ionut Pirva
    # ionut.pirva@gmail.com
    # March 2020

    # Main page footer dynamic content
    
    ini_set('display_errors', 0);
    error_reporting(0);

    if (isset($_GET['deployment']) and strlen($_GET['deployment']) > 0){
        $deployment = $_GET['deployment'];
    }else{
        echo "No deployment specified.";
        exit();
    }

    $json_file = file_get_contents($_SERVER['DOCUMENT_ROOT']."/deployments.json");

    if ($json_file) {
        $json = json_decode($json_file, true);

        $namespace = $json["namespace"];
        $dns_timeout = $json["dns"]["timeout"];
    } else {
        exit("Check that the deployment json file is set.");
    }

    echo "<div class=\"text-info\">";
        echo "This host is: " . gethostname();
    echo "</div>";
    echo "<div>";
        echo "Ingress LB IP is: " . $_SERVER['REMOTE_ADDR'];
    echo "</div>";
    echo "<p></p>";
    echo "<div>";
        echo "php.net IP is: " . dns_get_record("php.net", DNS_A)[0]["ip"];
    echo "</div>";
    echo "<p></p>";
    echo "<div>";
        echo "Check connection to Google public NS tcp://8.8.8.8: ";
        $fp = fsockopen("tcp://8.8.8.8", 53, $errno, $errstr, $dns_timeout ?? 3);
        if (!$fp) {
            echo "<font class=\"text-warning\"><i class=\"icon far fa-sad-cry fa-lg\">ERROR: $errno - $errstr</font>";
        } else {
            echo "<font class=\"text-info\"><i class=\"icon far fa-smile-wink fa-lg\"></i></font>";
        }
    echo "</div>";
    echo "<div>";
        echo "Check connection to Google public NS udp://8.8.8.8: ";
        $fp = fsockopen("udp://8.8.8.8", 53, $errno, $errstr, $dns_timeout ?? 3);
        if (!$fp) {
            echo "<font class=\"text-warning\"><i class=\"icon far fa-sad-cry fa-lg\">ERROR: $errno - $errstr</font>";
        } else {
            echo "<font class=\"text-info\"><i class=\"icon far fa-smile-wink fa-lg\"></i></font>";
        }
    echo "</div>";
    echo "<p></p>";
    echo "<div>";
        foreach ($json["deployments"] as $key => $value) {
            $name = $json["deployments"][$key]["name"];
            $svc_frontend_port = $json["deployments"][$key]["svc_frontend_port"];
            $svc_frontend_proto = $json["deployments"][$key]["svc_frontend_proto"];
            $svc_frontend_name = $json["deployments"][$key]["svc_frontend_name"];
            $svc_es_port = $json["deployments"][$key]["svc_es_port"];
            $svc_es_proto = $json["deployments"][$key]["svc_es_proto"];
            $svc_es_name = $json["deployments"][$key]["svc_es_name"];
            echo "<div>";
            echo "Check connection to <b>\"$name\"</b> ES service $svc_es_proto://$svc_es_name.$namespace.svc.cluster.local:$svc_es_port (" . dns_get_record("$svc_es_name.$namespace.svc.cluster.local", DNS_A)[0]["ip"] . "): ";
            $fp = fsockopen("$svc_es_proto://$svc_es_name.$namespace.svc.cluster.local", $svc_es_port, $errno, $errstr, $es_timeout ?? 3);
            if (!$fp) {
                echo "<font class=\"text-warning\"><i class=\"icon far fa-sad-cry fa-lg\"></i>ERROR: $errno - $errstr</font>";
            } else {
                echo "<font class=\"text-info\"><i class=\"icon far fa-smile-wink fa-lg\"></i></font>";
            }
            echo "</div>";
            echo "<div>";
                if ($name != $deployment) {
                    echo "Check connection to <b>\"$name\"</b> FRONTEND service $svc_frontend_proto://$svc_frontend_name.$namespace.svc.cluster.local:$svc_frontend_port (" . dns_get_record("$svc_frontend_name.$namespace.svc.cluster.local", DNS_A)[0]["ip"] . "): ";
                    $fp = fsockopen("$svc_frontend_proto://$svc_frontend_name.$namespace.svc.cluster.local", $svc_frontend_port, $errno, $errstr, $frontend_timeout ?? 3);
                    if (!$fp) {
                        echo "<font class=\"text-warning\"><i class=\"icon far fa-sad-cry fa-lg\"></i>ERROR: $errno - $errstr</font>";
                    } else {
                        echo "<font class=\"text-info\"><i class=\"icon far fa-smile-wink fa-lg\"></i></font>";
                    }
                }
            echo "</div>";
            echo "<p></p>";
    }
    echo "</div>";
?>
