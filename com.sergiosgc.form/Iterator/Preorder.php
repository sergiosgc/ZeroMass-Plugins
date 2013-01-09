<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A member set. Aggregates inputs and other member sets in a labeled group.
 */
class Iterator_Preorder implements \Iterator
{
    protected $iteratorStack = array();
    protected function peekIterator()
    {
        return $this->iteratorStack[count($this->iteratorStack) - 1];
    }
    protected function pushIterator($iterator)
    {
        $iterator->rewind();
        $this->iteratorStack[] = $iterator;
    }
    protected function popIterator()
    {
        if (count($this->iteratorStack) <= 1) throw new Exception('Trying to pop last iterator');
        $result = $this->peekIterator();
        unset($this->iteratorStack[count($this->iteratorStack) - 1]);
        return $result;
    }
    /* constructor {{{ */
    public function __construct(\RecursiveIterator $inner)
    {
        $inner->rewind();
        $this->iteratorStack[] = $inner;
    }
    /* }}} */
    public function current()
    {
        return $this->iteratorStack[count($this->iteratorStack) - 1]->current();
    }
    public function key()
    {
        return $this->iteratorStack[count($this->iteratorStack) - 1]->key();
    }
    public function next()
    {
        if ($this->peekIterator() instanceof \RecursiveIterator && $this->peekIterator()->hasChildren()) {
            $this->pushIterator($this->peekIterator()->getChildren());
        } else {
            $this->peekIterator()->next();
        }
        // If the top iterator is not valid, pop it and move the next iterator with its next()
        while (count($this->iteratorStack) > 1 && !$this->peekIterator()->valid()) {
            $this->popIterator();
            $this->peekIterator()->next();
        }
    }
    public function rewind()
    {
        $this->iteratorStack = array($this->iteratorStack[0]);
        $this->peekIterator()->rewind();
    }
    public function valid()
    {
        return $this->peekIterator()->valid();
    }
}
?>
