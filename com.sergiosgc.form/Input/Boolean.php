<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form multiple choice input. 
 */
class Input_Boolean extends Input_MultipleChoice
{
    /* constructor {{{ */
    public function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
        $this->addChoice(0, _('No'));
        $this->addChoice(1, _('Yes'));
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Boolean input '%s'. Value '%s'.", $this->name, $this->value);
    }
    /* }}} */
}
?>
