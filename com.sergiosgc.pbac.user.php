<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\pbac;

class User {
    protected static $singleton = null;
    /**
     * Singleton pattern instance getter
     *
     * @return User The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.hook.exception', array($this, 'redirectUser'));
    }/*}}}*/
    public function redirectUser($e) {/*{{{*/
        if ($e instanceof \com\sergiosgc\pbac\UnauthorizedAccessException) {
            try {
                \com\sergiosgc\Facility::get('user')->getLoggedIn();
                return $e;
            } catch (\Exception $e) {
                $url = \com\sergiosgc\Facility::get('user')->getUrl('login');
                $url .= (strpos('?', $url) === false ? '?' : '&') . 'afterlogin=' . urlencode($_SERVER['REQUEST_URI']);
                header('Location: ' . $url);
                exit;
            }
        }
        return $e;
    }/*}}}*/
}

User::getInstance();

/*#
 * Handle permission based access control unauthorized exceptions by redirecting logged in users to the login page
 *
 * This plugin glues com.sergiosgc.user and com.sergiosgc.pbac, handling the case where a user
 * does not have a permission because he is not logged in, redirecting the user to the login page, as
 * reported by com.sergiosgc.user
 *
 * # Usage summary 
 *
 * This plugin does not require special configuration. Just drop it in your plugin directory.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
