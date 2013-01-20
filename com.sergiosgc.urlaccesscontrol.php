<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\urlaccesscontrol;

class UrlAccessControl {
    protected static $singleton = null;
    protected $urlPatterns = array();
    /**
     * Singleton pattern instance getter
     *
     * @return UrlAccessControl The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'injectAccessAssertions'), 5);
    }/*}}}*/
    public function config() {/*{{{*/
        $indexes = \com\sergiosgc\Facility::get('config')->getKeys('com.sergiosgc.urlaccesscontrol.url');
        foreach ($indexes as $i) {
            $permissions = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.urlaccesscontrol.url.' . $i . '.permissions');
            $permissions = explode(',', $permissions);
            $pattern = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.urlaccesscontrol.url.' . $i . '.pattern');
            $test = preg_match($pattern, '');
            if ($test === false) {
                throw new Exception(sprintf('Regular expression %s for url %s returned an error (check PHP warning log for details)',
                    $pattern, 'com.sergiosgc.urlaccesscontrol.url.' . $i));
            }
            $this->urlPatterns[$pattern] = $permissions;
        }
    }/*}}}*/
    public function injectAccessAssertions($handled) {/*{{{*/
        if ($handled) return $handled;
        foreach ($this->urlPatterns as $regex => $permissions) {
            if (preg_match($regex, $_SERVER['REQUEST_URI'])) {
                foreach ($permissions as $permission) {
                    \ZeroMass::getInstance()->do_callback('com.sergiosgc.permission', $permission);
                }
            }
        }
        return $handled;
    }/*}}}*/
}
class Exception extends \Exception { }
UrlAccessControl::getInstance();

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
