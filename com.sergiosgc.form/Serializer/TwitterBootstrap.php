<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_TwitterBootstrap is a serializer to xhtml that produces code according to Twitter Bootstrap
 *
 * Please check 
 *  - http://www.alistapart.com/articles/prettyaccessibleforms 
 * for a description of the structure
 */
class Serializer_TwitterBootstrap
{
    private $inputToSerializerMap = array(
        '\com\sergiosgc\form\Input_MultipleChoice' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_MultipleChoice',
        '\com\sergiosgc\form\Input_Date' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Input',
        '\com\sergiosgc\form\Input_Hidden' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Hidden',
        '\com\sergiosgc\form\Input_Numeric' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Input',
        '\com\sergiosgc\form\Input_Text' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Input',
        '\com\sergiosgc\form\Input_Password' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Password',
        '\com\sergiosgc\form\Input_Button' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_Button',
        '\com\sergiosgc\form\MemberSet' => '\com\sergiosgc\form\Serializer_TwitterBootstrap_FieldSet'
        );

    public function serialize(Form $form)/*{{{*/
    {
        $hasButton = false;
        foreach($form->getIterator() as $input) {
            if ($input instanceof \com\sergiosgc\form\Input_Button) $hasButton = true;
        }
        switch ($this->getLayout()) {
        case 'default':
            $class = '';
            break;
        case 'search':
            $class = 'form-search';
            break;

        case 'inline':
            $class = 'form-inline';
            break;

        case 'horizontal':
            $class = 'form-horizontal';
            break;
        default:
            $class = '';
        }
        if ($hasButton) {
            $result = sprintf(<<<EOS
<form class="%s" method="post" action="%s">
%s
</form>
EOS
            , $class, $form->getAction('submit'), Form::indent($this->serializeMember($form->getTopMemberSet())));
        } else {
            $result = sprintf(<<<EOS
<form class="%s" method="post" action="%s">
%s
<input type="submit" />
</form>
EOS
            , $class, $form->getAction('submit'), Form::indent($this->serializeMember($form->getTopMemberSet())));
        }
        return $result;
    }/*}}}*/
    public function serializeMember(Member $member)/*{{{*/
    {
        foreach ($this->inputToSerializerMap as $input => $serializer) if ($member instanceof $input) return call_user_func(
            array($serializer, 'serialize'), $this, $member);
        throw new Exception('Unable to map serializer for form member of class ' . get_class($member) . ': ' . $member->__toString());
    }/*}}}*/
    public static function entitize($value) {/*{{{*/
        return strtr($value, array(
         '"' => '&#34;'
        ));
    }/*}}}*/
    protected $layout = 'default';
    public function setLayout($to) {/*{{{*/
        switch ($to) {
            case 'default':
            case 'search':
            case 'inline':
            case 'horizontal':
                $this->layout = $to;
        }
    }/*}}}*/
    public function getLayout() {/*{{{*/
        return $this->layout;
    }/*}}}*/

}
?>
