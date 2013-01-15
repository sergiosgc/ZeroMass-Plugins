<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table is a serializer to xhtml that lays out the form in a three column table (label, input, help/error_message)
 */
class Serializer_Table
{
    private $inputToSerializerMap = array(
        '\com\sergiosgc\form\Input_MultipleChoice' => '\com\sergiosgc\form\Serializer_Table_MultipleChoice',
        '\com\sergiosgc\form\Input_Date' => '\com\sergiosgc\form\Serializer_Table_Input',
        '\com\sergiosgc\form\Input_Hidden' => '\com\sergiosgc\form\Serializer_Table_Hidden',
        '\com\sergiosgc\form\Input_Numeric' => '\com\sergiosgc\form\Serializer_Table_Input',
        '\com\sergiosgc\form\Input_Text' => '\com\sergiosgc\form\Serializer_Table_Text',
        '\com\sergiosgc\form\MemberSet' => '\com\sergiosgc\form\Serializer_Table_FieldSet'
        );
    public static function oddEven()
    {
        static $last;
        return $last = ($last == 'odd' ? 'even' : 'odd');
    }


    public function serialize(Form $form)
    {
        $result = sprintf(<<<EOS
<form method="post" action="%s" class="structures_form_serializer_table">
 <h1>%s</h1>
 <table class="structures_form_serializer_table">
%s
%s
  <tr class="%s">
   <td> </td>
   <td><input type="submit" value="%s"/></td>
   <td> </td>
  </tr>
 </table>
</form>
EOS
        , $form->getAction('submit'), $form->getTitle(),
        $form->getHelp() != '' ? sprintf(<<<EOS
  <tr class="%s">
   <td colspan="3" class="form-help">
%s   
   </td>
EOS
            , self::oddEven(), Form::indent($form->getHelp(), 5)) : '',
        Form::indent($this->serializeMember($form->getTopMemberSet()), 2),
        self::oddEven(),
        $form->getActionVerb('submit'));
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
