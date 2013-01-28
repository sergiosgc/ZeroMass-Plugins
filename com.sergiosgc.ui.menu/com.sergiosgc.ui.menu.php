<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\ui\menu;

interface MenuSerializer {
    public function serialize(Menu $menu);
}

class Menu extends MenuItem {
    protected $items = array();
    protected static $registeredAutoload = false;
    public static function autoloader($class) {/*{{{*/
        if (strlen($class) < strlen(__NAMESPACE__) || __NAMESPACE__ != substr($class, 0, strlen(__NAMESPACE__))) return;
        $class = substr($class, strlen(__NAMESPACE__) + 1);
        $path = dirname(__FILE__) . '/' . strtr($class, array('_' => '/')) . '.php';

        require_once($path);
    }/*}}}*/
    public static function registerAutoloader() {/*{{{*/
        if (!self::$registeredAutoload) {
            spl_autoload_register(array(__CLASS__, 'autoloader'));
            self::$registeredAutoload = true;
        }
    }/*}}}*/
    public function __construct() {/*{{{*/
        self::registerAutoloader();
    }/*}}}*/
    public function getItems() {/*{{{*/
        return $this->items;
    }/*}}}*/
    public function setItems($items) {/*{{{*/
        $this->items = $items;
    }/*}}}*/
    public function addItem($item) {/*{{{*/
        $this->items[] = $item;
    }/*}}}*/
    protected function itemIndex($item) {/*{{{*/
        return array_search($item, $this->items);
    }/*}}}*/
    public function hasItem($item) {/*{{{*/
        return in_array($item, $this->items);
    }/*}}}*/
    public function removeItem($item) {/*{{{*/
        if (!$this->hasItem($item)) return false;
        unset($this->items[$this->itemIndex($item)]);
        $this->items = array_values($this->items);
        return true;
    }/*}}}*/
    public function output($serializer = null) {/*{{{*/
        if (is_null($serializer)) $serializer = new BootstrapSerializer();
        if (is_string($serializer)) $serializer = new $serializer();
        $serializer->serialize($this);
    }/*}}}*/
}

class Leaf extends MenuItem {
    public function __construct($label, $href) {/*{{{*/
        $this->setLabel($label);
        $this->setHref($href);
    }/*}}}*/
}

abstract class MenuItem { 
    protected $label;
    protected $href;
    protected $active = false;
    protected $open = false;
    public function setActive($to = true) {/*{{{*/
        $this->active = $to;
    }/*}}}*/
    public function getActive() {/*{{{*/
        return $this->active;
    }/*}}}*/
    public function setOpen($to = true) {/*{{{*/
        $this->open = $to;
    }/*}}}*/
    public function getOpen() {/*{{{*/
        return $this->open;
    }/*}}}*/
    public function setLabel($label) {/*{{{*/
        $this->label = $label;
    }/*}}}*/
    public function getLabel() {/*{{{*/
        return $this->label;
    }/*}}}*/
    public function setHref($href) {/*{{{*/
        $this->href = $href;
    }/*}}}*/
    public function getHref() {/*{{{*/
        return $this->href;
    }/*}}}*/


}
class Exception extends \Exception { }

Menu::registerAutoloader();

/*#
 * Single line
 *
 * Longer desc
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
