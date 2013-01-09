<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form text input. 
 */
class Input_Text extends Input
{
    public $multiline;
    public $mime;
    /* constructor {{{ */
    public function __construct($name, $value = null, $multiline = false, $mime = null)
    {
        parent::__construct($name, $value);
        $this->multiline = $multiline;
        $this->mime = $mime;
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Text input '%s'. %s. %sValue: %s", $this->name, $this->multiline ? 'Multiline' : 'Single-line', is_null($this->mime) ? '' : sprintf('Content mime-type: %s.', $this->mime), is_null($this->value) ? '<NULL>' : ('\'' . $this->value . '\''));
    }
    /* }}} */
    /* customInputAdapter {{{ */
    public static function customInputAdapter($field)
    {
        return new Input_Text($field['name']);
    }
    /* }}} */
}
?>
