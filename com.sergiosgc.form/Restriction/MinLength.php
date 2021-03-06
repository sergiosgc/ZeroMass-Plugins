<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Sets the minimum length of a field in characters
 */
class Restriction_MinLength extends Restriction
{
    protected $length;
    public function setLength($length)
    {
        $this->length = $length;
    }
    public function getLength()
    {
        return $this->length;
    }
    public function __construct($minLength)
    {
        $this->setLength($minLength);
    }
    public function validate() {/*{{{*/
        if (strlen($this->target->getValue()) < $this->length) return sprintf('Value must be at least %d characters long', $this->length+1);
        return true;
    }/*}}}*/
}
?>
