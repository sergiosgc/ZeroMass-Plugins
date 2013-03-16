<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_Hidden is an input serializer to xhtml that produces code according to TwitterBootstrap's accessible forms article
 * for a description of the structure
 */
class Serializer_TwitterBootstrap_Hidden
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input $input)
    {
        return sprintf(<<<EOS
  <input type="hidden" id="%s" name="%s" value="%s">
EOS
        , Serializer_TwitterBootstrap::entitize($input->name)
        , Serializer_TwitterBootstrap::entitize($input->name)
        , Serializer_TwitterBootstrap::entitize($input->value)
        );
    }
}
?>
