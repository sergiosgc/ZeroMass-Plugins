<?php
namespace com\sergiosgc\form;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/** 
 * Form represents a data input form
 *
 * This is a data model class. It contains no provisioning for output. A form is a composition of:
 *  - A form title
 *  - A form-level help text
 *  - A set of Input instances
 *  - A set of actions (including a mandatory 'submit' action)
 *  - A set of form-level restrictions (children of Restriction)
 *
 * As output is not a responsability of this package, inputs are not defined in the context of input widgets.
 * Inputs defined in the package are classified by their behaviour in terms of what type of input they accept
 * and what type of restriction they perform on the input. As an example, you will find an input for closed choice,
 * single select, but nothing defining if this is a set of radio buttons or a drop-down list. Specific widget 
 * instantiation is a responsability of the view implementing package.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
class Form implements \IteratorAggregate
{
    protected static $registeredAutoload = false;
    public static function autoloader($class) {/*{{{*/
        if (strlen($class) < strlen(__NAMESPACE__) || __NAMESPACE__ != substr($class, 0, strlen(__NAMESPACE__))) return;
        $class = substr($class, strlen(__NAMESPACE__) + 1);
        $path = dirname(__FILE__) . '/' . strtr($class, array('_' => '/')) . '.php';
        require_once($path);
    }/*}}}*/
    public static function registerAutoloader() {/*{{{*/
        if (!self::$registeredAutoload) {
            spl_autoload_register(array('com\sergiosgc\form\Form', 'autoloader'));
            self::$registeredAutoload = true;
        }
    }/*}}}*/
    /* constructor {{{ */
    /**
     * Constructor
     *
     * @param string submitAction Submit action URI
     */
    public function __construct($submitAction, $title, $help)
    {
        self::registerAutoloader();
        $this->setAction('submit', $submitAction);
        $this->title = $title;
        $this->help = $help;
        $this->topMemberSet = new MemberSet('');
    }
    /* }}} */
    /* title field {{{ */
    public $title;
    public function getTitle()
    {
        return $this->title;
    }
    /* }}} */
    /* help field {{{ */
    public $help;
    public function getHelp()
    {
        return $this->help;
    }
    /* }}} */
    /* actions field {{{ */
    protected $actions = array();
    public function getAction($type)
    {
        if (!array_key_exists($type, $this->actions)) throw new Exception('Unknown form action: ' . $type);
        return $this->actions[$type];
    }
    public function setAction($type, $uri)
    {
        if ($uri == '' || is_null($uri)) throw new Exception('Empty action uri');
        $this->actions[$type] = $uri;
    }
    public function removeAction($type)
    {
        if ($type == 'submit') throw new Exception('Submit action can\'t be removed');
        unset($this->actions[$type]);
    }
    /* }}} */
    /* actionVerbs field {{{ */
    protected $actionVerbs = array();
    public function getActionVerb($type)
    {
        if (!array_key_exists($type, $this->actionVerbs)) return $type;
        return $this->actionVerbs[$type];
    }
    public function setActionVerb($type, $verb)
    {
        if ($verb == '' || is_null($verb)) throw new Exception('Empty actionVerb verb');
        $this->actionVerbs[$type] = $verb;
    }
    public function removeActionVerb($type)
    {
        unset($this->actionVerbs[$type]);
    }
    /* }}} */
    /* topMemberSet field {{{ */
    protected $topMemberSet;
    public function getTopMemberSet()
    {
        return $this->topMemberSet;
    }
    /* }}} */
    /* members field delegation {{{ */
    public function addMember(Member $val)
    {
        $this->topMemberSet->addMember($val);
    }
    public function getMembers()
    {
        $this->topMemberSet->getMembers();
    }
    public function getMember($index)
    {
        $this->topMemberSet->getMember($index);
    }
    public function getMemberIndex(Member $val)
    {
        $this->topMemberSet->getMemberIndex($val);
    }
    public function removeMember($index)
    {
        $this->topMemberSet->removeMember($index);
    }
    /* }}} */
    /* restrictions field {{{ */
    protected $restrictions = array();
    public function getRestriction($index)
    {
        return $this->restrictions[$index];
    }
    public function getRestrictions()
    {
        return $this->restrictions;
    }
    public function getRestrictionsByClass($class)
    {
        $result = array();
        foreach ($this->restrictions as $restriction) if ($restriction instanceof $class) $result[] = $restriction;
        return $result;
    }
    public function hasRestrictionByClass($class)
    {
        $result = array();
        foreach ($this->restrictions as $restriction) if ($restriction instanceof $class) return true;
        return false;
    }
    public function addRestriction(Restriction $restriction)
    {
        $restriction->setTarget($this);
        $this->restrictions[] = $restriction;
    }
    public function removeRestriction($index)
    {
        unset($this->restrictions[$index]);
        $this->restrictions = array_values($this->restrictions);
    }
    /* }}} */
    /* toString {{{ */
    public function __toString()
    {   
        $result = sprintf("Form instance with title '%s'\n", $this->title);
        $result .= "-Actions:\n";
        foreach ($this->actions as $type => $action) $result .= sprintf("--'%s' -> '%s'\n", $type, $action);
        $result .= "-Top Member Set:\n";
        $input = $this->topMemberSet->__toString();
        $input = explode("\n", $input);
        foreach (array_keys($input) as $i) $input[$i] = '  ' . $input[$i];
        $input = implode("\n", $input);
        $result .= $input;
        $result .= "-Restrictions:\n";
        foreach ($this->restrictions as $index => $restriction) {
            $restriction = $restriction->__toString();
            $restriction = explode("\n", $restriction);
            foreach (array_keys($restriction) as $i) $restriction[$i] = '  ' . $restriction[$i];
            $restriction = implode("\n", $restriction);
            $result .= sprintf("--%d\n%s\n", $index, $restriction);
        }
        return $result;
    }
    /* }}} */
    /* indent {{{ */
    public static function indent($target, $indentation = null)
    {
        if (is_null($indentation)) $indentation = ' ';
        if (is_int($indentation)) {
            $temp = $indentation;
            $indentation = '';
            for ($i=0; $i<$temp; $i++) $indentation .= ' ';
            unset($temp);
        }
        return preg_replace('_^_m', $indentation, $target);
    }
    /* }}} */
    /* getIterator {{{ */
    public function getIterator()
    {
        if (is_null($this->topMemberSet)) return EmptyIterator();
        return new Iterator_Preorder($this->topMemberSet);
    }
    /* }}} */
    /* setValue {{{ */
    public function setValue($name, $value)
    {
        $this->topMemberSet->setValue($name, $value);
    }
    /* }}} */
}
/*# 
 * Object-oriented HTML form representation with pluggable serializers and validations
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
