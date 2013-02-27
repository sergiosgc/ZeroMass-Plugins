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
    /**
     * Disable the alarm. A permission has been requested
     *
     * This function is not meant to be publicly used. It's hooked to the 
     * com.sergiosgc.permission callback and uses it as a notification 
     * mechanism that the current page has requested a mechanism and so 
     * this plugin may be disabled (not report any error on page end)
     *
     * @param string permission
     * @return string The argument permission, unchanged
     */
    public function disableAlarm($permission) {/*{{{*/
        $this->enabled = false;
        return $permission;
    }/*}}}*/
    /**
     * Throw an exception if no permission has been requested yet.
     *
     * This function is not meant to be publicly used. It's hooked to the
     * com.sergiosgc.zeromass.answerPage callback, running after all other
     * callback handlers. If, by that time, no request for permission was 
     * made, it will throw an exception
     *
     * @param bool Page was handled
     * @return bool The single argument, unchanged
     */
    public function alarm($pageHandled) {/*{{{*/
        if ($this->enabled) throw new Exception('Page did not call for access control (com.sergiosgc.permission)');
        return $pageHandled;
    }/*}}}*/
}
class Exception extends \Exception { }
AccessControlSafeguard::getInstance();

/*#
 * Throw an exception if a page is handled without asking for any permission
 *
 * This plugin is a companion to com.sergiosgc.pbac, useful for development.
 * It registers itself to be run when a page has been executed, and throws 
 * an exception if, during the course of page execution, no permission has 
 * been requested.
 *
 * # Usage summary 
 *
 * To use this plugin, just install it. It requires no special configuration.
 *
 * The plugin is not meant to be a security safeguard on production systems. 
 * Since it runs its check _after_ page execution, it can't prevent security
 * problems. It does, however, signal a pretty obvious _smell_ in permission 
 * based access control: an application entry point that requests no permission
 * at all. 
 *
 * A permission, using the com.sergiosgc.pbac plugin is requested by firing the 
 * `com.sergiosgc.permission` hook like this:
 *
 *     \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', 'some_permission');
 *
 * Note that, if a page is public and needs no permission to be run, it may 
 * signal so using the empty permission
 *
 *     \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', '');
 *
 * A page not requesting any permission is considered a _smell_ that permission
 * based access control was forgotten.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
