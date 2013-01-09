<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Test is a test serializer. It just outputs the form's own cast to string (__toString)
 */
class Serializer_Test
{
    public function serialize(Form $form)
    {
        return $form->__toString();
    }
}
?>
