<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\ui;
/*#
 * com.sergiosgc.ui.table
 *
 * com.sergiosgc.ui.table is an user-interface building helper class for creating tables. It creates tables using the 
 * standard Twitter bootstrap classes 
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 * @link http://twitter.github.com/bootstrap/base-css.html#tables
 */
class Table {
    public $fields;
    public $rows = array();
    public $columnCount;
    public $classes = array();
    public $links = array();
    public $rowActions = array();
    public function addRow($row) {/*{{{*/
        if (!is_array($row)) throw new \ZeroMassException('row parameter must be an array');
        if ($this->isHash($row)) {
            if (!isset($this->fields)) {
                $this->fields = array_keys($row);
                if (count($this->rows)) {
                    $toReinsert = $this->rows;
                    $this->rows = array();
                    foreach($toReinsert as $r) $this->addRow($r);
                }
            }
            $this->rows[] = array();
            foreach ($this->fields as $field) {
                if (!isset($row[$field])) throw new \ZeroMassException(sprintf('Invalid row added, missing field %s', $field));
                $this->rows[count($this->rows) - 1][$field] = $row[$field];
            }
        } else {
            if (isset($this->fields)) {
                if (count($this->fields) != count($row)) throw new \ZeroMassException(sprintf('Invalid row added. Table has %d columns, row has %d columns', count($this->fields), count($row)));
                $this->rows[] = array();
                foreach ($this->fields as $i => $field) $this->rows[count($this->rows) - 1][$field] = $row[$i];
            } else {
                if (!isset($this->columnCount)) $this->columnCount = count($row);
                if ($this->columnCount != count($row)) throw new \ZeroMassException(sprintf('Invalid row added. Table has %d columns, row has %d columns', $this->columnCount, count($row)));
                $this->rows[] = $row;
            }
        }
    }/*}}}*/
    public function isHash($array) {/*{{{*/
        foreach(array_keys($array) as $key) if ($key != ((int) $key)) return true;
        return false;
    }/*}}}*/
    public function getHeaders() {/*{{{*/
        if (!isset($this->headers)) {
            $row = array_values($this->rows);
            if (count($row) == 0) return array();
            $row = $row[0];
            $this->headers = array();
            foreach ($row as $field => $value) $this->headers[$field] = $field;
        }
        return $this->headers;
    }/*}}}*/
    public function setHeaders($headers) {/*{{{*/
        if (is_array($headers)) {
            $this->headers = $headers;
        } else {
            $headers = array_values(func_get_args());
            $row = array_values($this->rows);
            if (count($row) == 0) return;
            if (count($row[0]) != count($headers)) throw new \Exception(sprintf('Header count (%d) and column count (%d) are different', count($headers), count($row[0])));
            $row = $row[0];
            $this->headers = array();

            foreach (array_keys($row) as $i => $field) {
                $this->headers[$field] = $headers[$i];
            }
        }
    }/*}}}*/
    public function addClass($class) {/*{{{*/
        $this->classes[] = $class;
    }/*}}}*/
    public function removeClass($class) {/*{{{*/
        if (!in_array($class, $this->classes)) return;
        foreach ($this->classes as $index => $test) if ($test == $class) {
            unset($this->classes[$index]);
            $this->classes = array_values($this->classes);
            return;
        }
    }/*}}}*/
    public function addLink($column, $printfPattern) {/*{{{*/
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $this->links[$column] = array('href' => $printfPattern, 'args' => $args);
    }/*}}}*/
    public function addRowAction($label, $printfPattern) {/*{{{*/
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $this->rowActions[] = array('label' => $label, 'href' => $printfPattern, 'args' => $args);
    }/*}}}*/

    public function output() {/*{{{*/
?>
<table class="table<?php foreach($this->classes as $class) echo " $class"; ?>">
 <thead>
  <tr>
<?php
        foreach ($this->getHeaders() as $field => $value) {
?>
   <th class="field-<?php echo $field ?>"><?php echo $value ?></th>
<?php
        }
        if (count($this->rowActions) > 0) print('<th class="row-actions"> </th>');
?>
  </tr>
 </thead>
 <tbody>
<?php 
        foreach ($this->rows as $row) {
?>
  <tr>
<?php       foreach ($row as $field => $value) { ?>
<td class="field-<?php echo $field ?>"><?php
if (isset($this->links[$field])) { 
    $values = array();
    $values[0] = $this->links[$field]['href'];
    foreach ($this->links[$field]['args'] as $arg) $values[] = $row[$arg];
    printf('<a href="%s">%s</a>', call_user_func_array('sprintf', $values), $value); 
   } else {
       echo $value;
   }
?></td>
<?php } 
if (count($this->rowActions) > 0) {
?>
<td class="row-actions">
<div class="btn-group">
<?php printf('<button class="btn dropdown-toggle" data-toggle="dropdown">%s <span class="caret"></span></button>', __('Actions')); ?>
<ul class="dropdown-menu">
<?php 
foreach ($this->rowActions as $action) { 
    $values = array();
    $values[0] = $action['href'];
    foreach($action['args'] as $arg) $values[] = $row[$arg];
    printf('<li><a href="%s">%s</a></li>', call_user_func_array('sprintf', $values), $action['label']);
}
?>
<?php } ?>
</ul>
</td>
</tr>
<?php } ?>
 </tbody>
</table>
<?php
    }/*}}}*/
}
?>
