<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Page {
    protected static $singleton = null;
    protected $templatePath = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'), 5);
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.replaced_config', array($this, 'config'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        $this->templatePath = \ZeroMass::getInstance()->privateDir . '/pageTemplates';
        \com\sergiosgc\Facility::register('page', $this);
    }/*}}}*/
    public function config() {/*{{{*/
        $this->templatePath = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.page.templatepath', false, $this->templatePath);
        /*#
         * Filter the templatePath. 
         *
         * @param string The full path to the template directory
         * @return string The full path to the template directory
         */
        $this->templatePath = \ZeroMass::getInstance()->do_callback('com.sergiosgc.page.templatepath', $this->templatePath);
    }/*}}}*/
}

class PageException extends \Exception { }

Page::getInstance();

/*#
 * Simplistic page template
 *
 * Very simple templating system for creating an HTML page out of the core 
 * content (supposedly produced by other plugins)
 *
 * # Usage summary 
 *
 * Drop this plugin on your plugin directory and create a directory named pageTemplates under
 * the webapp private directory (usually, <document_root>/private)
 *
 * Page templates are named after the page type, with extension .template. For example
 *
 *     default.template
 *
 * Page templates are just regular HTML, with placeholders for components
 *
 * The ini file is, by default, looked for in private/config.ini. You may change the location
 * by hooking up to the `com.sergiosgc.config.ini.path` hook.
 *
 * The file is parsed using [parse_ini_file](http://php.net/manual/en/function.parse-ini-file.php)
 * so it follows the php.ini conventions.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
