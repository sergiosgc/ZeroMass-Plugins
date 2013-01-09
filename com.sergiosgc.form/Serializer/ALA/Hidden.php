<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_Input is an input serializer to xhtml that produces code according to ALA's accessible forms article
 * for a description of the structure
 */
class Serializer_ALA_Hidden
{
    public static function serialize(Serializer_ALA $parentSerializer, Input $input)
    {
        return sprintf(<<<EOS
<input type="hidden" id="%s" name="%s" value="%s" />

EOS
        , $input->name, $input->getLabel(), $input->name, $input->name, $input->value);
    }
}
?>
