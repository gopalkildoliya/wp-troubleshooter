<?php

    respond('POST', '/login', 'login');
    respond(array('POST','GET'), '/home', 'home');

    $options[] = ['path'=>'/change_wp_password', 'label'=>'Change WP-Admin Password'];
    respond('POST','/change_wp_password', 'change_wp_password');

    $options[] = ['path'=>'/logout', 'label'=>'Logout'];
    respond('POST', '/logout', 'logout');

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
            $data->simpleData = "Please enter the password";
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

    function home ($request, $response){
        global $options;
        $options = array_map(function($a){
            $a['type'] = 'radio';
            $a['name'] = 'link';
            $a['value'] = $a['path'];
            return $a;
        }, $options);
        $options[] = ['name'  => 'submit', 'type'  => 'submit','value' => 'Continue'];
        $data = new JsonOutput();
        $data->title = "Home";
        $data->simpleData = "Welcome to <strong>WordPress TroubleShooter</strong>. Select a troubleshoot action. ";
        $data->form = true;
        $data->formData = $options;
        $data->flash = $response->flashes();
        $response->json($data);
    }

    function change_wp_password($request, $response, $app)
    {
        if ($request->password) {
            require ABSPATH.WPINC.'/class-phpass.php';

            $wp_hasher = new PasswordHash(8, true);

            $new_hash = $wp_hasher->HashPassword($request->password);
            $user_id=1;

            $app->db->update($app->db->users, array('user_pass' => $new_hash, 'user_activation_key' => ''), array('ID' => $user_id) );
            $response->flash("WP-Admin Password changed !", "success");
            home($request, $response);
        } else {
            $data = new JsonOutput();
            $data->title = "Change WP-Admin Password";
            $data->simpleData = "Please enter new password for WP-Admin";
            $data->form = true;
            $data->formData = array(
                array('name'  => 'link', 'type'  => 'hidden','value' => $_POST['link'] ),
                array('name'  => 'password', 'label' => 'New WP-Admin Password', 'type'  => 'password', 'value' => ''),
                array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Change Password')
            );
            $response->json($data);
        }
    }

?>