<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\accesscontrolsafeguard;

class AccessControlSafeguard {
    protected static $singleton = null;
    protected $enabled = true;
    /**
     * Singleton pattern instance getter
     *
     * @return AccessControlSafeguard The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'alarm'), 90);
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.permission', array($this, 'disableAlarm'));
    }/*}}}*/
    public function disableAlarm($permission) {/*{{{*/
        $this->enabled = false;
        return $permission;
    }/*}}}*/
    public function alarm($pageHandled) {/*{{{*/
        if ($this->enabled) throw new Exception('Page did not call for access control (com.sergiosgc.permission)');
        return $pageHandled;
    }/*}}}*/
}
class Exception extends \Exception { }
AccessControlSafeguard::getInstance();

/*#
 * Single line
 *
 * Longer desc
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
