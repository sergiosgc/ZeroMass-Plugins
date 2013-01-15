<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_MemberSet is a memberset serializer to xhtml that produces code according to Table's accessible forms article
 * for a description of the structure
 */
class Serializer_Table_FieldSet
{
    public static function serialize(Serializer_Table $parentSerializer, MemberSet $set)
    {
        $result = '';
        foreach ($set->getMembers() as $idx => $member) $result .= $parentSerializer->serializeMember($member);
        return $result;
    }
}
?>
