<?php
/**
 * Core functions for WordPress Troubleshooter
 */

    respond('POST', '/login', 'login');
    respond(array('POST','GET'), '/home/[:sublevel]?', 'home');
    respond('POST', '/logout', 'logout');

/**
 * Login to the troubleshooter
 * @param $request
 * @param $response
 */
function login($request, $response)
{

    if ($request->password) {
        if (Auth::logIn($request->password)) {
            $response->flash("Logged in", "success");
            home($request, $response);
        } else {
            $response->flash("Wrong password !!!", 'danger');
            $response->code(401);
        }
    } else {
        $response->flash("Please login first!!!", "danger");
        $data = new JsonOutput();
        $data->title = "Home";
        $data->simpleData = "Please enter the password to access the troubleshooter.<br>
                             The password is given at the begaining of the script.";
        $data->form = true;
        $data->formData = array(
            array('name'  => 'link', 'type'  => 'hidden', 'value' => '/login'),
            array('name'  => 'password', 'label' => 'Password', 'type'  => 'password', 'value' => ''),
            array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Login')
        );
        $data->flash = $response->flashes();
        $response->json($data);
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
    $data = new JsonOutput();
    $data->title = "Log Out";
    $data->form = true;
    $data->formData = array(
        array('name'  => 'link', 'type'  => 'hidden', 'value' => '/home' ),
        array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Home' )
    );
    $data->flash = $response->flashes();
    $response->json($data);
}

/**
 * Shows the home level and sub-level menu.
 * @param $request
 * @param $response
 */
function home ($request, $response)
{
    global $options;
    $data = new JsonOutput();
    if(isset($request->sublevel))
    {
        $data->title = $options[$request->sublevel]['label'];
        $data->simpleData = $options[$request->sublevel]['label'];
        $options = $options[$request->sublevel]['plugins'];
        array_walk($options, function(&$v, $k){
            $v = ['type'=> 'radio', 'name'=>'link', 'value'=>$v['link_main'], 'label'=>$v['label']];
        });
    }else{
        $data->title = "Home";
        $data->simpleData = "Welcome to <strong>WordPress TroubleShooter</strong>. Select a troubleshoot action. ";
        array_walk($options, function(&$v, $k){
            $v = ['type'=> 'radio', 'name'=>'link', 'value'=>'/home/'.$k, 'label'=>$v['label']];
        });
    }
    $options = array_values($options);
    $options[] = ['name'  => 'link', 'type'  => 'radio','value' => '/logout', 'label'=>'Logout'];
    $options[] = ['name'  => 'submit', 'type'  => 'submit','value' => 'Continue'];
    $data->form = true;
    $data->formData = $options;
    $data->flash = $response->flashes();
    $response->json($data);
}

function downloadFile($path, $name, $level=null)
{
    if($level){
        if(!is_dir($path))
            mkdir($path, 0777, true);
        file_put_contents($path.'/'.$name, file_get_contents("https://raw.githubusercontent.com/gopalkildoliya/wp-troubleshooter/master/plugins/".$level.'/'.$name));
    }else{
        file_put_contents($path.$name, file_get_contents("https://raw.githubusercontent.com/gopalkildoliya/wp-troubleshooter/master/plugins/".$name));
    }
}

?>