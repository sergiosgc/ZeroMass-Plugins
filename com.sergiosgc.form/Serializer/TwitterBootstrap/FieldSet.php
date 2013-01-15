<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap_FieldSet is a memberset serializer to xhtml that produces code according to ALA's accessible forms article
 * for a description of the structure
 */
class Serializer_TwitterBootstrap_FieldSet
{
    public static function serialize(Serializer_TwitterBootstrap $parentSerializer, MemberSet $set)
    {
        $result = sprintf(<<<EOS
<fieldset>
%s%s
</fieldset>
EOS
        , $set->getLabel() == '' ? '' : ('<legend>' . $set->getLabel() . '</legend>' . "\n"), '%s');


        $members = $set->getMembers();
        $innerResult = '';
        foreach ($members as $idx => $member) {
            $innerResult .= $parentSerializer->serializeMember($member);
        }

        return sprintf($result, Form::indent($innerResult));
    }
}
?>
