<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\config\combined;

class Config {
    protected static $singleton = null;
    protected $config = null;
    protected $readOnly = null;
    protected $readWrite = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.collision', array($this, 'init'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init($providers, $tag) {/*{{{*/
        if ($tag != 'config') return $providers;
        $readOnly = null;
        $readWrite = null;
        if ($providers[0]->isWritable()) $readWrite = $providers[0]; else $readOnly = $providers[0];
        if ($providers[1]->isWritable()) $readWrite = $providers[1]; else $readOnly = $providers[1];
        if (is_null($readOnly) || is_null($readWrite)) return $providers;

        $this->readOnly = $readOnly;
        $this->readWrite = $readWrite;
        return $this;
    }/*}}}*/
    /**
     * Get a configuration value
     *
     * @param string key Configuration key
     * @param boolean Whether to throw an exception if configuration key not found. Optional, defaults to true
     * @param mixed Default value, should an exception not be thrown when configuration key not found. Optional, defaults to null
     */
    public function get($key, $exceptionIfNotFound = true, $default = null) {/*{{{*/
        try {
            return $this->readOnly->get($key, true);
        } catch (\Exception $e) {
            return $this->readWrite->get($key, $exceptionIfNotFound, $default);
        }
    }/*}}}*/
    /**
     * Get subkeys immediately below a given key
     *
     * @param string key Configuration key
     * @param boolean Whether to throw an exception if configuration key not found. Optional, defaults to true
     * @param mixed Default value, should an exception not be thrown when configuration key not found. Optional, defaults to null
     */
    public function getKeys($key, $exceptionIfNotFound = true, $default = null) {/*{{{*/
        $result = $this->get($key, $exceptionIfNotFound, $default);
        if (!is_array($result) && $exceptionIfNotFound) throw new KeyNotFoundException(sprintf('Configuration under %s has no subkeys', $key));
        if (!is_array($result)) return $default;
        $result = array_keys($result);
        return $result;
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
        try {
            $this->readOnly->get($key, true);
        } catch (\Exception $e) {
            return $this->readWrite->set($key, $value);
        }
        throw new ConfigException(sprintf('Trying to set %s key, which is stored in read-only config driver', $key));
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

Config::getInstance();

/*#
 * Configuration driver that aggregates one read-only provider and a read/write provider
 *
 * Configuration facility provider that delegates configuration onto a pair of config providers: one read-only and one read/write
 *
 * # Usage summary 
 *
 * This is a webapp configuration provider, that combines a read-only config provider and a read-write config provider
 * allowing for static file based config providers to coexist with database backed config providers. `get` operations 
 * give higher priority to the static providers, falling back to the database provider on keys that do not exist on the 
 * static provider. 
 *
 * This plugin aims to solve the chicken-and-egg problem of initializing the database configuration provider, which needs to
 * retrieve its own database configuration from somewhere.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
