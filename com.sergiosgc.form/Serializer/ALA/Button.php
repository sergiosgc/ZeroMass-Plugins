<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_Button is an input serializer to xhtml that produces code according to ALA's accessible forms article
 * for a description of the structure
 */
class Serializer_ALA_Button
{
    public static function serialize(Serializer_ALA $parentSerializer, Input $input)
    {
        $label = is_null($input->getLabel()) ? 'Submit' : $input->getLabel();

        return sprintf(<<<EOS
<button type="%s" value="%s">%s</button>

EOS
        , $input->getType()
        , Serializer_ALA::entitize($input->getValue())
        , Serializer_ALA::entitize($input->getLabel()));
    }
}
?>
