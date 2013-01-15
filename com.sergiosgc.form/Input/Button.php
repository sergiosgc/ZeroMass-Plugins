<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form button input. 
 */
class Input_Button extends Input
{
    protected $primary = false;
    protected $type = 'submit';

    /* constructor {{{ */
    public function __construct($name, $value = null)
    {
        parent::__construct($name, $value, false, null);
    }
    /* }}} */
    /* __toString {{{ */
    public function __toString()
    {
        return sprintf("Button input '%s'. Value: %s", $this->name, is_null($this->value) ? '<NULL>' : ('\'' . $this->value . '\''));
    }
    /* }}} */
    public function isPrimary() {/*{{{*/
        return $this->primary;
    }/*}}}*/
    public function setPrimary($to = true) {/*{{{*/
        $this->primary = to;
    }/*}}}*/
    public function setType($type) {/*{{{*/
        switch ($type) {
        case 'button':
        case 'submit':
        case 'reset':
            $this->type = $type;
            break;
        default: 
            $this->type = 'submit';
            break;
        }
    }/*}}}*/
    public function getType() {/*{{{*/
        return $this->type;
    }/*}}}*/
}
?>
