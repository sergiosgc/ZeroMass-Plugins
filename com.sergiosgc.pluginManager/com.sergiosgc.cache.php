<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;


/** 
 * Backup implementation of a cache, using PHP sessions
 */
class Cache {
    protected static $singleton = null;
    protected function __construct() {/*{{{*/
        Cache::$singleton = $this;
    }/*}}}*/
    public static function getInstance() {/*{{{*/
        if (is_null(Cache::$singleton)) Cache::$singleton = new Cache();
        return Cache::$singleton;
    }/*}}}*/
    protected function session_start() {/*{{{*/
        if (function_exists('session_status')) { // Use only this path when PHP 5.4 is common enough
            switch (session_status()) {
                case PHP_SESSION_NONE:
                    session_start();
                case PHP_SESSION_ACTIVE:
                    return;
                case PHP_SESSION_DISABLED:
                default:
                    throw new \ZeroMassException('Sessions are disabled, and needed by \com\sergiosgc\zeromass\Cache');
            }
        } else {
            if (session_id() == "") session_start();
            return;
        }
    }/*}}}*/
    public function set($key, $value, $expiry = null, $absoluteExpiry = false) {/*{{{*/
        if (!is_null($expiry) && !$absoluteExpiry) {
            $absoluteExpiry = true;
            $expiry = mktime() + $expiry;
        }
        $this->session_start();
        if (!isset($_SESSION['com.sergiosgc.zeromass.cache'])) $_SESSION['com.sergiosgc.zeromass.cache'] = array();
        $_SESSION['com.sergiosgc.zeromass.cache'][$key] = array(
            'expiry' => $expiry,
            'value' => $value 
        );
    }/*}}}*/
    public function get($key, $functionOrDefault = null) {/*{{{*/
        $this->session_start();
        if (isset($_SESSION['com.sergiosgc.zeromass.cache']) && isset($_SESSION['com.sergiosgc.zeromass.cache'][$key]) && (
                is_null($_SESSION['com.sergiosgc.zeromass.cache'][$key]['expiry']) ||
                $_SESSION['com.sergiosgc.zeromass.cache'][$key]['expiry'] > mktime()
            )) {
            return $_SESSION['com.sergiosgc.zeromass.cache'][$key]['value'];
        }
        if (!is_callable($functionOrDefault)) return $functionOrDefault;
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        return call_user_func_array($functionOrDefault, $args);
    }/*}}}*/
}
?>
