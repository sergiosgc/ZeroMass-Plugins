<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;
use com\sergiosgc\form;

class User {
    protected static $singleton = null;
    protected $url = array(
        'login' => '/login/',
        'logout' => '/logout/',
        'afterlogin' => '/',
        'afterlogout' => '/',
    );
    protected $entity = 'user';
    protected $redirectParam = 'redirectTo';
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
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.user.login.validate', array($this, 'validateLogin'));
    }/*}}}*/
    public function init() {/*{{{*/
        if (is_null(\com\sergiosgc\Facility::get('user', false))) \com\sergiosgc\Facility::register('user', $this);

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
         *  - afternew: Where the new POST request gets redirected to
         *
         * @param array All plugin URLs
         * @return array All plugin URLs
         */
        $this->url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.url', $this->url);
        /*#
         * Filter the entity where users will be found
         *
         * @param string REST entity
         * @return string REST entity
         */
        $this->entity = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.entity', $this->entity);
        /*#
         * Filter the name of the parameter where the redirect URI for login/logout will be found
         *
         * When redirecting after a successful login, the user will be redirected. By default, redirection
         * occurs to the 'afterlogin' and 'afterlogout' URLs, configurable via config and hook mechanisms. 
         * However, if a parameter named 'redirectTo' is present, it is used as destination URI for the 
         * redirect. The name of this parameter can be changed wity this hook.
         *
         * @param string REST entity
         * @return string REST entity
         */
        $this->redirectParam = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.redirectParam', $this->redirectParam);
    }/*}}}*/
    public function config() {/*{{{*/
        foreach ($this->url as $key => $value) {
            $this->url[$key] = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.user.url.' . $key, false, $value);
        }
        $this->entity = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.user.entity', false, $this->entity);
        $this->redirectParam = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.user.redirectParam', false, $this->redirectParam);

        $this->init();
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                switch (preg_replace('_\?.*$_', '', $_SERVER['REQUEST_URI'])) {
                case $this->url['login']:
                    $this->loginForm();
                    return true;
                case $this->url['logout']:
                    $this->logout();
                    return true;

                default: return $handled;
            }
            case 'POST':
                switch (preg_replace('_\?.*$_', '', $_SERVER['REQUEST_URI'])) {
                case $this->url['login']:
                    $this->login();
                    return true;
                case $this->url['logout']:
                    $this->logout();
                    return true;
                default: return $handled;
            }
            default:
                return $handled;
        }
    }/*}}}*/
    public function getLoggedIn() {/*{{{*/
        return \com\sergiosgc\Facility::get('session')->get('user', true);
    }/*}}}*/
    public function isLoggedIn() {/*{{{*/
        $user = \com\sergiosgc\Facility::get('session')->get('user', false, false);
        if ($user === false) return false;
        return true;
    }/*}}}*/
    public function getUrl($which) {/*{{{*/
        if (!isset($this->url[$which])) throw new \Exception('Unknown URL: ' . $which);
        return $this->url[$which];
    }/*}}}*/
    public function generateLoginForm() {/*{{{*/
        $form = new form\Form($this->url['login'], 'Login', '');
        $form->addMember($input = new form\Input_Text('username'));
        $input->setLabel('Username');
        $input->addRestriction(new form\Restriction_Mandatory());
        if (isset($_REQUEST['username'])) $input->setValue($_REQUEST['username']);
        $form->addMember($input = new form\Input_Password('password'));
        if (isset($_REQUEST['password'])) $input->setValue($_REQUEST['password']);
        $input->setLabel('Password');
        $input->addRestriction(new form\Restriction_Mandatory());
        $form->addMember($input = new form\Input_Button('create'));
        $input->setLabel('Login');

        /*#
         * The plugin has just created a form for user login. Allow it to be mangled
         *
         * @param \com\sergiosgc\form\Form The login form
         * @return \com\sergiosgc\form\Form The login form
         */
        $form = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.login.form', $form);
        return $form;
    }/*}}}*/
    public function loginForm($form = null) {/*{{{*/
        if (is_null($form)) $form = $this->generateLoginForm();
        $serializer = new \com\sergiosgc\form\Serializer_TwitterBootstrap();
        $serializer->setLayout('horizontal');

        /*#
         * The plugin is about to serialize the login form. Allow the serializer to be mangled
         *
         * @param \com\sergiosgc\form\Form_Serializer The form serializer
         * @return \com\sergiosgc\form\Form_Serializer The form serializer
         */
        $serializer = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.login.form_serializer', $serializer);
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        $serialized = $serializer->serialize($form);
        /*#
         * The plugin is about to output the login form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        echo \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.login.form_html', $serialized);
    }/*}}}*/
    public function hashPassword($password) {/*{{{*/
        $salt = '$2y$' . 'gutUvEvdafcodNisikfij7'; // Fallback salt
        $salt = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.user.hashsalt', false, $salt); // Try to get salt from config
        /*#
         * A password hash is being generated. Allow the salt to be defined
         * 
         * @param string Salt. Check documentation for PHP crypt()
         * @return string Salt. Check documentation for PHP crypt()
         */
        $salt = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.hashsalt', $salt); 
        $hashed = crypt($password, $salt);
        /*#
         * A password hash is being generated. Allow the hash to be short-circuited
         * 
         * @param string Hashed password
         * @param string Unhashed password
         * @return string Hashed password
         */
        $hashed = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.passwordhash', $hashed, $password); 
        return $hashed;
    }/*}}}*/
    public function login() {/*{{{*/
        $form = $this->generateLoginForm();
        $validation = $form->validate();
        /*#
         * The plugin is validating the login form. Allow validation to be mangled.
         *
         * @param bool|array Validation result as returned by \com\sergiosgc\form\Form::validate()
         * @param \com\sergiosgc\form\Form The login form
         * @return bool|array Validation result as returned by \com\sergiosgc\form\Form::validate()
         */
        $validation = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.login.validate', $validation, $form);
        if ($validation === true) {
            $user = \com\sergiosgc\Rest::getInstance()->read('user');
            
            \com\sergiosgc\Facility::get('session')->set('user', $user[0]['username']);
            $redirect = $this->url['afterlogin'];
            /*#
             * User login is ending and will HTTP redirect. Allow the location to be mangled
             *
             * @param string URL to redirect to
             * @param string Username
             * @return string URL to redirect to
             */
            $redirect = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.login.redirect', $redirect, $_REQUEST['username']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $this->loginForm($form);
        }
    }/*}}}*/
    public function validateLogin($validation, $form) {/*{{{*/
        $username = $form->getInput('username')->getValue();
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.read.where.fields', function($fields, $entity) {
            if ($entity != 'user') return $fields;
            $fields = array_merge($fields, array('username' => $_POST['username']));
            if (isset($fields['password'])) unset($fields['password']);
            return $fields;
        }, 5);
        $user = \com\sergiosgc\Rest::getInstance()->read('user');
        if (count($user) == 0) {
            $toAppend = array('username' => 'Username not found or wrong password', 'password' => 'Username not found or wrong password');
            $form->appendInputErrors($toAppend);
            if ($validation === true) $validation = array();
            $validation = array_merge($validation, $toAppend);
            return $validation;
        }
        $user = $user[0];
        $hashCheckResult = null;
        /*#
         * A password hash is being checked. Allow the check to be short-circuited.
         *
         * The filter receives the user input, the database-obtained hashed password and must return one of:
         *  - true: The user input password passes the hash check
         *  - false: The user input password does not pass the hash check and this is final
         *  - null: The user input password does not pass the hash check and should be tested against the default algorithm
         * 
         * @param bool|null Hash check result
         * @param string User input password to check
         * @param string Database stored password hash
         * @return bool|null Hash check result
         */
        $hashCheckResult = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.passwordhashcheck', $hashCheckResult, $_POST['password'], $user['password']);
        if (is_null($hashCheckResult)) {
            $hashCheckResult = (crypt($_POST['password'], $user['password']) == $user['password']);
        }
        if (!$hashCheckResult) {
            $toAppend = array('username' => 'Username not found or wrong password', 'password' => 'Username not found or wrong password');
            $form->appendInputErrors($toAppend);
            if ($validation === true) $validation = array();
            $validation = array_merge($validation, $toAppend);
            return $validation;
        }
        return $validation;
    }/*}}}*/
    public function logout() {/*{{{*/
        \com\sergiosgc\Facility::get('session')->delete('user');
        $redirect = $this->url['afterlogout'];
        /*#
         * User logout is ending and will HTTP redirect. Allow the location to be mangled
         *
         * @param string URL to redirect to
         * @param string Username
         * @return string URL to redirect to
         */
        $redirect = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.logout.redirect', $redirect);
        header('Location: ' . $redirect);
        exit;
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
