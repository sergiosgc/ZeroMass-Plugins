<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_ALA is a serializer to xhtml that produces code according to ALA's accessible forms article
 *
 * Please check 
 *  - http://www.alistapart.com/articles/prettyaccessibleforms 
 *  - http://www.alistapart.com/articles/sensibleforms
 * for a description of the structure
 */
class Serializer_ALA
{
    private $inputToSerializerMap = array(
        '\com\sergiosgc\form\Input_MultipleChoice' => '\com\sergiosgc\form\Serializer_ALA_MultipleChoice',
        '\com\sergiosgc\form\Input_Date' => '\com\sergiosgc\form\Serializer_ALA_Input',
        '\com\sergiosgc\form\Input_Hidden' => '\com\sergiosgc\form\Serializer_ALA_Hidden',
        '\com\sergiosgc\form\Input_Numeric' => '\com\sergiosgc\form\Serializer_ALA_Input',
        '\com\sergiosgc\form\Input_Text' => '\com\sergiosgc\form\Serializer_ALA_Input',
        '\com\sergiosgc\form\Input_Password' => '\com\sergiosgc\form\Serializer_ALA_Password',
        '\com\sergiosgc\form\Input_Button' => '\com\sergiosgc\form\Serializer_ALA_Button',
        '\com\sergiosgc\form\MemberSet' => '\com\sergiosgc\form\Serializer_ALA_FieldSet'
        );

    public function serialize(Form $form)
    {
        $hasButton = false;
        foreach($form->getIterator() as $input) {
            if ($input instanceof \com\sergiosgc\form\Input_Button) $hasButton = true;
        }
        if ($hasButton) {
            $result = sprintf(<<<EOS
<form method="post" action="%s">
%s
</form>
EOS
            , $form->getAction('submit'), Form::indent($this->serializeMember($form->getTopMemberSet())));
        } else {
            $result = sprintf(<<<EOS
<form method="post" action="%s">
%s
<input type="submit" />
</form>
EOS
            , $form->getAction('submit'), Form::indent($this->serializeMember($form->getTopMemberSet())));
        }
        return $result;
    }
    public function serializeMember(Member $member)
    {
        foreach ($this->inputToSerializerMap as $input => $serializer) if ($member instanceof $input) return call_user_func(
            array($serializer, 'serialize'), $this, $member);
        throw new Exception('Unable to map serializer for form member of class ' . get_class($member) . ': ' . $member->__toString());
    }
    public static function entitize($value) {
        return strtr($value, array(
         '"' => '&#34;'
        ));
    }
}
?>
