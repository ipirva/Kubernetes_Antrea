<?php
    # Ionut Pirva
    # ionut.pirva@gmail.com
    # March 2020

    # Main page

    ini_set('display_errors', 0);
    error_reporting(0);

    $original_uri = getallheaders()["X-Original-Uri"];
    if ($original_uri){
        $uri_explode = explode("/", $original_uri, 3);
        if (count($uri_explode) >= 2){
            $deployment = $uri_explode[1];
        }else {
            exit("Cannot get a deployment name from the URI.");
        }
    }
    $json_file = file_get_contents($_SERVER['DOCUMENT_ROOT']."/deployments.json");

    if ($json_file and strlen($deployment) > 0){
        $json = json_decode($json_file, true);
        
        $namespace = $json["namespace"];
        $dns_timeout = $json["dns"]["timeout"];

        foreach ($json["deployments"] as $key => $value) {
            if ($json["deployments"][$key]["name"] == $deployment){
                $idx = $key;
            }
        }
        if (isset($idx)){
            $svc_frontend_port = $json["deployments"][$idx]["svc_frontend_port"];
            $svc_frontend_name = $json["deployments"][$idx]["svc_frontend_name"];
            $frontend_timeout = $json["deployments"][$idx]["frontend_timeout"];
            $svc_es_port = $json["deployments"][$idx]["svc_es_port"];
            $svc_es_name = $json["deployments"][$idx]["svc_es_name"];
            $svc_es_https = $json["deployments"][$idx]["svc_es_https"];
            $es_timeout = $json["es"]["timeout"];
            $es_timeout = $json["deployments"][$idx]["es_timeout"];
            $es_username = $json["deployments"][$idx]["es_username"];
            $es_password = $json["deployments"][$idx]["es_password"];
            $es_index = $json["deployments"][$idx]["es_index"];
            $image = $json["deployments"][$idx]["image"];
            if (!file_exists($image )) {
                $image = "assets/img/default-smile.png";
            }
            $image_hash = hash_file('md5', $image);
        }else{
            exit("Did not find a deployment with the name: ".$deployment);
        }
    }else{
        exit("Check that the deployment json file is set.");
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Home - Fruits Library</title>
    <meta name="description" content="Get some fruity details">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css?h=95a22dfb3dc46c1e6e894fd48b487af9">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,700">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/custom.css?h=5dd043c9aa748dfa2d3cfcbdc3866ade">
    <link rel="stylesheet" href="assets/fontawesome/css/fontawesome.min.css?h=5b0cf44e1069ceeb8430e2448319af08">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0/js/all.min.js" integrity="sha256-0vuk8LXoyrmCjp1f0O300qo1M75ZQyhH9X3J6d+scmk=" crossorigin="anonymous"></script>
    
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha256-pasqAKBDmFT4eHoN2ndd6lN370kFiGUFyTiUHWhU7k8=" crossorigin="anonymous"></script>
    <script type="text/javascript">
	        $(document).ready(function() {
                setInterval(function(){
                    $('#status').load('actions/tests/footer-tests.php?deployment=<?php echo $deployment;?>');
                },5000);
	    	  $('#search').click(function(e) {
		        e.preventDefault();
	    	    var keyword = $("#keyword").val();
                var optselect = $("#optselect option:selected").text();
	    	   if ($.trim(keyword).length > 0) {
                    var dataPost = {
                        "keyword": keyword,
                        "deployment": optselect,
                        "namespace": '<?php echo $namespace; ?>'
                    };
                    console.log(dataPost);
                    $.ajax({
                        type: "POST",
                        dataType: "JSON",
                        url: "actions/search/",
                        data: {dataQ: dataPost},
                        cache: false,
                        beforeSend: function() {
                            $("#search").html('Searching ...');
                        },
                        error: function() {
                            console.log("TIMEOUT");
                            $("#search").html('Search');
                            $("#notificationtext").removeClass("invisible");
                            $("#notificationtext").removeClass("text-info");
                            $("#notificationtext").addClass("text-danger");
                            $("#notificationtext").html('Timeout.<i class="icon far far fa-sad-cry fa-lg"></i>');
                            $("#records-table").empty();
                        },
                        success: function(response) {
                            console.log(response.error);
                            console.log(response);
                            if (response.info){
                                console.log(response.info);
                                $("#search").html('Search');
                                $("#notificationtext").removeClass("invisible");
                                $("#notificationtext").removeClass("text-danger");
                                $("#notificationtext").addClass("text-info");
                                $("#notificationtext").html(response.info+'<i class="icon far fa-meh-blank fa-lg"></i>');
                                $("#records-table").empty();
                            }
                            if (response.error){
                                console.log(response.error);
                                $("#search").html('Search');
                                $("#notificationtext").removeClass("invisible");
                                $("#notificationtext").removeClass("text-info");
                                $("#notificationtext").addClass("text-danger");
                                $("#notificationtext").html(response.error+'<i class="icon far fa-sad-cry fa-lg"></i>');
                                $("#records-table").empty();
                            }else{
                                //$("#notificationtext").addClass("invisible");
                                //var results = JSON.parse(response);
                                var results = response;
                                var len = results.length;
                                console.log(len);
                                if (len >= 1) {
                                    $("#notificationtext").removeClass("text-danger");
                                    $("#notificationtext").addClass("text-info");
                                    $("#notificationtext").html("Number of results: "+len+'<i class=\"icon far fa-smile-wink fa-lg"></i>');
                                    $("#records-table").empty();
                                    $("#search").fadeIn(1000, function(){
                                        $("#results").removeClass("invisible");
                                        $("#search").html('Search');
                                    });
                                    //var results = JSON.parse(response);
                                    var trHTML = '';
                                    $.each(results, function(i, item) {        
                                        console.log(item);
                                        trHTML += '<tr><td>' + item.searchfor + '</td><td>' + item.keyword + '</td><td>' + item.source + '</td><td>'+ item.mime + '</td><td><a href='+ item.link +' class="text-light" target="_blank">'+ item.link + '</a></td></tr>';
                                    });
                                    $('#records-table').append(trHTML);
                                }else {
                                    $("#search").html('Search');
                                    $("#results").addClass("invisible");
                                    $("#records-table").empty();
                                }
                            }
                        },
                        timeout: 3000
                    });
	    	   }
	    	   return false;
	    	  });
	        });
      </script>
</head>

<body id="page-top">
    <nav class="navbar navbar-light navbar-expand-lg fixed-top bg-secondary text-uppercase" id="mainNav">
        <div class="container"><a class="navbar-brand js-scroll-trigger" href="#page-top">Fruits Library</a><button data-toggle="collapse" data-target="#navbarResponsive" class="navbar-toggler navbar-toggler-right text-uppercase bg-primary text-white rounded" aria-controls="navbarResponsive"
                aria-expanded="false" aria-label="Toggle navigation"><i class="fa fa-bars"></i></button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="nav navbar-nav ml-auto">
                    <li class="nav-item mx-0 mx-lg-1" role="presentation"><a class="nav-link py-3 px-0 px-lg-3 rounded js-scroll-trigger" href="#library">Library</a></li>
                    <li class="nav-item mx-0 mx-lg-1" role="presentation"><a class="nav-link py-3 px-0 px-lg-3 rounded js-scroll-trigger" href="#status">Status</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <header class="masthead bg-primary text-white text-center">
        <div class="container">
            <img class="img-fluid d-block mx-auto mb-3" src=<?php echo $image."?h=".$image_hash;?> loading="auto">
            <h1>Do not go for the lowest hanging fruit!</h1>
        </div>
    </header>
    <section id="library" class="library">
        <div class="container">
            <h2 class="text-uppercase text-center text-secondary">Library</h2>
            <hr class="star-dark mb-5">
            <div class="row">
            <div class="col-md-6 col-lg-4"></div>
            <div class="col-md-6 col-lg-4">
                <p class="text-left text-md-left font-weight-normal" id="notificationtext"></p>
            </div>
            <div class="col-md-6 col-lg-4"></div>
            <div class="col-md-6 col-lg-4"></div>
            <div class="col-md-6 col-lg-4"></div>
            <div class="col-md-6 col-lg-4"></div>
            </div>
            <div class="row">
                <div class="col-md-3 col-lg-4"></div>
                <div class="col-md-6 col-lg-4">
                    <div class="form-group floating-label-form-group controls mb-10 pb-2">
                        <label>Search</label>
                        <input class="bg-light border rounded border-info px-2" type="text" id="keyword" required="" placeholder="Search" minlength="3" autocomplete="off">
                        <small class="form-text text-danger help-block"></small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 pt-4">
                    <select id="optselect" class="form-control mb-2 pb-2 col-sm-4">
                        <option value="" disabled selected>Databases</option>
                            <?php
                                foreach ($json["deployments"] as $key => $value) {
                                    if ($deployment == $json["deployments"][$key]["name"]){
                                        echo "<option selected value=\"".$json["deployments"][$key]["name"]."\">".$json["deployments"][$key]["name"]."</option>";
                                    }else{
                                        echo "<option value=\"".$json["deployments"][$key]["name"]."\">".$json["deployments"][$key]["name"]."</option>";
                                    }
                                }
                            ?>
                    </select>
                </div>
                <div class="col-md-3 col-lg-4"></div>
                <div class="col-md-6 col-lg-4">
                    <button class="btn btn-success border rounded d-xl-flex" type="submit" id="search">Search</button>
                </div>
                <div class="col-md-3 col-lg-4"></div>
            </div>
        </div>
    </section>
    <section id="results" class="bg-primary text-white mb-0 invisible">
        <div class="container">
            <h2 class="text-uppercase text-center text-white">RESULTS</h2>
            <hr class="star-light mb-5">
            <div class="row ml-4">
                <div class="col">
                    <div class="table-responsive">
                        <table class="table text-light" id="records-table">
                            <!-- Content dynamically generated -->
                            <thead>
                                <tr>
                                    <th>Search for:</th>
                                    <th>Keywords:</th>
                                    <th>Source:</th>
                                    <th>MIME:</th>
                                    <th>Link:</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="space"></section>
    <footer class="border rounded footer text-left">
        <div class="container">
            <section id="status">
                <!-- Content dynamically generated -->
            </section>
        </div>
    </footer>
    <div class="copyright py-4 text-center text-white">
        <div class="container"><small>Fruits Library, Created in 2020 </small><i class="icon far fa-lemon"></i><i class="icon fas fa-apple-alt"></i><i class="icon fas fa-carrot"></i></div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/js/bootstrap.bundle.min.js" integrity="sha256-OUFW7hFO0/r5aEGTQOz9F/aXQOt+TwqI1Z4fbVvww04=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js" integrity="sha256-H3cjtrm/ztDeuhCN9I4yh4iN2Ybx/y1RM7rMmAesA0k=" crossorigin="anonymous"></script>
    <script src="assets/js/freelancer.js?h=c39cf000ef6adba0f85349e55288c80d"></script>
</body>

</html>