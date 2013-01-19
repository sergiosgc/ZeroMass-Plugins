<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Marks a text field as being single line
 */
class Restriction_SingleLine extends Restriction
{
    public function validate() {/*{{{*/
        if (strpos($this->target->getValue(), "\n") !== FALSE) return sprintf('Text must not contain line breaks');
        return true;
    }/*}}}*/
}
?>
