<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_MultipleChoice is an multiple-choice input serializer to xhtml that produces code according to Table's accessible forms article
 * for a description of the structure. It delegates seralization to the relevant class in order to produce widgets appropriate to the selectable universe size and 
 * to the ability for selecting more than one choice.
 */
class Serializer_Table_MultipleChoice
{
    public static function serialize(Serializer_Table $parentSerializer, Input_MultipleChoice $choice)
    {
        if (count($choice->getChoices()) > 3) {
            if (count($choice->getRestrictionsByClass('Restriction_ClosedChoice')) > 0) {
                return Serializer_Table_DropdownList::serialize($parentSerializer, $choice);
            } else {
                return Serializer_Table_ComboList::serialize($parentSerializer, $choice);
            }
        } else {
            if (count($choice->getRestrictionsByClass('Restriction_ExclusiveChoice')) > 0) {
                return Serializer_Table_RadioButton::serialize($parentSerializer, $choice);
            } else {
                return Serializer_Table_Checkbox::serialize($parentSerializer, $choice);
            }
        }
    }
}
?>
