<?php

class Auth {

    protected static $key = 'najdhfuBNMxBHgYdg';

    public static function isLoggedIn()
    {
        self::startSession();
        if($_SESSION['authenticated'])
            return true;
        else
            return false;
    }

    public static function logIn($pass)
    {
        if('password'===$pass)
        {
            self::startSession();
            $_SESSION['authenticated'] = true;
            $_SESSION['fingerprint'] = md5( self::$key. $_SERVER['HTTP_USER_AGENT']. session_id());
            return true;
        } else {
            return false;
        }
    }

    public static function logOut()
    {
        self::startSession();
        unset($_SESSION['authenticated']);
        unset($_SESSION['fingerprint']);
    }

    public static function startSession() {
        if (session_id() === '') {
            session_start();
        }
    }
}

?>