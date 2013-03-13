<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_DropdownList is the TwitterBootstrap serializer that produces dropdown list inputs
 */
class Serializer_TwitterBootstrap_DropdownList
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input_MultipleChoice $choice)
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
        if ($parentSerializer->getLayout() == 'horizontal') {
            if ($choice->getLabel() != '') {
                $result = sprintf(<<<EOS
<div class="%s">
<label class="control-label" for="%s">%s</label>
<div class="controls">
<select name="%s"%s>%s</select>
%s        
</div>      
</div>      

EOS
                , is_null($choice->error) ? 'control-group' : 'control-group error'
                , Serializer_TwitterBootstrap::entitize($choice->name)
                , $choice->getLabel()
                , $choice->getName()
                , count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result
                , Serializer_TwitterBootstrap::helpBlock($choice->help, $choice->error)
                );
            } else {
                $result = sprintf(<<<EOS
<div class="%s">
<div class="controls">
<select name="%s"%s>%s</select>
%s        
</div>      
</div>      

EOS
                , is_null($choice->error) ? 'control-group' : 'control-group error'
                , $choice->getName()
                , count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result
                , Serializer_TwitterBootstrap::helpBlock($choice->help, $choice->error)
                );
            }
        } else {
            if ($choice->getLabel() != '') {
                $result = sprintf(<<<EOS
<label class="control-label" for="%s">%s</label>
<select name="%s"%s>%s</select>
%s        

EOS
                , Serializer_TwitterBootstrap::entitize($choice->name)
                , $choice->getLabel()
                , $choice->getName()
                , count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result
                , Serializer_TwitterBootstrap::helpBlock($choice->help, $choice->error)
                );
            } else {
                $result = sprintf(<<<EOS
<select name="%s"%s>%s</select>
%s        

EOS
                , $choice->getName()
                , count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ExclusiveChoice')) > 0 ? '' : ' multiple="multiple"'
                , $result
                , Serializer_TwitterBootstrap::helpBlock($choice->help, $choice->error)
                );
            }
        }
        return $result;
    }
}
?>
