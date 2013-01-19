<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\form;
/** 
 * Marks a field as being numeric integer.
 */
class Restriction_Integer extends Restriction
{
    public function validate() {/*{{{*/
        if ( ( (string) ( (int) $this->target->getValue() ) ) != $this->target->getValue() ) {
            return 'Value must be an integer';
        }
        return true;
    }/*}}}*/
}
?>
