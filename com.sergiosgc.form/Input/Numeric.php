<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form number input. 
 */
class Input_Numeric extends Input
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
        $restrictions = '';
        foreach ($this->restrictions as $restriction) $restrictions .= '-' . $restriction->__toString() . "\n";
        return sprintf("Numeric input '%s'. Value: %s. Restrictions: \n%s", $this->name, is_null($this->value) ? '<NULL>' : ('\'' . $this->value . '\''), Form::indent($restrictions));
    }
    /* }}} */
}
?>
