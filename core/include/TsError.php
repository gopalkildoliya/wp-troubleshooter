<?php
/**
 * Troubleshooter Error API.
 *
 * Contains the TsError class and the is_ts_error() function.
 *
 */

/**
 * Modified WordPress Error class.
 *
 * @package WordPress
 * @since 2.1.0
 */
class TsError {
    /**
     * Stores the list of errors.
     *
     * @since 2.1.0
     * @var array
     */
    public static $errors = array();

    /**
     * Initialize the error.
     *
     * @since 2.1.0
     *
     * @param string $message Error message
     */
    public function __construct( $message = '') {
        self::$errors[] = $message;
    }

    /**
     * Retrieve all error messages or error messages matching code.
     *
     * @since 2.1.0
     *
     * @return array Error strings on success, or empty array on failure (if using code parameter).
     */
    public function get_error_messages() {
        // Return all messages if no code specified.
        return self::$errors;

    }

    /**
     * Add an error or append additional message to an existing error.
     * Also add the error message in session flash to show.
     *
     * @since 2.1.0
     * @access public
     *
     * @param string $message Error message.
     */
    public static function add($message) {
        self::$errors[] = $message;
        startSession();
        if (!isset($_SESSION['__flashes'])) {
            $_SESSION['__flashes'] = array('danger' => array());
        } elseif (!isset($_SESSION['__flashes']['danger'])) {
            $_SESSION['__flashes']['danger'] = array();
        }
        $_SESSION['__flashes']['danger'][] = $message;
    }

    /**
     * Removes errors.
     *
     * This function removes all error messages.
     *
     * @since 4.1.0
     */
    public static function remove() {
        self::$errors = array();
    }
}

/**
 * Check whether variable is a Troubleshooter Error.
 *
 * Returns true if $thing is an object of the TsError class.
 *
 * @since 2.1.0
 *
 * @param mixed $thing Check if unknown variable is a TsError object.
 * @return bool True, if TsError. False, if not TsError.
 */
function is_ts_error( $thing ) {
    return ( $thing instanceof TsError );
}