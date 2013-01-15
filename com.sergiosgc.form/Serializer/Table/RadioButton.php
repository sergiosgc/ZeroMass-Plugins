<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_RadioButton is the Table serializer that produces radio button inputs
 */
class Serializer_Table_RadioButton
{
    public static $otherTranslation = null;
    public static function serialize(Serializer_Table $parentSerializer, Input_MultipleChoice $choice)
    {
        $result = '';
        foreach($choice->getChoices() as $value => $label) $result .= sprintf(<<<EOS
 <label class="radiobutton"><input type="radio" name="%s" value="%s"%s> %s</label>

EOS
            , $choice->getName(), $value, 
            $choice->getValue() == $value ? ' checked="checked"' : '',
            $label);
        if (count($choice->getRestrictionsByClass('Restriction_ClosedChoice')) == 0) {
            if (is_null(self::$otherTranslation)) self::$otherTranslation = _('Other:');
            $result .= sprintf(<<<EOS
 <label for="%s-open" class="radiobutton-open">%s</label>
 <input type="text" name="%s-open" id="%s-open" />

EOS
                , $choice->getName(), self::$otherTranslation, $choice->getName(), $choice->getName());
        }
        $result = sprintf(<<<EOS
<fieldset>
%s</fieldset>

EOS
            , $result);
        return Serializer_Table_Table::serialize($choice, $result);
    }
}
?>
