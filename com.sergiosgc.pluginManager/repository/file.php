<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass\repository;

class File extends \com\sergiosgc\zeromass\Repository {
    public function __construct($url) {/*{{{*/
        if (!preg_match('_^file://_', $url)) throw new \ZeroMassException('Invalid URL');
        $this->directory = preg_replace('_^file://_', '', $url);
    }/*}}}*/
    public function createPlugin($plugin) {/*{{{*/
        require_once(dirname(__FILE__) . '/../com.sergiosgc.plugin.php');

        $candidates = array(
            sprintf('%s/%s.php', $this->directory, $plugin),
            sprintf('%s/%s/%s.php', $this->directory, $plugin, $plugin));

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                return new \com\sergiosgc\zeromass\Plugin($candidate);
            }
        }
        throw new \ZeroMassException(sprintf('Plugin %s not found in %s', $plugin, $this->directory));
    }/*}}}*/
    public function containsPlugin($plugin) {/*{{{*/
        $candidates = array(
            sprintf('%s/%s.php', $this->directory, $plugin),
            sprintf('%s/%s/%s.php', $this->directory, $plugin, $plugin));

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                return true;
            }
        }
        return false;
    }/*}}}*/
}
?>
