<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_Textarea produces a text input serialization as a textarea
 * for a description of the structure
 */
class Serializer_Table_Textarea
{
    public static function serialize(Serializer_Table $parentSerializer, Input_Text $input)
    {
        return Serializer_Table_Table::serialize($input, sprintf(<<<EOS
<textarea id="%s" name="%s">%s</textarea>

EOS
        , $input->name, $input->name, $input->value));
    }
}
?>
