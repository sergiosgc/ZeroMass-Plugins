<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * A form restriction. It can be a field-level restriction, as well as a form-level restriction
 *
 * As with the rest of this package, restrictions contain the data needed to define the restriction, but 
 * contain no provision for generating the proper validation procedures. Interface generation is outside the
 * scope of the package
 */
abstract class Restriction
{
    /* target field {{{ */
    protected $target;
    public function setTarget($target)
    {
        $this->target = $target;
    }
    public function getTarget()
    {
        return $this->target;
    }
    /* }}} */
    /* toString {{{ */
    public function __toString()
    {
        return get_class($this);
    }
    /* }}} */
}
?>
