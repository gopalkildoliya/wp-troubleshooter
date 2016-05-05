<?php
    respond('GET', '/login', function($request, $response){
        global $title, $content;

    });

    respond('POST', '/login', function($request, $response){
        if($request->password){
            if(Auth::logIn($request->password)) {
                $response->flash("Logged in", "success");
            } else{
                $response->flash("Wrong password !!!", 'danger');

            }

            $data = new JsonOutput();
            $data->title = "Home";
            $data->simpleData = "Welcome to home";
            $data->flash = $response->flashes();
            $response->json($data);
        } else {
            $response->flash("Please login first!!!", "danger");
            $data = new JsonOutput();
            $data->title = "Home";
            $data->simpleData = "Please enter the password";
            $data->form = true;
            $data->formData = array(
                array(
                    'name' => 'link',
                    'type' => 'hidden',
                    'value' => $_POST['link']
                ),
                array(
                    'name'  =>  'password',
                    'label' =>  'Password',
                    'type'  =>  'password',
                    'value' =>  ''
                ),
                array(
                    'name'  =>  'submit',
                    'type'  =>  'submit',
                    'value' =>  'Login'
                )
            );
            $data->flash = $response->flashes();
            $response->json($data);
        }
        /*if(Auth::logIn($request->password)) {
            } else{
            $response->flash("Wrong password !!!", 'danger');
            $response->back();
        }*/
    });

    respond(array('POST','GET'), '/home', function($request, $response){
        $data = new JsonOutput();
        $data->title = "Home";
        $data->simpleData = "Welcome to home";
        $response->code(401);
        //$response->json($data);
    });

?>