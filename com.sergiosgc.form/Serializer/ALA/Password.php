<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_Password is an input serializer to xhtml that produces code according to ALA's accessible forms article
 * for a description of the structure
 */
class Serializer_ALA_Password
{
    public static function serialize(Serializer_ALA $parentSerializer, Input $input)
    {
        return sprintf(<<<EOS
<label for="%s">%s</label>
<input id="%s" name="%s" value="%s" type="password" />

EOS
        , Serializer_ALA::entitize($input->name)
        , $input->getLabel()
        , Serializer_ALA::entitize($input->name)
        , Serializer_ALA::entitize($input->name)
        , Serializer_ALA::entitize($input->value));
    }
}
?>
