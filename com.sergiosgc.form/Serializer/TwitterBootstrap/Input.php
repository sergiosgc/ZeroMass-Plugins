<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_Input is an input serializer to xhtml that produces code according to TwitterBootstrap's accessible forms article
 * for a description of the structure
 */
class Serializer_TwitterBootstrap_Input
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, Input $input)
    {
        if ($parentSerializer->getLayout() == 'horizontal') {
            return sprintf(<<<EOS
<div class="%s">
 <label class="control-label" for="%s">%s</label>
 <div class="controls">
  <input type="text" id="%s" name="%s" value="%s">
  %s
 </div>
</div>
EOS
            , is_null($input->error) ? 'control-group' : 'control-group error'
            , Serializer_TwitterBootstrap::entitize($input->name)
            , $input->getLabel()
            , Serializer_TwitterBootstrap::entitize($input->name)
            , Serializer_TwitterBootstrap::entitize($input->name)
            , Serializer_TwitterBootstrap::entitize($input->value)
            , Serializer_TwitterBootstrap::helpBlock($input->help, $input->error)
            );

        } else {
            return sprintf(<<<EOS
<label for="%s">%s</label>
<input id="%s" name="%s" value="%s" />
%s
EOS
            , Serializer_TwitterBootstrap::entitize($input->name)
            , $input->getLabel()
            , Serializer_TwitterBootstrap::entitize($input->name)
            , Serializer_TwitterBootstrap::entitize($input->name)
            , Serializer_TwitterBootstrap::entitize($input->value)
            , Serializer_TwitterBootstrap::helpBlock($input->help, $input->error)
            );
        }
    }
}
?>
