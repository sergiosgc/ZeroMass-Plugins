<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\form;
/** 
 * Restricts a field to fullfilling a regexp
 */
class Restriction_Regexp extends Restriction
{
    public function __construct($regexp, $failMessage) {
        if (false === preg_match($regexp, '')) {
            throw new Exception('Failed regexp validation for: ' . $regexp);
        }
        $this->regexp = $regexp;
        $this->failMessage = $failMessage;
    }
    public function validate() {/*{{{*/
        if (0 === preg_match($this->regexp, $this->target->getValue())) {
            return $this->failMessage;
        }
        return true;
    }/*}}}*/
}
?>
