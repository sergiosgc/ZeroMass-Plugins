<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\config\db;

class Config {
    protected static $singleton = null;
    protected $config = null;
    protected $recursionSemaphore = false;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_db', array($this, 'init'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('config', $this);
    }/*}}}*/
    /**
     * Get a configuration value
     *
     * @param string key Configuration key
     * @param boolean Whether to throw an exception if configuration key not found. Optional, defaults to true
     * @param mixed Default value, should an exception not be thrown when configuration key not found. Optional, defaults to null
     */
    public function get($key, $exceptionIfNotFound = true, $default = null) {/*{{{*/
        if ($this->recursionSemaphore) {
            if ($exceptionIfNotFound) throw new KeyNotFoundException(sprintf('Did not find configuration key %s', $key));
            return $default;
        } else {
            $this->recursionSemaphore = true;
            $result = \com\sergiosgc\Facility::get('db')->fetchValue('SELECT value FROM config WHERE ckey = ?', $key);
            $this->recursionSemaphore = false;
            return $result;
        }
    }/*}}}*/
    /**
     * Set a configuration key
     *
     * This plugin is read-only, so this method throws a ConfigException if called
     *
     * @param string Configuration key
     * @param string Configuration value
     */
    public function set($key, $value) {/*{{{*/
        throw new ConfigException('\com\sergiosgc\config\ini\Config is read only');
    }/*}}}*/
    /**
     * Report this plugin as read/write
     * @return boolean true, stating this config driver is read/write
     */
    public function isWritable() {/*{{{*/
        return true;
    }/*}}}*/
}

class ConfigException extends \Exception { }
class KeyNotFoundException extends ConfigException { }

Config::getInstance();

/*#
 * Configuration driver using a database
 *
 * Configuration facility provider that reads its configuration from a database
 *
 * # Usage summary 
 *
 * This is a webapp configuration provider, that reads its configuration from a database and writes
 * configuration to a database.
 *
 * It expects the database to contain a `config` table, with two fields: `ckey` and `value`, both strings
 * with a primary key on `ckey`.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
