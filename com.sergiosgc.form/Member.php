<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form member. Either a form input or an input set.
 */
abstract class Member
{
    public $name;
    public function getName()
    {
        return $this->name;
    }
    /* constructor {{{ */
    public function __construct($name)
    {
        $this->name = (string) $name;
    }
    /* }}} */
    /* form field {{{ */
    protected $form;
    public function setForm(Form $form)
    {
        $this->form = $form;
    }
    public function getForm()
    {
        return $this->form;
    }
    /* }}} */
    /* label field {{{ */
    protected $label = null;
    public function setLabel($label)
    {
        $this->label = $label;
    }
    public function getLabel()
    {
        return $this->label;
    }
    /* }}} */
}
?>
