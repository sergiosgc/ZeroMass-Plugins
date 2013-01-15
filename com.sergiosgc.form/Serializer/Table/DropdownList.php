<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_DropdownList is the Table serializer that produces dropdown list inputs
 */
class Serializer_Table_DropdownList
{
    public static function serialize(Serializer_Table $parentSerializer, Input_MultipleChoice $choice)
    {
        $multiple = (count($choice->getRestrictionsByClass('Restriction_ExclusiveChoice')) > 0);
        $result = '';
        foreach($choice->getChoices() as $value => $label) $result .= sprintf(<<<EOS
 <option value="%s"%s>%s</option>

EOS
            , $value, 
            (($multiple && is_array($choice->getValue()) && in_array($value, $choice->getValue())) ||
             (!$multiple && $value == $choice->getValue())) ? ' selected="selected"' : '', 
            $label);
        $result = sprintf(<<<EOS
<select name="%s" id="%s"%s>
%s</select>

EOS
            , $choice->getName(), $choice->getName(), count($choice->getRestrictionsByClass('Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
            , $result);
        return Serializer_Table_Table::serialize($choice, $result);
    }
}
?>
