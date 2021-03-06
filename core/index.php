<?php

    define('PASSWORD', 'root');
    define('WP_DEBUG', true);
    define('WP_DEBUG_DISPLAY', false);
    if(!is_dir(TS_PLUGIN_DIR))
        mkdir(TS_PLUGIN_DIR, 0777, true);

    session_start();
    $idletime = 3000; //after 300 seconds the user gets logged out
    if (time()-$_SESSION['timestamp']>$idletime){
        session_destroy();
        session_unset();
    }else{
        $_SESSION['timestamp']=time();
    }

    require "include/auth.inc.php";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_POST['link']))
    {

    require "include/klein.inc.php";
    require "include/TsError.php";
    require "include/db.inc.php";
    require "include/JsonOutput.php";

    require "functions.php";

    if(!file_exists(TS_PLUGIN_DIR.'plugins.json'))
        downloadFile(TS_PLUGIN_DIR, 'plugins.json');
    $options_file = file_get_contents(TS_PLUGIN_DIR.'plugins.json');
    global $options;
    $options = json_decode($options_file, true);




    respond(function (TsRequest $request, TsResponse $response, TsApp $app) {
        $response->onError(function ($response, $err_msg) {
            $response->flash($err_msg, 'danger');
            $response->back();
        });
        $app->register('db', function() {
            $db_details = array();
            $configPath = TS_ABSPATH.'wp-config.php';
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

    foreach($options as $level_name=>$level)
    {
        foreach($level['plugins'] as $file_name=>$file)
        {
            if(in_array($_POST['link'], $file['links_all']))
            {
                if(!file_exists(TS_PLUGIN_DIR.$level_name.'/'.$file_name.'.php'))
                {
                    downloadFile(TS_PLUGIN_DIR.$level_name.'/', $file_name.'.php', $level_name);
                }
                require TS_PLUGIN_DIR.$level_name.'/'.$file_name.'.php';
            }
        }
    }

    if (Auth::isLoggedIn()) {
        dispatch($_POST['link']);

        // wordpress include
        if(function_exists('afterWordPress') && defined('INCLUDE_WORDPRESS')) {
            ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE);
            declare( ticks = 1);
            /*register_tick_function(function(){
                $fp = fopen('/work/backnew.txt', 'a');
                fwrite($fp, debug_backtrace()[2]['file'] . "\n");
                fclose($fp);
            });*/
            register_tick_function(array($p3Profiler, 'ts_tick_handler'));
            require  TS_ABSPATH. 'index.php';
            //ob_end_clean();
            ob_clean();
            afterWordPress();
            //http_response_code(200);
        }

    } else {
        $_POST['backlink'] = $_POST['link'];
        dispatch('/login');
    }
} elseif(isset($_GET['ts_plugin'])) {
        if (Auth::isLoggedIn()) {
            if(!file_exists(TS_PLUGIN_DIR.$_GET['ts_plugin'].'.php'))
            {
                downloadFile(TS_PLUGIN_DIR.$_GET['ts_plugin'].'.php', explode('/', $_GET['ts_plugin'])[0]);
            }
            require TS_PLUGIN_DIR.$_GET['ts_plugin'].'.php';
        }
    } else {

if (file_exists(TS_ABSPATH."wp-admin/images/spinner-2x.gif"))
    $loading = "wp-admin/images/spinner-2x.gif";
else
    $loading = "wp-admin/images/loading.gif";

echo '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wordpress Troubleshooter</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.11/css/jquery.dataTables.css">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container" style="margin-top:30px">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading"><span class="panel-title"><strong id="title">Welcome to WordPress TroubleShooter</strong></span>
                    <span class="pull-right" id="search-box">
                    <input type="text" id="quick-search">

                    </span>

                    <br>

                </div>
                <div>
                    <ol class="breadcrumb" style="font-size:12px;">
                    </ol>
                    <ul class="list-group text-info" style="" id="quick-links">
                    </ul>
                </div>
                <img src="';
                echo $loading;
echo '" style="margin-left: 50%; display: none;" id="loading">
                <div class="panel-body">
                    <div id="simpledata">
                    </div>
                    <div id="formBody">
                        <form><input type="hidden" value="/home" name="link">
                        <input type="submit" value="Let\'s Start" class="btn btn-primary">
                    </form></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"></h4>
              </div>
              <div class="modal-body">

              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
    </div>

    <!-- jQuery (necessary for Bootstrap\'s JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.11/js/jquery.dataTables.js"></script>
    <script src="/core/main.js">

    </script>
  </body>
</html>';
}

?>