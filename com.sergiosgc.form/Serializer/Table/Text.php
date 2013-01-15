<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_Text picks a suitable serializer for a text input
 */
class Serializer_Table_Text
{
    public static function serialize(Serializer_Table $parentSerializer, Input_Text $text)
    {
        if ($text->hasRestrictionByClass('Restriction_SingleLine')) {
            return Serializer_Table_Input::serialize($parentSerializer, $text);
        } elseif ($text->hasRestrictionByClass('Restriction_HTMLContent')) {
            return Serializer_Table_HTMLEditor::serialize($parentSerializer, $text);
        } else {
            $max = $text->getRestrictionsByClass('Restriction_MaxLength');
            if (count($max) == 0) {
                $max = null;
            } else {
                $max = $max[0]->getLength();
            }
            if ($max > 60) {
                return Serializer_Table_TextArea::serialize($parentSerializer, $text);
            } else {
                return Serializer_Table_Input::serialize($parentSerializer, $text);
            }
        }
    }
}
?>
