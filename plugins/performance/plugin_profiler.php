<?php
/**
 * Created by PhpStorm.
 * User: gopal
 * Date: 16/5/16
 * Time: 2:30 PM
 */

/**
 * Meta Info
 * FILE_NAME: plugin_profiler.php
 * LABEL: Plugin load time profiler
 * LINK_MAIN: /performance/plugin_profiler
 *
 */

respond('POST','/performance/plugin_profiler', 'performance_plugin_profiler');

/*
 *  Class Start
 */


/**
 * Profiles a WordPress site
 *
 * @author GoDaddy.com
 * @version 1.0
 * @package P3_Profiler
 */
class Ts_P3_Profiler {

    /**
     * Time spent in WordPress Core
     * @var float
     */
    private $_core = 0;

    /**
     * Time spent in theme
     * @var float
     */
    private $_theme = 0;

    /**
     * Time spent in the profiler code
     * @var float
     */
    private $_runtime = 0;

    /**
     * Time spent in plugins
     * @var float
     */
    private $_plugin_runtime = 0;

    /**
     * Profile information, built up during the application's execution
     * @var array
     */
    public $_profile = array();

    /**
     * Stack trace of the last function call.  The stack is held here until
     * it's recorded.  It's not recorded until it's been timed.  It won't be
     * timed until after it's complete and the next function is in being
     * examined, so the $_last_stack will be moved to $_profile and the current
     * function will be moved to $_last_stack.
     * @var array
     */
    private $_last_stack = array();

    /**
     * Time spent in last function call
     * @var float
     */
    private $_last_call_time = 0;

    /**
     * Timestamp when the last function call was started
     * @var float
     */
    private $_last_call_start = 0;

    /**
     * How to categorize the last call ( core, theme, plugin )
     * @var int
     */
    private $_last_call_category = '';

    /**
     * Where to save the profile when it's done
     * @var string
     */
    private $_profile_filename = '';

    /**
     * App start time ( as close as we can measure )
     * @var float
     */
    private $_start_time = 0;

    /**
     * Path to ourselves
     * @var string
     */
    private $_P3_PATH = ''; // Cannot rely on P3_PATH, may be instantiated before the plugin

    /**
     * Debug log entry
     * @var array
     */
    private $_debug_entry = array();

    /**
     * Last stack should be marked as plugin time
     * @const
     */
    const CATEGORY_PLUGIN = 1;

    /**
     * Last stack should be marked as theme time
     * @const
     */
    const CATEGORY_THEME = 2;

    /**
     * Last stack should be marked as core time
     * @const
     */
    const CATEGORY_CORE = 3;

    /**
     * Constructor
     * Initialize the object, figure out if profiling is enabled, and if so,
     * start the profile.
     */
    public function __construct() {

        // Debug mode
        $this->_debug_entry = array(
            'profiling_enabled'  => false,
            'recording_ip'       => '',
            'scan_name'          => '',
            'recording'          => false,
            'disable_optimizers' => false,
            'url'                => $this->_get_url(),
            'visitor_ip'         => p3_profiler_get_ip(),
            'time'               => time(),
            'pid'                => getmypid()
        );




        // Kludge memory limit / time limit
        if ( (int) @ini_get( 'memory_limit' ) < 256 ) {
            @ini_set( 'memory_limit', '256M' );
        }
        @set_time_limit( 90 );

        // Set the profile file
        //$this->_profile_filename = $opts['profiling_enabled']['name'] . '.json';
        $this->_profile_filename = 'p3_profiler_test';

        // Start timing
        $this->_start_time      = microtime( true );
        $this->_last_call_start = microtime( true );

        // Reset state
        $this->_last_call_time     = 0;
        $this->_runtime            = 0;
        $this->_plugin_runtime     = 0;
        $this->_core               = 0;
        $this->_theme              = 0;
        $this->_last_call_category = self::CATEGORY_CORE;
        $this->_last_stack         = array();

        // Add some startup information
        $this->_profile = array(
            'url'   => $this->_get_url(),////////////////////--------------------
            'ip'    => p3_profiler_get_ip(),//////////////////-------------------
            'pid'   => getmypid(),
            'date'  => @date( 'c' ),
            'stack' => array()
        );

        // Disable opcode optimizers.  These "optimize" calls out of the stack
        // and hide calls from the tick handler and backtraces
        if ( true ) {
            if ( extension_loaded( 'xcache' ) ) {
                @ini_set( 'xcache.optimizer', false ); // Will be implemented in 2.0, here for future proofing
                // XCache seems to do some optimizing, anyway.  The recorded stack size is smaller with xcache.cacher enabled than without.
            } elseif ( extension_loaded( 'apc' ) ) {
                @ini_set( 'apc.optimization', 0 ); // Removed in APC 3.0.13 (2007-02-24)
                apc_clear_cache();
            } elseif ( extension_loaded( 'eaccelerator' ) ) {
                @ini_set( 'eaccelerator.optimizer', 0 );
                if ( function_exists( 'eaccelerator_optimizer' ) ) {
                    @eaccelerator_optimizer( false );
                }
                // If you're reading this, try setting eaccelerator.optimizer = 0 in a .user.ini or .htaccess file
            } elseif (extension_loaded( 'Zend Optimizer+' ) ) {
                @ini_set('zend_optimizerplus.optimization_level', 0);
            }
            // Tested with wincache
            // Tested with ioncube
            // Tested with zend guard loader
        }

        // Monitor all function-calls
        declare( ticks = 1 );
        register_tick_function( array( $this, 'ts_tick_handler' ) );
    }

    /**
     * In between every call, examine the stack trace time the calls, and record
     * the calls if the operations went through a plugin
     * @return void
     */
    public function ts_tick_handler() {
        declare( ticks = 1);
        static $theme_files_cache = array();         // Cache for theme files
        static $content_folder = '';
        if ( empty( $content_folder ) ) {
            $content_folder = basename( WP_CONTENT_DIR );
        }
        $themes_folder = 'themes';

        // Start timing time spent in the profiler
        $start = microtime( true );

        // Calculate the last call time
        $this->_last_call_time = ( $start - $this->_last_call_start );

       /* $fp = fopen('/work/back.txt', 'a');
        fwrite($fp, microtime(true) . "\n");
        fclose($fp);*/

        // If we had a stack in the queue, track the runtime, and write it to the log
        // array() !== $this->_last_stack is slightly faster than !empty( $this->_last_stack )
        // which is important since this is called on every tick
        if ( self::CATEGORY_PLUGIN == $this->_last_call_category && array() !== $this->_last_stack ) {
            // Write the stack to the profile
            $this->_plugin_runtime += $this->_last_call_time;

            // Add this stack to the profile
            $this->_profile['stack'][] = array(
                'plugin'  => $this->_last_stack['plugin'],
                'runtime' => $this->_last_call_time,
            );

            // Reset the stack
            $this->_last_stack = array();
        } elseif ( self::CATEGORY_THEME == $this->_last_call_category ) {
            $this->_theme += $this->_last_call_time;
        } elseif ( self::CATEGORY_CORE == $this->_last_call_category ) {
            $this->_core += $this->_last_call_time;
        }

        // Examine the current stack, see if we should track it.  It should be
        // related to a plugin file if we're going to track it
        static $is_540;
        static $is_536;

        if ( $is_540 == null ) {
            if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) == false ) {
                $is_540 = false;
                $is_536 = version_compare( PHP_VERSION, '5.3.6', '>=' );
            } else {
                $is_540 = true;
            }
        }

        if ( $is_540 ) { // if $ver >= 5.4.0
            $bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT );//////////////////-------
        } elseif ( $is_536 ) { // if $ver >= 5.3.6
            $bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT );////////////////////-----------
        } else { // if $ver < 5.3.6
            $bt = debug_backtrace( true );
        }

        // Find our function
        $frame = $bt[0];
        if ( isset( $bt[1] ) )
            $frame = $bt[1];
        /*if(defined('AKISMET_VERSION')) {
            print_r(debug_backtrace());
            die();
        }*/
        $lambda_file = isset( $bt[0]['file']{0} ) ? $bt[0]['file'] : '';

        //error_log(print_r($bt, true));
        // Free up memory
        unset( $bt );

        // Include/require
        if ( in_array( strtolower( $frame['function'] ), array( 'include', 'require', 'include_once', 'require_once' ) ) ) {
            $file = $frame['args'][0];

            // Object instances
        } elseif ( isset( $frame['object'] ) && method_exists( $frame['object'], $frame['function'] ) ) {
            try {
                $reflector = new ReflectionMethod( $frame['object'], $frame['function'] );
                $file      = $reflector->getFileName();
            } catch ( Exception $e ) {
            }

            // Static object calls
        } elseif ( isset( $frame['class'] ) && method_exists( $frame['class'], $frame['function'] ) ) {
            try {
                $reflector = new ReflectionMethod( $frame['class'], $frame['function'] );
                $file      = $reflector->getFileName();
            } catch ( Exception $e ) {
            }

            // Functions
        } elseif ( !empty( $frame['function'] ) && function_exists( $frame['function'] ) ) {
            try {
                $reflector = new ReflectionFunction( $frame['function'] );
                $file      = $reflector->getFileName();
            } catch ( Exception $e ) {
            }

            // Lambdas / closures
        } elseif ( '__lambda_func' == $frame['function'] || '{closure}' == $frame['function'] ) {
            $file = preg_replace( '/\(\d+\)\s+:\s+runtime-created function/', '', $lambda_file );

            // Files, no other hints
        } elseif ( isset( $frame['file'] ) ) {
            $file = $frame['file'];

            // No idea
        } else {
            $file = $_SERVER['SCRIPT_FILENAME'];
        }

        // Check for "eval()'d code"
        if ( strpos( $file, "eval()'d" ) ) {
            list($file, $junk) = explode(': eval(', $str, 2);
            $file = preg_replace('/\(\d*\)$/', '', $file);
        }

        // Is it a plugin?
        $plugin = $this->_is_a_plugin_file( $file );
        if ( $plugin ) {
            $plugin_name = $this->_get_plugin_name( $file );
        }

        // Is it a theme?
        $is_a_theme = false;
        if ( FALSE === $plugin ) {
            if ( !$is_a_theme && isset( $theme_files_cache[$file] ) ) {
                $is_a_theme = $theme_files_cache[$file];
            }

            $theme_files_cache[$file] = (
                ( FALSE !== strpos( $file, '/' . $themes_folder . '/' ) || FALSE !== strpos( $file, '\\'. $themes_folder . '\\' ) ) &&
                ( FALSE !== strpos( $file, '/' . $content_folder . '/' ) || FALSE !== strpos( $file, '\\' . $content_folder . '\\' ) )
            );
            $theme_files_cache[$file];

            if ( $theme_files_cache[$file] ) {
                $is_a_theme = true;
            }
        }

        // If we're in a plugin, queue up the stack to be timed and logged during the next tick
        if ( FALSE !== $plugin ) {
            $this->_last_stack         = array( 'plugin' => $plugin_name );
            $this->_last_call_category = self::CATEGORY_PLUGIN;

            // Track theme times - code can travel from core -> theme -> plugin, and the whole trace
            // will show up in the stack, but we can only categorize it as one time, so we prioritize
            // timing plugins over themes, and thems over the core.
        } elseif ( FALSE !== $is_a_theme ) {
            $this->_last_call_category = self::CATEGORY_THEME;
            if ( !isset( $this->_profile['theme_name'] ) ) {
                $this->_profile['theme_name'] = $file;
            }

            // We must be in the core
        } else {
            $this->_last_call_category = self::CATEGORY_CORE;
        }

        // Count the time spent in here as profiler runtime
        $tmp             = microtime( true );
        $this->_runtime += ( $tmp - $start );

        // Reset the timer for the next tick
        $this->_last_call_start = microtime( true );
    }

    /**
     * Check if the given file is in the plugins folder
     * @param string $file
     * @return bool
     */
    private function _is_a_plugin_file( $file ) {
        static $plugin_files_cache = array();
        static $plugins_folder     = 'plugins';    // Guess, if it's not defined
        static $muplugins_folder   = 'mu-plugins';
        static $content_folder     = 'wp-content';
        static $folder_flag        = false;

        // Set the plugins folder
        if ( !$folder_flag ) {
            $plugins_folder   = basename( WP_PLUGIN_DIR );
            $muplugins_folder = basename( WPMU_PLUGIN_DIR );
            $content_folder   = basename( WP_CONTENT_DIR );
            $folder_flag      = true;
        }

        if ( isset( $plugin_files_cache[$file] ) ) {
            return $plugin_files_cache[$file];
        }

        $plugin_files_cache[$file] = (
            (
                ( FALSE !== strpos( $file, '/' . $plugins_folder . '/' ) || FALSE !== stripos( $file, '\\' . $plugins_folder . '\\' ) ) ||
                ( FALSE !== strpos( $file, '/' . $muplugins_folder . '/' ) || FALSE !== stripos( $file, '\\' . $muplugins_folder . '\\' ) )
            ) &&
            ( FALSE !== strpos( $file, '/' . $content_folder . '/' ) || FALSE !== stripos( $file, '\\' . $content_folder . '\\' ) )
        );

        return $plugin_files_cache[$file];
    }

    /**
     * Guess a plugin's name from the file path
     * @param string $path
     * @return string
     */
    private function _get_plugin_name( $path ) {
        static $seen_files_cache = array();
        static $plugins_folder   = 'plugins';    // Guess, if it's not defined
        static $muplugins_folder = 'mu-plugins';
        static $content_folder   = 'wp-content';
        static $folder_flag      = false;

        // Set the plugins folder
        if ( !$folder_flag ) {
            $plugins_folder   = basename( WP_PLUGIN_DIR );
            $muplugins_folder = basename( WPMU_PLUGIN_DIR );
            $content_folder   = basename( WP_CONTENT_DIR );
            $folder_flag      = true;
        }

        // Check the cache
        if ( isset( $seen_files_cache[$path] ) ) {
            return $seen_files_cache[$path];
        }

        // Trim off the base path
        $_path = realpath( $path );
        if ( FALSE !== strpos( $_path, '/' . $content_folder . '/' . $plugins_folder . '/' ) ) {
            $_path = substr(
                $_path,
                strpos( $_path, '/' . $content_folder . '/' . $plugins_folder . '/' ) +
                strlen( '/' . $content_folder . '/' . $plugins_folder . '/' )
            );
        } elseif ( FALSE !== stripos( $_path, '\\' . $content_folder . '\\' . $plugins_folder . '\\' ) ) {
            $_path = substr(
                $_path,
                stripos( $_path, '\\' . $content_folder . '\\' . $plugins_folder . '\\' ) +
                strlen( '\\' . $content_folder . '\\' . $plugins_folder . '\\' )
            );
        } elseif ( FALSE !== strpos( $_path, '/' . $content_folder . '/' . $muplugins_folder . '/' ) ) {
            $_path = substr(
                $_path,
                strpos( $_path, '/' . $content_folder . '/' . $muplugins_folder . '/' ) +
                strlen( '/' . $content_folder . '/' . $muplugins_folder . '/' )
            );
        } elseif ( FALSE !== stripos( $_path, '\\' . $content_folder . '\\' . $muplugins_folder . '\\' ) ) {
            $_path = substr(
                $_path, stripos( $_path, '\\' . $content_folder . '\\' . $muplugins_folder . '\\' ) +
                strlen( '\\' . $content_folder . '\\' . $muplugins_folder . '\\' )
            );
        }

        // Grab the plugin name as a folder or a file
        if ( FALSE !== strpos( $_path, DIRECTORY_SEPARATOR ) ) {
            $plugin = substr( $_path, 0, strpos( $_path, DIRECTORY_SEPARATOR ) );
        } else {
            $plugin = substr( $_path, 0, stripos( $_path, '.php' ) );
        }

        // Save it to the cache
        $seen_files_cache[$path] = $plugin;

        // Return
        return $plugin;
    }

    /**
     * Shutdown handler function
     * @return void
     */
    public function shutdown_handler() {
        unregister_tick_function(array($this, 'ts_tick_handler'));
        // Detect fatal errors (e.g. out of memory errors)
        $error = error_get_last();
        if ( empty( $error ) || E_ERROR !== $error['type'] ) {
    //        delete_option( 'p3_profiler-error_detection' );
        } else {
            /*update_option( 'p3_notices', array( array(
                'msg'   => sprintf( __( 'A fatal error occurred during profiling: %s in file %s on line %d ', 'p3-profiler' ), $error['message'], $error['file'], $error['line'] ),
                'error' => true,
            ) ) );*/
        }
        unset( $error );

        // Write debug log
        /*$opts = get_option('p3-profiler_options' );
        if ( !empty( $opts['debug'] ) ) {
            $this->_write_debug_log();
        }*/

        // Make sure we've actually started ( wp-cron??)
        /*if ( !defined( 'WPP_PROFILING_STARTED' ) || !WPP_PROFILING_STARTED ) {
            return;
        }*/

        // Last call time
        $this->_last_call_time = ( microtime( true ) - $this->_last_call_start );

        // Account for the last stack we measured
        if ( self::CATEGORY_PLUGIN == $this->_last_call_category && array() !== $this->_last_stack ) {
            // Write the stack to the profile
            $this->_plugin_runtime += $this->_last_call_time;

            // Add this stack to the profile
            $this->_profile['stack'][] = array(
                'plugin'  => $this->_last_stack['plugin'],
                'runtime' => $this->_last_call_time,
            );

            // Reset the stack
            $this->_last_stack = array();
        } elseif ( self::CATEGORY_THEME == $this->_last_call_category ) {
            $this->_theme += $this->_last_call_time;
        } elseif ( self::CATEGORY_CORE == $this->_last_call_category ) {
            $this->_core += $this->_last_call_time;
        }

        // Total runtime by plugin
        $plugin_totals = array();
        if ( !empty( $this->_profile['stack'] ) ) {
            foreach ( $this->_profile['stack'] as $stack ) {
                if ( empty( $plugin_totals[$stack['plugin']] ) ) {
                    $plugin_totals[$stack['plugin']] = 0;
                }
                $plugin_totals[$stack['plugin']] += $stack['runtime'];
            }
        }
        foreach ( $plugin_totals as $k => $v ) {
            $plugin_totals[$k] = $v;
        }

        // Stop timing total run
        $tmp     = microtime( true );
        $runtime = ( $tmp - $this->_start_time );

        // Count the time spent in here as profiler runtime
        $this->_runtime += ( $tmp - $this->_last_call_start );

        // Is the whole script a plugin? ( e.g. http://mysite.com/wp-content/plugins/somescript.php )
        if ( $this->_is_a_plugin_file( $_SERVER['SCRIPT_FILENAME'] ) ) {
            $this->_profile['runtime'] = array(
                'total'     => $runtime,
                'wordpress' => 0,
                'theme'     => 0,
                'plugins'   => ( $runtime - $this->_runtime ),
                'profile'   => $this->_runtime,
                'breakdown' => array(
                    $this->_get_plugin_name( $_SERVER['SCRIPT_FILENAME'] ) => ( $runtime - $this->_runtime ),
                )
            );
        } elseif (
            ( FALSE !== strpos( $_SERVER['SCRIPT_FILENAME'], '/themes/' ) || FALSE !== stripos( $_SERVER['SCRIPT_FILENAME'], '\\themes\\' ) ) &&
            (
                FALSE !== strpos( $_SERVER['SCRIPT_FILENAME'], '/' . basename( WP_CONTENT_DIR ) . '/' ) ||
                FALSE !== stripos( $_SERVER['SCRIPT_FILENAME'], '\\' . basename( WP_CONTENT_DIR ) . '\\' )
            )
        ) {
            $this->_profile['runtime'] = array(
                'total'     => $runtime,
                'wordpress' => 0.0,
                'theme'     => ( $runtime - $this->_runtime ),
                'plugins'   => 0.0,
                'profile'   => $this->_runtime,
                'breakdown' => array()
            );
        } else {
            // Add runtime information
            $this->_profile['runtime'] = array(
                'total'     => $runtime,
                'wordpress' => $this->_core,
                'theme'     => $this->_theme,
                'plugins'   => $this->_plugin_runtime,
                'profile'   => $this->_runtime,
                'breakdown' => $plugin_totals,
            );
        }

        // Additional metrics
        $this->_profile['memory']    = memory_get_peak_usage( true );
        $this->_profile['stacksize'] = count( $this->_profile['stack'] );
        //$this->_profile['queries']   = get_num_queries();

        // Throw away unneeded information to make the profiles smaller
        unset( $this->_profile['stack'] );

        // Write the profile file
        /*$transient   = get_option( 'p3_scan_' . $opts['profiling_enabled']['name'] );
        if ( false === $transient ) {
            $transient = '';
        }
        $transient  .= json_encode( $this->_profile ) . PHP_EOL;
        update_option( 'p3_scan_' . $opts['profiling_enabled']['name'], $transient );*/
    }

    /**
     * Get the current URL
     * @return string
     */
    private function _get_url() {
        static $url = '/2016/05/03/et-et-quia-cum-quas-ab/';
        if ( !empty( $url ) ) {
            return $url;
        }
        //$url = esc_url( remove_query_arg( 'P3_NOCACHE', $_SERVER['REQUEST_URI'] ) );
        return $url;
    }
}
function p3_profiler_get_ip() {
    static $ip = '';
    if ( !empty( $ip ) ) {
        return $ip;
    } else {
        if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( !empty ( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
global $p3Profiler;
/*
 *  Class end
 */

function performance_plugin_profiler(TsRequest $request, TsResponse $response)
{
    if (isset($request->url)) {
        if ( !defined('ABSPATH') )
            define('ABSPATH', TS_ABSPATH);
        define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
        if ( !defined('WP_PLUGIN_DIR') )
            define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' ); // full path, no trailing slash
        if ( !defined('WPMU_PLUGIN_DIR') )
            define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' ); // full path, no trailing slash

        // TODO-Gopal : Full url
        $_SERVER['REQUEST_URI'] = $request->url;
        /** Loads the WordPress Environment and Template */
        global $p3Profiler;
        $p3Profiler = new Ts_P3_Profiler();
        //declare( ticks = 1);
        define('WP_USE_THEMES', true);
        //require  TS_ABSPATH. 'wp-blog-header.php';
        define('INCLUDE_WORDPRESS', true);

    } else {
        $response->data->title = "Plugin Profiler";
        $response->data->simpleData = "Enter the url to profile the plugin performance.";
        $response->data->form = true;
        $response->data->formData = array(
            array('name'  => 'link', 'type'  => 'hidden', 'value' => $request->link),
            array('name'  => 'url', 'label' => 'Url', 'type'  => 'text', 'value' => ''),
            array('name'  => 'submit', 'type'  => 'submit', 'value' => 'Scan')
        );
        $response->sendDataJson();
    }
}

function afterWordPress()
{
    $response = new TsResponse();
    global $p3Profiler;
    $p3Profiler->shutdown_handler();
    $response->data->title = "Plugin Profiler";
    $text = '<strong>Rountime</strong>';
    $text .= "<br>Total time : ".sprintf('%0.4f sec.', $p3Profiler->_profile['runtime']['total']);
    $text .= "<br>WordPress : ".sprintf('%0.4f sec.', $p3Profiler->_profile['runtime']['wordpress']);
    $text .= "<br>Theme : ".sprintf('%0.4f sec.', $p3Profiler->_profile['runtime']['theme']);
    $text .= "<br>Plugins : ".sprintf('%0.4f sec.', $p3Profiler->_profile['runtime']['plugins']);
    $text .= "<br>Profile : ".sprintf('%0.4f sec.', $p3Profiler->_profile['runtime']['profile']);
    $text .="<hr>";
    $response->data->simpleData = $text;
    $response->data->table = true;
    $plugin_arr = $p3Profiler->_profile['runtime']['breakdown'];
    $table_rows = array();
    foreach($plugin_arr as $k => $v) {
        $table_rows[] = [$k, $v];
    }
    $response->data->tableData = $table_rows;
    $response->data->tableColumns = array(['title'=>'Plugin'],
        ['title'=>'Time(s)']);
    $response->code(200);
    $response->sendDataJson();
    ob_end_flush();
}