<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_Checkbox is the Table serializer that produces checkbox inputs
 */
class Serializer_Table_Checkbox
{
    public static $otherTranslation = null;
    public static function serialize(Serializer_Table $parentSerializer, Input_MultipleChoice $choice)
    {
        $result = '';
        foreach($choice->getChoices() as $value => $label) $result .= sprintf(<<<EOS
 <label class="checkbox"><input type="checkbox" name="%s" value="%s"%s> %s</label>

EOS
            , $choice->getName(), $value, 
            (is_array($choice->getValue()) && in_array($value, $choice->getValue())) ? ' checked="checked"' : '',
            $label);
        if (count($choice->getRestrictionsByClass('Restriction_ClosedChoice')) == 0) {
            if (is_null(self::$otherTranslation)) self::$otherTranslation = _('Other:');
            $result .= sprintf(<<<EOS
 <label for="%s-open" class="checkbox-open">%s</label>
 <input type="text" name="%s-open" id="%s-open" />

EOS
                , $choice->getName(), self::$otherTranslation, $choice->getName(), $choice->getName());
        }
        return Serializer_Table_Table::serialize($choice, $result);
    }
}
?>
