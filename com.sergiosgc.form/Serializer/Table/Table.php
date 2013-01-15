<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Serializer_Table_Table is the generic layout serializer for an input. It receives the specific code for the widget and
 * inserts it in a table row
 */
class Serializer_Table_Table
{
    public static function serialize(Input $input, $innerCode)
    {
        return sprintf(<<<EOS
<tr class="%s">
 <td class="label">
  <label for="%s">%s</label>
 </td>
 <td class="input">
%s
 </td>
 <td class="%s">
%s
 </td>
</tr>

EOS
        ,
        Serializer_Table::oddEven(),
        $input->getName(), $input->getLabel(), 
        Form::indent($innerCode, 2),
        $input->getError() == '' ? 'help' : 'error',
        Form::indent($input->getError() == '' ? $input->getHelp() : $input->getError(), 2));
    }
}
?>
