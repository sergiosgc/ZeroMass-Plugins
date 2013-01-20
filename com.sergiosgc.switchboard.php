<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;
/*#
 * Switchboard
 */
class Switchboard {
    const TARGET_TYPE_URI = 1;
    const TARGET_TYPE_URIREGEX = 2;

    private $handlers = array();
    private $notFoundHandler = null;

    public function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'), 1);
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'answerPage'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'throw404Exception'), 50);
    }/*}}}*/
    public function init() {/*{{{*/
        Facility::register('switchboard', $this);
    }/*}}}*/
    public function addHandler($callable, $target, $type = self::TARGET_TYPE_URI) {/*{{{*/
        if (!is_callable($callable)) throw new \ZeroMassException('Callable parameter is not a PHP callable');

        if (is_string($target)) {
            switch ($type) {
            case self::TARGET_TYPE_URI: 
                $target = new HandlerTargetUri($target);
                break;
            case self::TARGET_TYPE_URIREGEX: 
                $target = new HandlerTargetRegex($target);
                break;
            }
        }
        if (!is_object($target) || !method_exists($target, 'matches')) throw new \ZeroMassException('Target parameter is not a switchboard handler target');

        $this->handlers[] = array('target' => $target, 'callable' => $callable);
    }/*}}}*/
    public function setNotFoundHandler($callable) {/*{{{*/
        if (!is_callable($callable) && !is_null($callable)) throw new \ZeroMassException('Callable parameter is not a PHP callable');

        $this->notFoundHandler = $callable;
    }/*}}}*/
    public function answerPage($handled) {/*{{{*/
        if ($handled) return $handled;
        return $this->handleCurrentRequest();
    }/*}}}*/
    protected function handleCurrentRequest($exceptionIfNotFound = false) {/*{{{*/
        foreach ($this->handlers as $handler) {
            if ($handler['target']->matches()) {
                if (call_user_func($handler['callable'])) return true;
            }
        }
        if (!is_null($this->notFoundHandler)) {
            call_user_func($this->notFoundHandler);
            return true;
        } else {
            if ($exceptionIfNotFound) throw new \ZeroMassNotFoundException($_SERVER['REQUEST_URI'] . ' not found');
            return false;
        }
    }/*}}}*/
    public function throw404Exception($handled) {/*{{{*/
        if ($handled) return $handled;
        throw new \ZeroMassNotFoundException($_SERVER['REQUEST_URI'] . ' not found');
    }/*}}}*/

}
class HandlerTargetUri {
    public function __construct($uri) {/*{{{*/
        $this->uri = $uri;
    }/*}}}*/
    public function matches($uri = null) {/*{{{*/
        if (is_null($uri)) $uri = $_SERVER['REQUEST_URI'];
        return $uri == $this->uri;
    }/*}}}*/
}
class HandlerTargetRegex {
    public function __construct($regex) {/*{{{*/
        if (preg_match($regex, '') === FALSE) throw new \ZeroMassException('Invalid regex supplied: ' . $regex);
        $this->regex = $regex;
    }/*}}}*/
    public function matches($uri = null) {/*{{{*/
        if (is_null($uri)) $uri = $_SERVER['REQUEST_URI'];
        if (preg_match($this->regex, $uri)) return true;

        return false;
    }/*}}}*/
}
new Switchboard();
?>
