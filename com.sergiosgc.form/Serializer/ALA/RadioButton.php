<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_RadioButton is the ALA serializer that produces radio button inputs
 */
class Serializer_ALA_RadioButton
{
    public static $otherTranslation = null;
    public static function serialize(Serializer_ALA $parentSerializer, Input_MultipleChoice $choice)
    {
        $result = '';
        foreach($choice->getChoices() as $value => $label) $result .= sprintf(<<<EOS
 <label><input type="radio" name="%s" value="%s"%s> %s</label>

EOS
            , $choice->getName(), $value, 
            $choice->getValue() == $value ? ' checked="checked"' : '',
            $label);
        if (count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ClosedChoice')) == 0) {
            if (is_null(self::$otherTranslation)) self::$otherTranslation = _('Other:');
            $result .= sprintf(<<<EOS
 <label for="%s-open">%s</label>
 <input type="text" name="%s-open" id="%s-open" />

EOS
                , $choice->getName(), self::$otherTranslation, $choice->getName(), $choice->getName());
        }
        if ($choice->getLabel() != '') {
            $result = sprintf(<<<EOS
<fieldset>
 <legend>%s</legend>
%s</fieldset>

EOS
                , $choice->getLabel(), $result);
        } else {
            $result = sprintf(<<<EOS
<fieldset>
%s</fieldset>

EOS
                , $result);
        }
        return $result;
    }
}
?>
