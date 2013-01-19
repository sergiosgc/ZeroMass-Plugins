<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Pbac {
    protected static $singleton = null;
    protected $aPermissionWasChecked = false;
    /**
     * Singleton pattern instance getter
     *
     * @return Pbac The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('permission', $this);
    }/*}}}*/
    public function has($permission) {/*{{{*/
        $result = false;
        /*#
         * A permission request has been received. Attempt to grant the permission
         *
         * Plugins should hook here to grant permissions. The expected behavior is
         * that, if a permission is granted already, the plugin should return true, 
         * although nothing prevents _removing_ permissions (returning false even
         * if already granted by a previous plugin in the hook chain).
         *
         * @param bool True if permission granted
         * @param string Permission tag
         * @return bool True if permission granted
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.pbac', $result, $permission);
        if ($result) $this->aPermissionWasChecked = true;

        return $result;
    }/*}}}*/
    public function assert($permission) {/*{{{*/
        if (!$this->has($permission)) throw new UnauthorizedAccessException('Permission ' . $permission . ' not granted');
    }/*}}}*/
}
class Exception extends \Exception { }
class UnauthorizedAccessException extends Exception { }

Pbac::getInstance();

/*#
 * Permission based access control
 *
 * 
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
