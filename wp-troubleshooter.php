<?php
    define('ABSPATH', dirname(__FILE__) . '/');
    define( 'WPINC', 'wp-includes' );
    require ABSPATH.WPINC.'/class-phpass.php';

    $wp_hasher = new PasswordHash(8, true);

    $new_hash = $wp_hasher->HashPassword('newadmin');
    $user_id=1;

require 'core/index.php';

//$db->update($db->users, array('user_pass' => $new_hash, 'user_activation_key' => ''), array('ID' => $user_id) );

?>