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

function file_database_db_manager($request, $response)
{
    $response->data->title = "Database Manager";
    $response->data->form = true;
    $response->data->formData = array(
        array('name'  => 'link', 'type' => 'hidden',
              'value' => '/file_database/db_manager')
    );
    $response->sendDataJson();
}