<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form multiple choice input. 
 */
class Input_Hidden extends Input
{
    /* constructor {{{ */
    public function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Hidden input '%s'. Value '%s'.", $this->name, $this->value);
    }
    /* }}} */
}
?>
