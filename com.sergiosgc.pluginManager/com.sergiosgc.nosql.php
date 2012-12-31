<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;


/** 
 * Backup implementation of a key-value store, using the cache API
 */
class Nosql {
    protected static $singleton = null;
    protected function __construct() {/*{{{*/
        Nosql::$singleton = $this;
    }/*}}}*/
    public static function getInstance() {/*{{{*/
        if (is_null(Nosql::$singleton)) Nosql::$singleton = new Nosql();
        return Nosql::$singleton;
    }/*}}}*/
    public function set($key, $value) {/*{{{*/
        \ZeroMass::getInstance()->getAPI('cache')->set($key, $value);
    }/*}}}*/
    public function get($key) {/*{{{*/
        return \ZeroMass::getInstance()->getAPI('cache')->get($key);
    }/*}}}*/
}
?>
