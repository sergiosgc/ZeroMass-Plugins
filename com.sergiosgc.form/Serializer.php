<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A Structures_Form serializer is a class that can output a form as a string
 */
interface Serializer
{
    /**
     * Serialize the form into a string. xhtml output is an option, as is xforms. Specific output language is dependent on the implementation
     */
    public function serialize(Structures_Form $form);
}
?>
