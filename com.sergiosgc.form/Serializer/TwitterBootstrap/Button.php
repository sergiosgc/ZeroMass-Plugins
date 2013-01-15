<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_Button is an input serializer to xhtml that produces code according to TwitterBootstrap's accessible forms article
 * for a description of the structure
 */
class Serializer_TwitterBootstrap_Button
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input $input)
    {
        if ($parentSerializer->getLayout() == 'horizontal') {
            return sprintf(<<<EOS
<div class="control-group">
 <div class="controls">
  <button class="%s" type="%s" value="%s">%s</button>
 </div>
</div>
EOS
            , $input->isPrimary() ? 'btn btn-primary' : 'btn'
            , $input->getType()
            , Serializer_TwitterBootstrap::entitize($input->getValue())
            , is_null($input->getLabel()) ? 'Submit' : Serializer_TwitterBootstrap::entitize($input->getLabel()));
        } else {
            return sprintf(<<<EOS
<button class="%s" type="%s" value="%s">%s</button>
EOS
            , $input->isPrimary() ? 'btn btn-primary' : 'btn'
            , $input->getType()
            , Serializer_TwitterBootstrap::entitize($input->getValue())
            , is_null($input->getLabel()) ? 'Submit' : Serializer_TwitterBootstrap::entitize($input->getLabel()));
        }
    }
}
?>
