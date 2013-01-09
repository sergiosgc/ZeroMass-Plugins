<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form input. It contains at least a name for the represented field and a value
 *
 * An input may also contain a set of field-level restrictions
 */
abstract class Input extends Member
{
    /* help field {{{ */
    public $help;
    public function getHelp()
    {
        return $this->help;
    }
    public function setHelp($val)
    {
        $this->help = $val;
    }
    /* }}} */
    /* error field {{{ */
    public $error;
    public function getError()
    {
        return $this->error;
    }
    public function setError($val)
    {
        $this->error = $val;
    }
    /* }}} */
    /* value field {{{ */
    public $value;
    public function getValue()
    {
        return $this->value;
    }
    public function setValue($val)
    {
        $this->value = $val;
    }
    /* }}} */
    /* constructor {{{ */
    public function __construct($name, $value = null)
    {
        parent::__construct($name);
        $this->value = $value;
    }
    /* }}} */
    /* restrictions field {{{ */
    protected $restrictions = array();
    public function getRestriction($index) 
    {   
        return $this->restrictions[$index];
    }
    public function getRestrictions()
    {
        return $this->restrictions;
    }
    public function getRestrictionsByClass($class)
    {
        $result = array();
        foreach ($this->restrictions as $restriction) if ($restriction instanceof $class) $result[] = $restriction;
        return $result;
    }
    public function hasRestrictionByClass($class)
    {
        $result = array();
        foreach ($this->restrictions as $restriction) if ($restriction instanceof $class) return true;
        return false;
    }
    public function addRestriction(Restriction $restriction)
    {
        $restriction->setTarget($this);
        $this->restrictions[] = $restriction;
    }
    public function removeRestriction($index)
    {
        unset($this->restrictions[$index]);
        $this->restrictions = array_values($this->restrictions);
    }
    /* }}} */
}
?>