<?php
namespace com\sergiosgc\form;

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A member set. Aggregates inputs and other member sets in a labeled group.
 */
class Iterator_RestrictionIterator implements \Iterator
{
    protected $form = null;
    protected $iterators = array();
    protected $currentIterator = 0;
    protected $key = -1;
    protected $current = null;
    protected $valid = false;
    /* constructor {{{ */
    public function __construct(Form $form)
    {
        $this->form = $form;
        $this->rewind();
    }
    /* }}} */
    public function current()
    {
        return $this->current;
    }
    public function key()
    {
        return $this->key;
    }
    public function next()
    {
        if ($this->currentIterator == 0) {
            if ($this->iterators[0]->valid()) {
                $this->key++;
                $this->valid = true;
                $this->current = $this->iterators[0]->current();
                $this->iterators[0]->next();
                return;
            } else {
                $this->currentIterator = 2;
                return $this->next();
            }
        }
        if ($this->currentIterator == 1) {
            while ($this->iterators[1]->valid() && !is_callable(array($this->iterators[1]->current(), 'getRestrictions'))) {
                $this->iterators[1]->next();
            }
            if ($this->iterators[1]->valid()) {
                $this->iterators[2] = new \ArrayIterator($this->iterators[1]->current()->getRestrictions());
                $this->iterators[1]->next();
                $this->iterators[2]->rewind();
                $this->currentIterator = 2;
                return $this->next();
            } else {
                $this->current = null;
                $this->valid = false;
                return;
            }
        }
        if ($this->currentIterator == 2) {
            if ($this->iterators[2]->valid()) {
                $this->key++;
                $this->valid = true;
                $this->current = $this->iterators[2]->current();
                $this->iterators[2]->next();
                return;
            }
            $this->currentIterator = 1;
            return $this->next();
        }
    }
    public function rewind()
    {
        $this->iterators = array(
            new \ArrayIterator($this->form->getRestrictions()),
            $this->form->getIterator(),
            new \EmptyIterator());
        $this->iterators[0]->rewind();
        $this->iterators[1]->rewind();
        $this->key = -1;
        $this->current = null;
        $this->valid = false;
        $this->next();
    }
    public function valid()
    {
        return $this->valid;
    }
}
?>
