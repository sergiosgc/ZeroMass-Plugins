<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_MultipleChoice is an multiple-choice input serializer to xhtml that produces code according to TwitterBootstrap's accessible forms article
 * for a description of the structure. It delegates seralization to the relevant class in order to produce widgets appropriate to the selectable universe size and 
 * to the ability for selecting more than one choice.
 */
class Serializer_TwitterBootstrap_MultipleChoice
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input_MultipleChoice $choice)
    {
        if (count($choice->getChoices()) > 3) {
            if (count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ClosedChoice')) > 0) {
                return Serializer_TwitterBootstrap_DropdownList::serialize($parentSerializer, $choice);
            } else {
                return Serializer_TwitterBootstrap_ComboList::serialize($parentSerializer, $choice);
            }
        } else {
            if (count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0) {
                return Serializer_TwitterBootstrap_RadioButton::serialize($parentSerializer, $choice);
            } else {
                return Serializer_TwitterBootstrap_Checkbox::serialize($parentSerializer, $choice);
            }
        }
    }
}
?>
