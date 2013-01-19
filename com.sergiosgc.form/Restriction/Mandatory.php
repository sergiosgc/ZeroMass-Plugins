<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Marks a field as being mandatory. No empty strings nor null values allowed
 */
class Restriction_Mandatory extends Restriction
{
    public function validate() {/*{{{*/
        if (strlen($this->getTarget()->getValue()) == 0) return 'Field is mandatory';
        return true;
    }/*}}}*/
}
?>
