<?php
/**
 * Meta Info
 * FILE_NAME: change_wp_password.php
 * LABEL: Change WordPress Admin Password
 * LINK_MAIN: /user/change_wp_password
 *
 */



respond('POST', '/user/change_wp_password', 'user_change_wp_password');

/**
 * Change WordPress admin password.
 * If ypu are using any external plugins for hashing the it will not work.
 *
 * @param $request
 * @param $response
 * @param $app
 */
function user_change_wp_password($request, $response, $app)
{
    if ($request->password) {
        require TS_ABSPATH.TS_WPINC.'class-phpass.php';

        $wp_hasher = new PasswordHash(8, true);

        $new_hash = $wp_hasher->HashPassword($request->password);
//            TODO-Gopal Username Based Password Change
        $user_id=1;

        if ($app->db->update($app->db->users, array('user_pass' => $new_hash, 'user_activation_key' => ''), array('ID' => $user_id))) {
            $response->flash("WP-Admin Password changed !", "success");
            home($request, $response);
        } else {
            $response->flash("Failed to change WP-Admin password !", "danger");
            unset($request->password);
            change_wp_password($request, $response, $app);
        }
    } else {
        $response->data->title = "Change WP-Admin Password";
        $response->data->simpleData = "Please enter new password for WP-Admin";
        $response->data->form = true;
        $response->data->formData = array(
            array('name'  => 'link', 'type'  => 'hidden','value' => $_POST['link'] ),
            array('name'  => 'password', 'label' => 'New WP-Admin Password', 'type'  => 'password', 'value' => ''),
            array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Change Password')
        );
        $response->sendDataJson();
    }
}
