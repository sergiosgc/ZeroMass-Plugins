<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form multiple choice input. 
 */
class Input_MultipleChoice extends Input
{
    /* choices field {{{ */
    private $choices = array();
    public function addChoice($key, $value = null)
    {
        if (is_null($value)) $value = $key;
        $this->choices[$key] = $value;
    }
    public function getChoices()
    {
        return $this->choices;
    }
    public function setChoices($choices)
    {
        $this->choices = $choices;
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        $result = sprintf("Multiple choice input '%s'. Value '%s'. Choices:", $this->name, is_array($this->value) ? $this->value[0] : $this->value);
        foreach ($this->choices as $key => $value) {
            $result .= sprintf("\n-- '%s' => '%s'", $key, $value);
        }
        return $result;
    }
    /* }}} */
}
?>
