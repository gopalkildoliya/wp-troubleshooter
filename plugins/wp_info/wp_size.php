<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 18/5/16
 * Time: 4:15 PM
 */

/**
 * Meta Info
 * FILE_NAME: wp_size.php
 * LABEL: WordPress Installation Size
 * LINK_MAIN: /wp_info/wp_size
 *
 */

respond('POST','/wp_info/wp_size', 'wp_info_wp_size');

function wp_info_wp_size(TsRequest $request, TsResponse $response)
{
    $size = getDirectorySize(realpath(TS_ABSPATH));

    $response->data->title = 'WordPress Installation Size';
    $disk_space = disk_free_space(TS_ABSPATH);
    $gb = 1024*1024*1024;
    $mb = 1024*1024;
    if($disk_space > $gb)
        $disk_space = sprintf('%0.2f GB', $disk_space/$gb);
    else
        $disk_space = sprintf('%0.2f MB', $disk_space/$mb);
    $out = "WordPress installation size : ".sprintf('%0.2f MB <br>', ($size['size'] / (1024*1024)));
    $out .= "<br>Free Space on disk : $disk_space";
    $response->data->simpleData = $out;
    $response->sendDataJson();
}

function getDirectorySize($path)
{
    error_log($path);
    $totalsize = 0;
    $totalcount = 0;
    $dircount = 0;
    if($handle = opendir($path))
    {
        while (false !== ($file = readdir($handle)))
        {
            $nextpath = $path . '/' . $file;
            if($file != '.' && $file != '..' && !is_link ($nextpath))
            {
                if(is_dir($nextpath))
                {
                    $dircount++;
                    $result = getDirectorySize($nextpath);
                    $totalsize += $result['size'];
                    $totalcount += $result['count'];
                    $dircount += $result['dircount'];
                }
                else if(is_file ($nextpath))
                {
                    $totalsize += filesize ($nextpath);

                    $mb = 1024*1024;

                    if(filesize ($nextpath) > 10*$mb) {
                        //echo $nextpath.' : '. ( filesize ($nextpath) / $mb ) .' MB<br />';
                    }


                    $totalcount++;
                }
            }
        }
    }
    closedir($handle);
    $total['size'] = $totalsize;
    $total['count'] = $totalcount;
    $total['dircount'] = $dircount;
    return $total;
}