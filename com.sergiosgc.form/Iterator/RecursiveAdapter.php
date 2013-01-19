<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A member set. Aggregates inputs and other member sets in a labeled group.
 */
class Iterator_RecursiveAdapter implements \RecursiveIterator
{
    /* constructor {{{ */
    public function __construct(Iterator $inner)
    {
        $this->inner = $inner;
    }
    /* }}} */
    public function current()
    {
        return $this->inner->current();
    }
    public function getChildren()
    {
        throw new Exception('getChildren can\'t be called when hasChildren returned false');
    }
    public function hasChildren()
    {
        return false;
    }
    public function key()
    {
        return $this->inner->key();
    }
    public function next()
    {
        return $this->inner->next();
    }
    public function rewind()
    {
        return $this->inner->rewind();
    }
    public function valid()
    {
        return $this->inner->valid();
    }
}
?>
