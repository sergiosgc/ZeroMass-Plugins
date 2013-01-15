<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_Input is an input serializer to xhtml that produces code according to Table's accessible forms article
 * for a description of the structure
 */
class Serializer_Table_Hidden
{
    public static function serialize(Serializer_Table $parentSerializer, Input $input)
    {
        return Serializer_Table_Table::serialize($input, sprintf(<<<EOS
<input type="hidden" id="%s" name="%s" value="%s" class="hidden" />

EOS
        , $input->name, $input->name, $input->value));
    }
}
?>
