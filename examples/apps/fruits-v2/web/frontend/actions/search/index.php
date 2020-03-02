<?php
    # Ionut Pirva
    # ionut.pirva@gmail.com
    # March 2020

    # AJAX endpoint - searches in DB / ES

    ini_set('display_errors', 0);
    error_reporting(0);
    
    function output($type, $message){
        if ($type == "info") {
            echo json_encode(array("info" => $message));
        } elseif ($type == "error") {
            echo json_encode(array("error" => $message));
        } elseif ($type == "output") {
            echo $message;
        } else {
            echo json_encode(array());
        }
        exit();
    }
    #$datatest = array(
    #    "deployment" => "banana",
    #    "keyword" => "Banana Drawing High-Res",
    #    "namespace" => "fruits-library"
    #);
    #if (isset($datatest)) {
    if (isset($_POST["dataQ"])) {
        $datarcv = $_POST["dataQ"];
        #$datarcv = $datatest;
        #$data = json_decode($datarcv, true);
        $data = $datarcv;
        $keys = array("keyword", "deployment", "namespace");
        if (count(array_intersect_key(array_flip($keys), $data)) === count($keys)) {
            foreach ($data as $k => $v) {
                $keyword = $data["keyword"];
                $deployment = $data["deployment"];
                $namespace  = $data["namespace"];
            }

            $json_file = file_get_contents($_SERVER['DOCUMENT_ROOT']."/deployments.json");

            if ($json_file and strlen($deployment) > 0){
                $json = json_decode($json_file, true);
                
                foreach ($json["deployments"] as $key => $value) {
                    if ($json["deployments"][$key]["name"] == $deployment){
                        $idx = $key;
                    }
                }
                if (isset($idx)){
                    $es_endpoint = $json["deployments"][$idx]["svc_es_name"];
                    $es_username = $json["deployments"][$idx]["es_username"];
                    $es_password = $json["deployments"][$idx]["es_password"];
                    $es_https = $json["deployments"][$idx]["svc_es_https"];
                    $es_index = $json["deployments"][$idx]["es_index"];
                    $es_port = $json["deployments"][$idx]["svc_es_port"];
                }else{
                    # Did not find a deployment with the name $deployment
                    output("error", "Did not find the deployment: ".$deployment);
                }
            }else{
                # Check that the deployment json file is set
                output("error", "Deployment description file is missing.");
            } 

            $payload = '
                {   
                    "from" : 0, "size" : 100,
                    "query": {
                        "match_phrase" : {
                            "title" : "'. $keyword .'"
                        }
                    }
                }
            ';
            # write json data to file tmp
            $tmpfname = tempnam("/tmp", "query_json_".time()."_");
            $handle = fopen($tmpfname, "w");
            fwrite($handle, $payload);
            fclose($handle);

            $link = $es_https.$es_username.":".$es_password."@".$es_endpoint.".".$namespace.".svc.cluster.local".":".$es_port."/".$es_index."/_search";
   
            # shell_exec quicker than cURL :) - it fits my needs
            # shell_exec returns only the stdout, if no stdout, we may have an error
            $querydata = shell_exec('curl -k -H '.'"Content-Type: application/json"'.' -X POST '.$link.' -d @'.$tmpfname);
            # delete tmp file
            unlink($tmpfname);

            $querydata = json_decode($querydata,true);

            # return data
            $return = array();

            # stdout present
            if ($querydata){
                if (array_key_exists("error", $querydata)){
                    # returned data has errors
                    output("error", "The DB search returned an error: ".json_encode($querydata["error"]));
                }else{
                    if (array_key_exists("hits", $querydata)){
                        if (array_key_exists("hits", $querydata["hits"])){
                            #print("<pre>".print_r($querydata["hits"]["hits"],true)."</pre>");
                            $hits = $querydata["hits"]["hits"];
                            $hits_size = sizeof($hits);
                            for ($i = 0; $i < $hits_size; $i++) {
                                if (array_key_exists("_source", $hits[$i])){
                                    $source = $hits[$i]["_source"];
                                    foreach ($source as $k => $v) {
                                        $return[$i]["searchfor"] = $data["keyword"];
                                        $return[$i]["keyword"] = $source["title"];
                                        $return[$i]["source"] = $source["displayLink"];
                                        $return[$i]["mime"] = $source["mime"];
                                        $return[$i]["link"] = $source["link"];
                                    }
                                }else{
                                    output("error", "The hits have no details associated.");
                                }
                            }
                            if (sizeof($return) == 0){
                                output("info", "No results found.");
                            }
                            $return = json_encode($return,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
                            output("output", $return);
                        }else{
                            output("error", "No hits elements found in the DB.");
                        }
                    }else{
                        output("error", "No hits found in the DB.");
                    }
                }
            }else{
                output("error", "DB server - unreachable.");
            }
        } else {
            # required keys missing
            output("error", "Missing required search keys.");
        }
    } else {
        # missing input data
        output("error", "Missing input data.");
    }
?>