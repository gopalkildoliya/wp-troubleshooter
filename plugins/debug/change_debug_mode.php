<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 7/5/16
 * Time: 2:31 PM
 */

/**
 * Meta Info
 * FILE_NAME: change_debug_mode.php
 * LABEL: Change WordPress Debug Mode
 * LINK_MAIN: /debug/change_debug_mode
 *
 */



respond('POST','/debug/change_debug_mode', 'debug_change_debug_mode');

/**
 * Change WordPress Debug mode WP_DEBUG
 * @param $request
 * @param $response
 */
function debug_change_debug_mode(TsRequest $request, TsResponse $response)
{
    if (isset($request->mode)) {
        $str = "false";
        if($request->mode==='enable'){
            $str = "true";
        }
        try {
            $content = file_get_contents(TS_ABSPATH . 'wp-config.php');
            $content = preg_replace("/(define.*WP_DEBUG.*)(true|false)/", '${1}' . $str, $content);
            file_put_contents(TS_ABSPATH . 'wp-config.php', $content);
            $response->flash("Debug mode $request->mode"."d !", "success");
        }
        catch (Exception $e) {
            $response->flash($e->getMessage(), "danger");
        }
        home($request, $response);
    } else {
        $response->data->title = "Change WordPress Debug Mode";
        $response->data->simpleData = "Select to enable or disable debug";
        $response->data->form = true;
        $response->data->formData = array(
            array('name' => 'link', 'type' => 'hidden', 'value' => $_POST['link']),
            array('name' => 'mode', 'label' => 'Enable', 'type' => 'radio', 'value' => 'enable'),
            array('name' => 'mode', 'label' => 'Disable', 'type' => 'radio', 'value' => 'disable'),
            array('name' => 'submit', 'type' => 'submit', 'value' => 'Change Debug Mode')
        );
        $response->sendDataJson();
    }
}
