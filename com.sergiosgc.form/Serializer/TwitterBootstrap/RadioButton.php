<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_RadioButton is the TwitterBootstrap serializer that produces radio button inputs
 */
class Serializer_TwitterBootstrap_RadioButton
{
    public static $otherTranslation = null;
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input_MultipleChoice $choice) {
        $options = '';
        foreach($choice->getChoices() as $value => $label) $options .= sprintf(<<<EOS
 <label class="radio"><input type="radio" name="%s" value="%s"%s>%s</label>
EOS
            , $choice->getName()
            , $value
            , $value == $choice->getValue() ? ' checked' : ''
            , $label
        );
        
        $openChoiceInput = '';
        if (count($choice->getRestrictionsByClass('\com\sergiosgc\form\Restriction_ClosedChoice')) == 0) {
            if ($parentSerializer->getLayout() == 'horizontal') {
                $openChoiceInput .= sprintf(<<<EOS
<div class="%s">
 <label class="control-label" for="%s-open">%s</label>
 <div class="controls">
  <input type="text" name="%s-open" id="%s-open" />
 </div>
</div>

EOS
                , is_null($choice->error) ? 'control-group' : 'control-group error'
                , $choice->getName(), 
                __('Other:'),
                $choice->getName(), 
                $choice->getName());
            } else {
                $openChoiceInput .= sprintf(<<<EOS
 <label for="%s-open">%s</label>
 <input type="text" name="%s-open" id="%s-open" />

EOS
                , $choice->getName(), 
                __('Other:'),
                $choice->getName(), 
                $choice->getName());
            }
        }

        $label = '';
        if ($choice->getLabel() != '') {
            $label = sprintf('<label%sfor="%s">%s</label>'
                , $parentSerializer->getLayout() == 'horizontal' ? ' class="control-label" ' : ''
                , $choice->getName()
                , $choice->getLabel() 
            );
        }
        
        $result ='';
        if ($parentSerializer->getLayout() == 'horizontal') {
            $result .= sprintf(<<<EOS
<div class="%s">
 %s
 <div class="controls">
  %s
 </div>
</div>

EOS
                , is_null($choice->error) ? 'control-group' : 'control-group error'
                , $label
                , $options
            );
        } else {
            $result .= sprintf(<<<EOS
 %s
 %s

EOS
                , $label
                , $options
            );
        }

        $result .= $openChoiceInput;
        return $result;
    }
}
?>
