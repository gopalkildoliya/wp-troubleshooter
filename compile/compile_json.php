<?php

define('PLUGIN_PATH', dirname(__DIR__) . '/plugins/');

$levels = array();
    foreach (glob(PLUGIN_PATH.'*', GLOB_ONLYDIR) as $dir){
        $info = json_decode(file_get_contents($dir.'/info.json'), true);
        $levels[$info['name']] = $info['details'];
    }


foreach($levels as $level=>$details){
    $plugins = glob(PLUGIN_PATH.$level.'/*.php');
    foreach($plugins as $plugin)
    {
        $contents = file_get_contents($plugin);
        preg_match("/FILE_NAME\\s*:\\s*(\\w*)\\.php/", $contents, $matches_name);
        preg_match("/LABEL\\s*:\\s*([\\w ]*)/", $contents, $matches_label);
        preg_match("/LINK_MAIN\\s*:\\s*([\\w \\/]*)/", $contents, $matches_link_main);
        preg_match_all("/respond[\\w\\(\\'\\s]*POST[\\s\\'\\\"\\,]*([\\w \\/]*)/", $contents, $matches_links_all);
        //var_dump($matches_links_all[1]);
        if(!empty($matches_name)) {
            $levels[$level]['plugins'][$matches_name[1]] = array(
                'label'     => $matches_label[1],
                'link_main' => $matches_link_main[1],
                'links_all' => $matches_links_all[1]
            );
        }
    }
}

$str = json_encode($levels);
file_put_contents(PLUGIN_PATH.'plugins.json', $str);

