<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 11/5/16
 * Time: 4:26 PM
 */

/**
 * Meta Info
 * FILE_NAME: db_manager.php
 * LABEL: Database Manager
 * LINK_MAIN: /file_database/db_manager
 *
 */

respond('POST','/file_database/db_manager', 'file_database_db_manager');

function file_database_db_manager(TsRequest $request, TsResponse $response)
{

    $response->data->title = "Database Manager";
    $configPath = TS_ABSPATH.'wp-config.php';
    if (is_file($configPath)) {
        $c = file_get_contents($configPath);
        if ($c) {
            preg_match('/define.*DB_NAME.*\'(.*)\'/', $c, $m);
            $db_name = $m[1];

            preg_match('/define.*DB_USER.*\'(.*)\'/', $c, $m);
            $db_user = $m[1];

            preg_match('/define.*DB_PASSWORD.*\'(.*)\'/', $c, $m);
            $db_pass = $m[1];

            preg_match('/define.*DB_HOST.*\'(.*)\'/', $c, $m);
            $db_host = $m[1];

        } else {
            $response->flash("Unable to get Database details.", 'danger');
        }
    } else{

    }
    $out = "<form method='post' action='".$_SERVER["PHP_SELF"]."?ts_plugin=file_database/adminer'>
                <input type=\"hidden\" value=\"server\" name=\"auth[driver]\">
                <input type=\"hidden\" value=\"$db_host\" name=\"auth[server]\">
                <input type=\"hidden\" value=\"$db_user\" name=\"auth[username]\">
                <input type=\"hidden\" value=\"$db_pass\" name=\"auth[password]\">
                <input type=\"hidden\" value=\"$db_name\" name=\"auth[db]\">
                <input type=\"submit\" value=\"Launch database manager\" class=\"btn btn-primary\">
                    </form>";
    $response->data->simpleData = $out;
    $response->sendDataJson();
}