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
        'logoutaction' => '/logout/',
        'afterlogout' => '/',
        'new' => '/signup/',
        'afternew' => '/'
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
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_REST', array($this, 'registerRestEntities'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.user.signup.validate', array($this, 'validateUniqueUsernameConstraint'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.create.fields', array($this, 'hashPasswordOnCreateUpdate'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.fields', array($this, 'hashPasswordOnCreateUpdate'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.user.login.validate', array($this, 'validateLogin'));
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
         *  - afternew: Where the new POST request gets redirected to
         *
         * @param array All plugin URLs
         * @return array All plugin URLs
         */
        $this->url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.url', $this->url);
    }/*}}}*/
    public function registerRestEntities() {/*{{{*/
        \com\sergiosgc\Rest::getInstance()->registerEntity('user', 'user');
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                switch (preg_replace('_\?.*$_', '', $_SERVER['REQUEST_URI'])) {
                case $this->url['new']:
                    $this->signupForm();
                    return true;
                case $this->url['login']:
                    $this->loginForm();
                    return true;
                case $this->url['logoutaction']:
                    $this->logout();
                    return true;

                default: return $handled;
            }
            case 'POST':
                switch (preg_replace('_\?.*$_', '', $_SERVER['REQUEST_URI'])) {
                case $this->url['new']:
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
            default:
                return $handled;
        }
    }/*}}}*/
    public function generateSignupForm() {/*{{{*/
        $form = new form\Form($this->url['new'], 'Login', '');
        $form->addMember($input = new form\Input_Text('username'));
        $input->setLabel('Username');
        $input->setHelp('Your unique user identifier');
        $input->addRestriction(new form\Restriction_Mandatory());
        if (isset($_REQUEST['username'])) $input->setValue($_REQUEST['username']);
        $form->addMember($input = new form\Input_Password('password'));
        if (isset($_REQUEST['password'])) $input->setValue($_REQUEST['password']);
        $input->setLabel('Password');
        $input->setHelp('A secret password, easily memorizable, difficult to guess');
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
    public function signupForm($form = null) {/*{{{*/
        if (is_null($form)) $form = $this->generateSignupForm();
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
        $serialized = $serializer->serialize($form);
        /*#
         * The plugin is about to output the signup form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        echo @\ZeroMass::do_callback('com.sergiosgc.user.signup.form_html', $serialized);
    }/*}}}*/
    public function signup() {/*{{{*/
        $form = $this->generateSignupForm();
        $validation = $form->validate();
        /*#
         * The plugin is validating the signup form. Allow validation to be mangled.
         *
         * @param bool|array Validation result as returned by \com\sergiosgc\form\Form::validate()
         * @param \com\sergiosgc\form\Form The signup form
         * @return bool|array Validation result as returned by \com\sergiosgc\form\Form::validate()
         */
        $validation = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.signup.validate', $validation, $form);
        if ($validation === true) {
            $result = \com\sergiosgc\Rest::getInstance()->create('user');
            /*#
             * An user has just been created from an UI action.
             *
             * @param string Username
             */
            \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.signup.created', $_REQUEST['username']);
            $redirect = $this->url['afternew'];
            /*#
             * User signup is ending and will HTTP redirect. Allow the location to be mangled
             *
             * @param string URL to redirect to
             * @param string Username
             * @return string URL to redirect to
             */
            $redirect = \ZeroMass::getInstance()->do_callback('com.sergiosgc.user.signup.redirect', $redirect, $_REQUEST['username']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $this->signupForm($form);
        }
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
    public function validateUniqueUsernameConstraint($validation, $form) {/*{{{*/
        $username = $form->getInput('username')->getValue();
        $count = \com\sergiosgc\Facility::get('db')->fetchValue(<<<EOS
SELECT 
 count(*)
FROM 
 "user"
WHERE
 username = ?
EOS
        , $username);
        if ($count == 0) return $validation;

        $toAppend = array('username' => 'Username already registered');
        $form->appendInputErrors($toAppend);
        if ($validation === true) $validation = array();
        $validation = array_merge($validation, $toAppend);
        return $validation;
    }/*}}}*/
    public function hashPasswordOnCreateUpdate($fields, $entity) {/*{{{*/
        if ($entity != 'user') return $fields;
        if (!isset($fields['password'])) return $fields;
        $fields['password'] = $this->hashPassword($fields['password']);
        return $fields;
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
        $serializer = @\ZeroMass::do_callback('com.sergiosgc.user.login.form_serializer', $serializer);
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
            \com\sergiosgc\Facility::get('session')->set('user', $_REQUEST['username']);
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
        });
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
    public function getLoggedIn() {/*{{{*/
        return \com\sergiosgc\Facility::get('session')->get('user', true);
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
