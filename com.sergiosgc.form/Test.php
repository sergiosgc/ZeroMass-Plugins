<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class Test
{
    public function __construct() {/*{{{*/
         @\ZeroMass::register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleCurrentRequest'));
    }/*}}}*/
    public function handleCurrentRequest($handled) {/*{{{*/
        if ($handled) return;
        switch ($_SERVER['REQUEST_URI']) {
        case '/form/test/1/':
            $this->alaSerializer();
            return true;
        case '/form/test/2/':
            $this->dateInput();
            return true;
        case '/form/test/3/':
            $this->emptyForm();
            return true;
        case '/form/test/4/':
            $this->formIterator();
            return true;
        case '/form/test/5/':
            $this->htmlInput();
            return true;
        case '/form/test/6/':
            $this->integerInput();
            return true;
        case '/form/test/7/':
            $this->multipleChoice();
            return true;
        case '/form/test/8/':
            $this->numericInput();
            return true;
        case '/form/test/9/':
            $this->textInput();
            return true;
        case '/form/test/10/':
            $this->tableSerializer();
            return true;
        default:
            return false;
        }
    }/*}}}*/
    public function alaSerializer() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Text('text'));
        $form->addMember($choice = new Input_MultipleChoice('checkbox-open-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both, none or other?');
        $form->addMember($choice = new Input_MultipleChoice('checkbox-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both or none?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $form->addMember($choice = new Input_MultipleChoice('radiobutton-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setLabel('Foo or bar?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz or bat?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz, bat or other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open-multiple'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setValue(array(1,3));
        $choice->setLabel('Foo, bar, baz, bat and other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->setValue('text', '"quote text"');
        $serializer = new Serializer_ALA();
        print($serializer->serialize($form));

    }/*}}}*/
    public function dateInput() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Date('teste'));
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function emptyForm() {/*{{{*/
        $form = new Form('/someaction/', 'Empty form', 'This form has no inputs so it requires no action from you');
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function formIterator() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Text('text'));
        $form->addMember($choice = new Input_MultipleChoice('checkbox-open-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both, none or other?');
        $form->addMember($choice = new Input_MultipleChoice('checkbox-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both or none?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $form->addMember($choice = new Input_MultipleChoice('radiobutton-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setLabel('Foo or bar?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz or bat?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz, bat or other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open-multiple'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setValue(array(1,3));
        $choice->setLabel('Foo, bar, baz, bat and other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        foreach ($form as $member) {
            print((string) $member);
            print("\n");
        }
    }/*}}}*/
    public function htmlInput() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Text('test', null, true, 'text/html'));
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function integerInput() {/*{{{*/
        $form = new Form('/someaction/', 'Form with integer input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember($input = new Input_Numeric('test'));
        $input->addRestriction(new Restriction_Integer());
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function multipleChoice() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember($choice = new Input_MultipleChoice('test'));
        $choice->addChoice(1, 'foo');
        $choice->addChoice(2, 'bar');
        $choice->addChoice(3, 'baz');
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function numericInput() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Numeric('test'));
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function textInput() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Text('test'));
        $serializer = new Serializer_Test();
        var_dump($serializer->serialize($form));
    }/*}}}*/
    public function tableSerializer() {/*{{{*/
        $form = new Form('/someaction/', 'Form with text input', 'This form has no interesting inputs so it requires no action from you');
        $form->addMember(new Input_Text('text'));
        $form->addMember($choice = new Input_MultipleChoice('checkbox-open-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both, none or other?');
        $form->addMember($choice = new Input_MultipleChoice('checkbox-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setValue(array(1));
        $choice->setLabel('Foo, bar, both or none?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $form->addMember($choice = new Input_MultipleChoice('radiobutton-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->setLabel('Foo or bar?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz or bat?');
        $choice->addRestriction(new Restriction_ClosedChoice());
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setLabel('Foo, bar, baz, bat or other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->addMember($choice = new Input_MultipleChoice('dropdown-field-open-multiple'));
        $choice->addChoice(0, 'foo');
        $choice->addChoice(1, 'bar');
        $choice->addChoice(2, 'baz');
        $choice->addChoice(3, 'bat');
        $choice->setValue(array(1,3));
        $choice->setLabel('Foo, bar, baz, bat and other?');
        $choice->addRestriction(new Restriction_ExclusiveChoice());
        $form->setValue('text', '"quote text"');
        $serializer = new Serializer_Table();
        print($serializer->serialize($form));
    }/*}}}*/
}
?>
