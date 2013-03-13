<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A member set. Aggregates inputs and other member sets in a labeled group.
 */
class MemberSet extends Member implements \RecursiveIterator
{
    /* constructor {{{ */
    public function __construct($name)
    {
        parent::__construct($name);
    }
    /* }}} */
    /* members field {{{ */
    public $members = array();
    public function addMember(Member $val, $before = null)
    {
        if (is_null($before)) {
            $this->members[] = $val;
        } else {
            $referenceIndex = $this->getMemberIndex($before);
            for ($i=count($this->members); $i>$referenceIndex; $i--) $this->members[$i] = $this->members[$i-1];
            $this->members[$referenceIndex] = $val;
        }
    }
    public function getMembers()
    {
        return $this->members;
    }
    public function getMember($index)
    {
        return $this->members[$index];
    }
    public function getMemberIndex(Member $val)
    {
        foreach ($this->members as $index => $candidate) if ($candidate == $val) return $index;
        throw new Exception('Member not found');
    }
    public function removeMember($index)
    {
        if ($index instanceof Member) return $this->removeMember($this->getMemberIndex($index));
        unset($this->members[$index]);
        $this->members = array_values($this->members);
    } 
    public function moveMemberBefore($member, $reference) {
        $memberIndex = $this->getMemberIndex($member);
        unset($this->members[$memberIndex]);
        $this->members = array_values($this->members);
        $referenceIndex = $this->getMemberIndex($before);
        for ($i=count($this->members); $i>$referenceIndex; $i--) $this->members[$i] = $this->members[$i-1];
        $this->members[$referenceIndex] = $member;
    }

    /* }}} */
    /* toString {{{ */
    public function __toString()
    {   
        $result = sprintf("MemberSet instance with label '%s'\n", $this->label);
        $result .= "-Members:\n";
        foreach ($this->members as $index => $member) {
            $member = $member->__toString();
            $member = explode("\n", $member);
            foreach (array_keys($member) as $i) $member[$i] = '  ' . $member[$i];
            $member = implode("\n", $member);
            $result .= sprintf("--%d\n%s\n", $index, $member);
        }
        return $result;
    }
    /* }}} */
    /* RecursiveIterator implementation {{{ */
    protected $recursiveIteratorCursor = 0;
    public function current()
    {
        return $this->members[$this->recursiveIteratorCursor];
    }
    public function getChildren()
    {
        $candidate = $this->current();
        if ($candidate instanceof \RecursiveIterator) return $candidate;
        if ($candidate instanceof \IteratorAggregate) $candidate = $candidate->getIterator();
        if ($candidate instanceof \RecursiveIterator) return $candidate;
        if ($candidate instanceof \Iterator) return new Iterator_RecursiveAdapter($candidate);
        throw new Exception('Child at position ' . $this->recursiveIteratorCursor . ' is not iterable');
    }
    public function hasChildren()
    {
        if (!$this->valid()) return false;
        $candidate = $this->current();
        if ($candidate instanceof \RecursiveIterator) return true;
        if ($candidate instanceof \IteratorAggregate) $candidate = $candidate->getIterator();
        if ($candidate instanceof \RecursiveIterator) return true;
        if ($candidate instanceof \Iterator) return true;
    }
    public function key()
    {
        return $this->recursiveIteratorCursor;
    }
    public function next()
    {
        $this->recursiveIteratorCursor++;
    }
    public function rewind()
    {
        $this->recursiveIteratorCursor = 0;
    }
    public function valid()
    {
        return $this->recursiveIteratorCursor < count($this->members);
    }
    /* }}} */
    /* setValue {{{ */
    public function setValue($name, $value)
    {
        foreach (array_keys($this->members) as $index)
        {
            if ($this->members[$index] instanceof MemberSet) $this->members[$index]->setVavlue($name, $value);
            if ($this->members[$index] instanceof Input && $this->members[$index]->getName() == $name) $this->members[$index]->setValue($value);
        }
    }
    /* }}} */
}
?>
