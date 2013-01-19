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
        \com\sergiosgc\form\Form::registerAutoloader();
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
        switch (preg_replace('_\?.*$_', '', $_SERVER['REQUEST_URI'])) {
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
    public function generateSignupForm() {/*{{{*/
        $form = new form\Form($this->url['newaction'], 'Login', '');
        $form->addMember($input = new form\Input_Text('username'));
        $input->setLabel('Username');
        $input->addRestriction(new form\Restriction_Mandatory());
        if (isset($_REQUEST['username'])) $input->setValue($_REQUEST['username']);
        $form->addMember($input = new form\Input_Password('password'));
        if (isset($_REQUEST['password'])) $input->setValue($_REQUEST['password']);
        $input->setLabel('Password');
        $input->addRestriction(new form\Restriction_Mandatory());
        $form->addMember($input = new form\Input_Button('create'));
        $input->setLabel('Create user');

        /*#
         * The plugin has just created a form for user signup. Allow it to be mangled
         *
         * @param \com\sergiosgc\form\Form The signup form
         * @return \com\sergiosgc\form\Form The signup form
         */
        $form = @\ZeroMass::do_callback('com.sergiosgc.user.signup.form', $form);
        return $form;
    }/*}}}*/
    public function signupForm() {/*{{{*/
        $form = $this->generateSignupForm();
        $serializer = new \com\sergiosgc\form\Serializer_TwitterBootstrap();
        $serializer->setLayout('horizontal');

        /*#
         * The plugin is about to serialize the signup form. Allow the serializer to be mangled
         *
         * @param \com\sergiosgc\form\Form_Serializer The form serializer
         * @return \com\sergiosgc\form\Form_Serializer The form serializer
         */
        $serializer = @\ZeroMass::do_callback('com.sergiosgc.user.signup.form_serializer', $serializer);
        @\ZeroMass::do_callback('com.sergiosgc.contentType', 'text/html');
        /*#
         * The plugin is about to output the signup form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        echo @\ZeroMass::do_callback('com.sergiosgc.user.signup.form_html', $serializer->serialize($form));
    }/*}}}*/
    public function signup() {
        $form = $this->generateSignupForm();
        $validation = $form->validate();
        var_dump($form);
    }
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
