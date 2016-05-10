<?php
/**
 * Core functions for WordPress Troubleshooter
 */

    respond('POST', '/login', 'login');
    respond(array('POST','GET'), '/home/[:sublevel]?', 'home');
    respond('POST', '/logout', 'logout');
    respond('POST', '/quick-search', 'quick_search');

/**
 * Login to the troubleshooter
 * @param $request
 * @param $response
 */
function login($request, $response)
{
    if(Auth::isLoggedIn())
        home($request, $response);
    if ($request->password) {
        if (Auth::logIn($request->password)) {
            $response->flash("Logged in", "success");
            /*if(isset($request->backlink)){
                $response->discard(true);
                dispatch($request->backlink);
            } else*/
                home($request, $response);
        } else {
            $response->flash("Wrong password !!!", 'danger');
            $response->code(401);
        }
    } else {
        $response->flash("Please login first!!!", "danger");
        $response->data->title = "Home";
        $response->data->simpleData = "Please enter the password to access the troubleshooter.<br>
                             The password is given at the begaining of the script.";
        $response->data->form = true;
        $response->data->formData = array(
            array('name'  => 'link', 'type'  => 'hidden', 'value' => '/login'),
            array('name'  => 'password', 'label' => 'Password', 'type'  => 'password', 'value' => ''),
            array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Login')
        );
        $response->sendDataJson();
    }
}

/**
 * Logout from troubleshooter
 * @param $request
 * @param $response
 */
function logout($request, $response)
{
    Auth::logOut();
    $response->flash("Logged Out !!!");
    $response->data->title = "Log Out";
    $response->data->form = true;
    $response->data->formData = array(
        array('name'  => 'link', 'type'  => 'hidden', 'value' => '/home' ),
        array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Home' )
    );
    $response->sendDataJson();
}

/**
 * Shows the home level and sub-level menu.
 * @param $request
 * @param $response
 */
function home ($request, $response)
{
    global $options;
    if(isset($request->sublevel))
    {
        $response->data->title = $options[$request->sublevel]['label'];
        $response->data->simpleData = $options[$request->sublevel]['label'];
        $options = $options[$request->sublevel]['plugins'];
        array_walk($options, function(&$v, $k){
            $v = ['type'=> 'radio', 'name'=>'link', 'value'=>$v['link_main'], 'label'=>$v['label']];
        });
    }else{
        $response->data->title = "Home";
        $response->data->simpleData = "Welcome to <strong>WordPress TroubleShooter</strong>. Select a troubleshoot action. ";
        array_walk($options, function(&$v, $k){
            $v = ['type'=> 'radio', 'name'=>'link', 'value'=>'/home/'.$k, 'label'=>$v['label']];
        });
    }
    $options = array_values($options);
    $options[] = ['name'  => 'link', 'type'  => 'radio','value' => '/logout', 'label'=>'Logout'];
    $options[] = ['name'  => 'submit', 'type'  => 'submit','value' => 'Continue'];
    $response->data->form = true;
    $response->data->formData = $options;
    $response->sendDataJson();
}

function downloadFile($path, $name, $level=null)
{
    if($level){
        if(!is_dir($path))
            mkdir($path, 0777, true);
        $source = "https://raw.githubusercontent.com/gopalkildoliya/wp-troubleshooter/master/plugins/".$level.'/'.$name;
    }else{
        $source = "https://raw.githubusercontent.com/gopalkildoliya/wp-troubleshooter/master/plugins/".$name;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $source);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_SSLVERSION,3);
    $data = curl_exec ($ch);
    $error = curl_error($ch);
    curl_close ($ch);
    file_put_contents($path.$name, $data);
}

function quick_search($request, $response)
{
    global $options;
    $links=array();
    foreach($options as $name => $details){
        $links[] = ['link' =>'/home/'.$name, 'label' => $details['label']];
        foreach($details['plugins'] as $k => $v){
            $links[] = ['link' =>$v['link_main'], 'label' => $v['label']];
        }
    }
    $outlinks = array();
    foreach($links as $link){
        if (false === stripos( strtolower($link['label']), $request->str))
              continue;
            else {
                $link['label'] = str_ireplace($request->str, "<strong>".$request->str."</strong>", $link['label']);
                $outlinks[] = $link;
            }
    }
    $response->json($outlinks);
}

?>