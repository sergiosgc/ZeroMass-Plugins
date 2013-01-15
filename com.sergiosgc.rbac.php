<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Rbac {
    protected static $singleton = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
    }/*}}}*/
}

Rbac::getInstance();

/*#
 * Role Based Access Control
 *
 * Building on top of user authentication, provides role based access control
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
