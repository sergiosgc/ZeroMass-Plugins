<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_DropdownList is the ALA serializer that produces dropdown list inputs
 */
class Serializer_ALA_DropdownList
{
    public static function serialize(Serializer_ALA $parentSerializer, Input_MultipleChoice $choice)
    {
        $multiple = (count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0);
        $result = '';
        foreach($choice->getChoices() as $value => $label) $result .= sprintf(<<<EOS
 <option value="%s"%s>%s</option>

EOS
            , $value, 
            (($multiple && is_array($choice->getValue()) && in_array($value, $choice->getValue())) ||
             (!$multiple && $value == $choice->getValue())) ? ' selected="selected"' : '', 
            $label);
        if ($choice->getLabel() != '') {
            $result = sprintf(<<<EOS
<label for="%s">%s</label>
<select name="%s" id="%s"%s>
%s</select>

EOS
                , $choice->getName(), $choice->getLabel()
                , $choice->getName(), $choice->getName(), count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result);
        } else {
            $result = sprintf(<<<EOS
<select name="%s" id="%s"%s>
%s</select>

EOS
                , $choice->getName(), $choice->getName(), count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result);
        }
        return $result;
    }
}
?>
