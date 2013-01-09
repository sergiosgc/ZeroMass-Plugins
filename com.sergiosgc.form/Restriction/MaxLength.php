<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Sets the maximum length of a field in characters
 */
class Restriction_MaxLength extends Restriction
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
    public function __construct($maxLength)
    {
        $this->setLength($maxLength);
    }
}
?>
