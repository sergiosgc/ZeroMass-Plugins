<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA_MemberSet is a memberset serializer to xhtml that produces code according to ALA's accessible forms article
 * for a description of the structure
 */
class Serializer_ALA_FieldSet
{
    public static function serialize(Serializer_ALA $parentSerializer, MemberSet $set)
    {
        $result = sprintf(<<<EOS
<fieldset>
%s%s
</fieldset>
EOS
        , $set->getLabel() == '' ? '' : ('<legend>' . $set->getLabel() . '</legend>' . "\n"), '%s');


        $members = $set->getMembers();
        if (count($members) == 0 || count($members) == 1 && $members[0] instanceof MemberSet) {
            $innerResult = count($members) == 0 ? '' : $parentSerializer->serializeMember($members[0]);
        } else {
            $innerResult = '<ol>' . "\n";
            foreach ($members as $idx => $member) $innerResult .= Form::indent(sprintf(<<<EOS
<li class="%s">
%s</li>

EOS
                , $idx % 2 ? 'even' : 'odd', Form::indent($parentSerializer->serializeMember($member)))); // odd and even are swapped because of 0-start count
            $innerResult .= '</ol>';
        }
        return sprintf($result, Form::indent($innerResult));
    }
}
?>
