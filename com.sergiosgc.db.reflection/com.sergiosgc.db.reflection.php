<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\db;

class Reflection {
    protected $db = null;
    public static function create(\com\sergiosgc\DB $db) {/*{{{*/
        $driver = $db->getDriver();
        require_once(dirname(__FILE__) . '/' . $driver . '.php');
        $className = '\com\sergiosgc\db\reflection\\' . $driver;
        return new $className($db);
    }/*}}}*/
}

/*#
 * Database reflection plugin
 *
 * An API for database reflection, on top of the DB plugin
 *
 * # Usage summary 
 *
 *
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
