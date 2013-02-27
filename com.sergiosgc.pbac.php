<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\pbac;

class Pbac {
    protected static $singleton = null;
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
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.permission', array($this, 'assert'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('permission', $this);
    }/*}}}*/
    public function has($permission) {/*{{{*/
        if ($permission == '') return true;

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

        return $result;
        
        // The code below is unreachable, and is here for documentation purposes only
        /*#
         * Filter a permission request, asserting the permission
         *
         * @param string Permission tag
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', $permission);
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
 * This plugin exposes an API for permission based access control.
 * It does not actually grant any permissions, so for it to be usable
 * a complementary plugin, such as `com.sergiosgc.rbac` must be 
 * installed.
 *
 * # Usage summary 
 *
 * This is the application-facing interface for access control based on 
 * permission requests. A permission is a string tag, developer-readable
 * but never directly exposed to the user. Example permissions:
 *
 *     'reader'
 *     'commenter'
 *     'edit_own_content'
 *     'edit_team_content'
 *     'super_admin'
 *
 * ## Client usage
 *
 * This plugin is to be used by other plugins, to state permissions required
 * for execution of an operation. Two entrypoints may be used: hook based or 
 * object-oriented.
 *
 * The hook based entry point is called like this:
 *
 *     \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', 'somePermission');
 *
 * If this plugin is installed, the callback will return only if the 
 * permission is granted. If the permission is not granted, an 
 * UnauthorizedAccessException is thrown. 
 *
 * If this plugin is not installed, the callback is a no-op.
 *
 * The object-oriented entry point introduces a dependency on this plugin
 * and is called like this:
 * 
 *     \com\sergiosgc\pbac\Pbac::getInstance()->assert('somePermission');
 *
 * or like this:
 *
 *     \com\sergiosgc\pbac\Pbac::getInstance()->has('somePermission');
 *
 * The first form will throw an exception if the permission is not granted. 
 * The second form will return false.
 *
 * Use the hook based entry point to avoid creating a dependency on this plugin,
 * or use the object-oriented if you are sure of the dependency. When in doubt,
 * use the hook based entry point.
 *
 * ## Granting permissions
 *
 * Granting permissions requires more infrastructure than what this plugin 
 * provides (user identification, authentication, session management). So,
 * in true AOP, this plugin does not do it. It fires a `com.sergiosgc.pbac`
 * hook, with an ungranted permission, and allows other plugins to grant the 
 * permission. One such plugin, designed to be a companion, is 
 * `com.sergiosgc.rbac` a role based access control plugin.
 *
 * The basic design driver is that client applications -- the code that 
 * actually needs to get permission to run -- do not need to care if 
 * permissions are organized in roles, directly assigned to users or dependent
 * on the day of the month. Plugins only require a permission, and that is 
 * then somehow dealt with.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
