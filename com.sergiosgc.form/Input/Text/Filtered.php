<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form text input accepting some characters only. 
 */
class Input_Text_Filtered extends Input_Text
{
    public $acceptedChars;
    /* constructor {{{ */
    public function __construct($name, $value = null, $acceptedChars = null, $multiline = false, $mime = null)
    {
        parent::__construct($name, $value, $multiline, $mime);
        $this->acceptedChars = (string) $acceptedChars;
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Filtered Text input '%s'. Accepts '%s', %s. %sValue: %s", $this->name, $this->acceptedChars, $this->multiline ? 'Multiline' : 'Single-line', is_null($this->mime) ? '' : sprintf('Content mime-type: %s.', $this->mime), is_null($this->value) ? '<NULL>' : ('\'' . $this->value . '\''));
    }
    /* }}} */
}
?>
