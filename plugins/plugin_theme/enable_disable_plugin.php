<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 10/5/16
 * Time: 10:22 AM
 */

/**
 * Meta Info
 * FILE_NAME: enable_disable_plugin.php
 * LABEL: Enable or Disable a Plugin
 * LINK_MAIN: /plugin_theme/enable_disable_plugin
 *
 */

respond('POST','/plugin_theme/enable_disable_plugin', 'plugin_theme_enable_disable_plugin');
respond('POST','/plugin_theme/enable_plugin', 'plugin_theme_enable_plugin');
respond('POST','/plugin_theme/disable_plugin', 'plugin_theme_disable_plugin');

function plugin_theme_enable_disable_plugin($request, $response)
{
    $response->data->title = "Enable or Disable a plugin";
    $response->data->simpleData = "Here we will enable or disable a plugin";
    $response->data->form = true;
    $response->data->formData = array(
        array('name'  => 'link', 'type'  => 'radio',
              'value' => '/plugin_theme/enable_plugin', 'label' => 'Enable a Plugin' ),
        array('name'  => 'link', 'type'  => 'radio',
              'value' => '/plugin_theme/disable_plugin', 'label' => 'Disable a Plugin' ),
        array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Continue' )
    );
    $response->sendDataJson();
}

function plugin_theme_enable_plugin($request, $response)
{
    $plugin_root = TS_ABSPATH."wp-content/plugins/";
    if(isset($request->plugin)){
        $oldname = $plugin_root.$request->plugin;
        //$newname = dirname($oldname).'/ts-disable-'.basename($oldname);
        $newname = dirname($oldname).'/'.str_replace('ts-disable-', '',basename($oldname));
        rename($oldname,$newname);
    }
    $plugins = getPlugins(false);
    $response->data->title = "Enable a plugin";
    if(empty($plugins))
        $response->data->simpleData = "No plugin to enable.";
    else {
        $response->data->simpleData = "Please select a plugin to enable";
        $response->data->form = true;
        $response->data->formData = array();
        foreach ($plugins as $name => $details) {
            $response->data->formData[] = array('name'  => 'plugin', 'type' => 'radio',
                                                'value' => $name, 'label' => $details['Title']);
        }
        $response->data->formData[] = array('name'  => 'link', 'type' => 'hidden',
                                            'value' => '/plugin_theme/enable_plugin');
        $response->data->formData[] = array('name' => 'submit', 'type' => 'submit', 'value' => 'Enable');
    }
    $response->sendDataJson();
}

function plugin_theme_disable_plugin($request, $response, $app)
{
    $plugin_root = TS_ABSPATH."wp-content/plugins/";
    if(isset($request->plugin)){
        $oldname = $plugin_root.$request->plugin;
        $newname = dirname($oldname).'/ts-disable-'.basename($oldname);
        rename($oldname,$newname);
    }
    $plugins = getPlugins();
    $response->data->title = "Disable a plugin";
    if(empty($plugins))
        $response->data->simpleData = "No plugin to disable.";
    else {
        $row = $app->db->get_row( $app->db->prepare( "SELECT option_value FROM ".$app->db->options." WHERE option_name = %s LIMIT 1", 'active_plugins' ), ARRAY_A );
        if ( is_object( $row ) )
            $row = $row->option_value;
        $response->data->simpleData = "Please select a plugin to disable";
        $db_text = $row['option_value'];
        $response->data->form = true;
        $response->data->formData = array();
        foreach ($plugins as $name => $details) {
            if(false !== strpos($db_text, $name))
                $response->data->formData[] = array('name'  => 'plugin', 'type' => 'radio',
                                                'value' => $name, 'label' => $details['Title']);
        }
        $response->data->formData[] = array('name'  => 'link', 'type' => 'hidden',
                                            'value' => '/plugin_theme/disable_plugin');
        $response->data->formData[] = array('name' => 'submit', 'type' => 'submit', 'value' => 'Disable');
    }
    $response->sendDataJson();
}

function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {

    $default_headers = array(
        'Name' => 'Plugin Name',
        'Version' => 'Version',
        'Author' => 'Author',
        'Description' => 'Description',
        'TextDomain' => 'Text Domain',
        'DomainPath' => 'Domain Path',
        'Network' => 'Network',
        // Site Wide Only is deprecated in favor of Network.
        '_sitewide' => 'Site Wide Only',
    );

    $plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

    // Site Wide Only is the old header for Network
    if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
        $plugin_data['Network'] = $plugin_data['_sitewide'];
    }
    $plugin_data['Network'] = ( 'true' == strtolower( $plugin_data['Network'] ) );
    unset( $plugin_data['_sitewide'] );

    $plugin_data['Title']      = $plugin_data['Name'];
    $plugin_data['AuthorName'] = $plugin_data['Author'];

    return $plugin_data;
}

function get_file_data( $file, $default_headers, $context = '' ) {
    // We don't need to write to the file, so just open for reading.
    $fp = fopen( $file, 'r' );

    // Pull only the first 8kiB of the file in.
    $file_data = fread( $fp, 8192 );

    // PHP will close file handle, but we are good citizens.
    fclose( $fp );

    // Make sure we catch CR-only line endings.
    $file_data = str_replace( "\r", "\n", $file_data );

    $all_headers = $default_headers;
    foreach ( $all_headers as $field => $regex ) {
        if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
            $all_headers[ $field ] =trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1] ));
        else
            $all_headers[ $field ] = '';
    }

    return $all_headers;
}

function getPlugins($enabled = true, $all = false)
{
    $plugin_folder='';
    $wp_plugins = array ();
    $plugin_root = TS_ABSPATH."wp-content/plugins/";
    if ( !empty($plugin_folder) )
        $plugin_root .= $plugin_folder;

    // Files in wp-content/plugins directory
    $plugins_dir =  opendir( $plugin_root);
    $plugin_files = array();
    if ( $plugins_dir ) {
        while (($file = readdir( $plugins_dir ) ) !== false ) {
            if ( substr($file, 0, 1) == '.' )
                continue;
            if ( is_dir( $plugin_root.'/'.$file ) ) {
                $plugins_subdir = @ opendir( $plugin_root.'/'.$file );
                if ( $plugins_subdir ) {
                    while (($subfile = readdir( $plugins_subdir ) ) !== false ) {
                        if ( substr($subfile, 0, 1) == '.' )
                            continue;
                        if ( substr($subfile, -4) == '.php' )
                            $plugin_files[] = "$file/$subfile";
                    }
                    closedir( $plugins_subdir );
                }
            } else {
                if ( substr($file, -4) == '.php' )
                    $plugin_files[] = $file;
            }
        }
        closedir( $plugins_dir );
    }
    if($enabled && !$all){
        $plugin_files = array_filter($plugin_files, function($file_name){
            return false === strpos($file_name, "ts-disable-");
        });
    }
    if(!$enabled && !$all){
        $plugin_files = array_filter($plugin_files, function($file_name){
            return false !== strpos($file_name, "ts-disable-");
        });
    }
    if ( empty($plugin_files) )
        return $wp_plugins;

    foreach ( $plugin_files as $plugin_file ) {
        if ( !is_readable( "$plugin_root/$plugin_file" ) )
            continue;

        $plugin_data = get_plugin_data( "$plugin_root/$plugin_file", false, false ); //Do not apply markup/translate as it'll be cached.

        if ( empty ( $plugin_data['Name'] ) )
            continue;

        $wp_plugins[ $plugin_file ] = $plugin_data;
    }
    return $wp_plugins;
}
