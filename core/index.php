<?php

if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {


    define('PASSWORD', 'root');
    define('WP_DEBUG', true);
    define('WP_DEBUG_DISPLAY', true);

    require "include/klein.inc.php";
    require "include/auth.inc.php";
    require "include/WP_Error.php";
    require "include/db.inc.php";
    require "include/JsonOutput.php";

    respond(function ($request, $response, $app) {
        $app->register('db', function() {
            $db_details = array();
            $configPath = ABSPATH.'wp-config.php';
            if (is_file($configPath)) {
                $c = file_get_contents($configPath);
                if ($c) {
                    preg_match('/define.*DB_NAME.*\'(.*)\'/', $c, $m);
                    $db_details['name'] = $m[1];

                    preg_match('/define.*DB_USER.*\'(.*)\'/', $c, $m);
                    $db_details['user'] = $m[1];

                    preg_match('/define.*DB_PASSWORD.*\'(.*)\'/', $c, $m);
                    $db_details['pass'] = $m[1];

                    preg_match('/define.*DB_HOST.*\'(.*)\'/', $c, $m);
                    $db_details['host'] = $m[1];
                    preg_match('/\$table_prefix.*\'(.*)\'/', $c, $m);
                    $db_details['prefix'] = $m[1];

                } else {

                }
            } else{

            }
            $db = new DB($db_details['user'], $db_details['pass'], $db_details['name'], $db_details['host']);
            $db->set_prefix($db_details['prefix']);
            return $db;
        });
    });
    global $options;
    $options = array();
    require "functions.php";
    if(Auth::isLoggedIn())
        dispatch($_POST['link']);
    else
        dispatch('/login');
} else {



    echo <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wordpress Troubleshooter</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container" style="margin-top:30px">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel panel-default">
          <div class="panel-heading"><h3 class="panel-title"><strong id="title">Welcome to WordPress TroubleShooter</strong>
            <span class="pull-right btn btn-primary btn-xs" id="home">Home</span>
            </h3></div>
          <div class="panel-body">
                <form id="form">
                    <input type="hidden" value="/home" name="link">
                    <input type="submit" value="Let's Start" class="btn btn-primary">
                </form>
          </div>
        </div>
        </div>
        </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    <script src="/core/main.js">

    </script>
  </body>
</html>
EOT;
}

?>