<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;

class Repository {
    public static function createFromURL($url) {/*{{{*/
        if (!preg_match('_([^a-zA-Z]*):.*_', $url)) throw new \ZeroMassException('Invalid repository URL');

        $transport = preg_replace('_([^a-zA-Z]*):.*_', '\1', $url);
        require_once(dirname(__FILE__) . '/repository/' . $transport . '.php');
        $transport = strtoupper(substr($transport, 0, 1)) . substr($transport, 1);
        $className = '\\com\\sergiosgc\\zeromass\\repository\\'  . $transport;

        return new $className($url);
    }/*}}}*/
    public function isInSync() {/*{{{*/
        return true;
    }/*}}}*/
    public function requiresAuthentication() {/*{{{*/
        return false;
    }/*}}}*/
    public function sync() {/*{{{*/
        return;
    }/*}}}*/
}
?>
