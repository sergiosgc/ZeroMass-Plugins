<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;
use com\sergiosgc\form;

class User {
    protected static $singleton = null;
    protected $url = array(
        'login' => '/login/', 
        'loginaction' => '/actions/login/', 
        'afterlogin' => '/',
        'logoutaction' => '/actions/logout/',
        'afterlogout' => '/',
        'new' => '/signup/',
        'newaction' => '/actions/signup/',
    );
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.replaced_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('user', $this);
    }/*}}}*/
    public function config() {/*{{{*/
        foreach ($this->url as $key => $value) {
            $this->url[$key] = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.user.url.' . $key, false, $value);
        }
        
        /*#
         * Filter the URLs handled by this plugin.
         *
         * The URLs in the array are:
         *  - login: The login form
         *  - loginaction: Where the login form gets submitted to
         *  - afterlogin: Where the loginaction request gets redirected to
         *  - logoutaction: Request to be done to end the user session
         *  - afterlogout: Where the logoutaction request gets redirected to
         *  - new: The signup form
         *  - newaction: Where the signup form gets submitted to
         *
         * @param array All plugin URLs
         * @return array All plugin URLs
         */
        $this->url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.url', $this->url);
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        switch ($_SERVER['REQUEST_URI']) {
        case $this->url['new']:
            $this->signupForm();
            return true;
        case $this->url['newaction']:
            $this->signup();
            return true;
        case $this->url['login']:
            $this->login();
            return true;
        case $this->url['logoutaction']:
            $this->logout();
            return true;

        default: return $handled;
        }
    }/*}}}*/
    public function signupForm() {/*{{{*/
        $form = new form\Form($this->url['newaction'], 'Login', '');
        $form->addMember($input = new form\Input_Text('username'));
        $input->setLabel('Username');
        $form->addMember($input = new form\Input_Password('password'));
        $input->setLabel('Password');
        $form->addMember($input = new form\Input_Button('create'));
        $input->setLabel('Create user');

        @\ZeroMass::do_callback('com.sergiosgc.contentType', 'text/html');
        $serializer = new \com\sergiosgc\form\Serializer_TwitterBootstrap();
        $serializer->setLayout('horizontal');
        echo $serializer->serialize($form);
    }/*}}}*/
}

User::getInstance();

/*#
 * User authentication 
 *
 * Provides login, logout, credentials check and user registration facilities
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
