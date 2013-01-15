<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form password input. 
 */
class Input_Password extends Input
{
    /* constructor {{{ */
    public function __construct($name, $value = null)
    {
        parent::__construct($name, $value, false, null);
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Password input '%s'. Value: %s", $this->name, is_null($this->value) ? '<NULL>' : ('\'' . $this->value . '\''));
    }
    /* }}} */
}
?>
