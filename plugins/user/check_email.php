<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 12/5/16
 * Time: 10:01 AM
 */

/**
 * Meta Info
 * FILE_NAME: check_email.php
 * LABEL: Check Email
 * LINK_MAIN: /user/check_email
 *
 */



respond('POST', '/user/check_email', 'user_check_email');

function user_check_email($request, $response)
{
    $response->data->title = "Check Email";
    if (isset($request->email)) {
        $request->validate('email', 'Enter a valid email')->isEmail();
        include ABSPATH.WPINC.'class-phpmailer.php';
        include ABSPATH.WPINC.'class-smtp.php';
        $phpmailer = new PHPMailer(true);
        $phpmailer->Hostname = 'localhost';
        $phpmailer->addAddress($request->email);
        $phpmailer->Subject = "Test email";
        $phpmailer->Body = "This is a test email from wordpress";
        $phpmailer->IsMail();
        $phpmailer->send();
    } else {
        $response->data->simpleData = "Enter the email address where you want to send the test email.";
        $response->data->form = true;
        $response->data->formData = array(
            array(
                'name'  => 'link',
                'type' => 'hidden',
                'value' => '/user/check_email'
            ),
            array(
                'name'  => 'email',
                'type' => 'text',
                'value' => '',
                'label' => 'Email'
            ),
            array(
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'Test'
            )
        );
    }
    $response->sendDataJson();
}
