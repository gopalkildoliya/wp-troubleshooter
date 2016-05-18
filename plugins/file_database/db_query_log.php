<?php
/**
 * Meta Info
 * FILE_NAME: db_query_log.php
 * LABEL: Log Database Queries for given Url
 * LINK_MAIN: /file_database/db_query_log
 *
 */

respond('POST','/file_database/db_query_log', 'file_database_db_query_log');

function file_database_db_query_log(TsRequest $request, TsResponse $response)
{
    $response->data->title = "Log Database Queries";
    if (isset($request->url)) {
        define( 'SAVEQUERIES', true );
        // TODO-Gopal : Add functionality for full url check.
        $_SERVER['REQUEST_URI'] = $request->url;
        define('INCLUDE_WORDPRESS', true);
        /** Loads the WordPress Environment and Template */
        //require  TS_ABSPATH. 'wp-blog-header.php';

        /*global $wpdb, $wp;
        $response->discard();
        $response->data->table = true;
        $queries = array_walk($wpdb->queries, function(&$q){
            $q[1] = (float) $q[1] * 1000;
            $q[1] = floatval(number_format($q[1], 5, '.', ''));
        });
        $response->data->tableData = $wpdb->queries;
        $response->data->tableColumns = array(['title'=>'Query'],
            ['title'=>'Exec. Time(ms)'], ['title'=>'Caller']);

        $response->data->simpleData = "";
        $response->code(200);*/
    } else {
        $response->data->simpleData = "Enter a url on which you want to log database queries like: /2016/05/03/hello-world/";
        $response->data->form = true;
        $response->data->formData = array(
            array('name'  => 'url', 'type' => 'text',
                  'value' => '/2016/05/03/hello-world/', 'label' => 'Url'),
            array('name'  => 'link', 'type' => 'hidden',
                  'value' => '/file_database/db_query_log'),
            array('name' => 'submit', 'type' => 'submit', 'value' => 'Log')
        );
        $response->sendDataJson();
    }
    //$response->sendDataJson();
}

function afterWordPress()
{
    $response = New TsResponse();
    global $wpdb, $wp;
    $response->data->title = "Log Database Queries";
    $response->data->table = true;
    $queries = array_walk($wpdb->queries, function(&$q){
        $q[1] = (float) $q[1] * 1000;
        $q[1] = floatval(number_format($q[1], 5, '.', ''));
    });
    $response->data->tableData = $wpdb->queries;
    $response->data->tableColumns = array(['title'=>'Query'],
        ['title'=>'Exec. Time(ms)'], ['title'=>'Caller']);

    $response->data->simpleData = "Query Logs";
    $response->code(200);
    $response->sendDataJson();
    ob_flush();
}