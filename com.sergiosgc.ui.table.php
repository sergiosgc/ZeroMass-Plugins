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
    public function output() {/*{{{*/
?>
<table class="table">
 <tbody>
<?php 
        foreach ($this->rows as $row) {
?>
  <tr>
<?php       foreach ($row as $field => $value) { ?>
   <td class="field-<?php echo $field ?>"><?php echo $value ?></td>
<?php } ?>
  </tr>
<?php
        }
?>
 </tbody>
</table>
<?php
    }/*}}}*/
}
?>
