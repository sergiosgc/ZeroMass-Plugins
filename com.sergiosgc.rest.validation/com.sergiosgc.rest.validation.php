<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Validation {
    protected static $singleton = null;
    protected static $registeredAutoload = false;
    protected $entityValidators = array();
    protected $aggregateFieldValidators = array();
    
    public static function autoloader($class) {/*{{{*/
        if (strlen($class) < strlen(__NAMESPACE__) || __NAMESPACE__ != substr($class, 0, strlen(__NAMESPACE__))) return;
        $class = substr($class, strlen(__NAMESPACE__) + 1);
        $path = dirname(__FILE__) . '/' . strtr($class, array('_' => '/')) . '.php';

        require_once($path);
    }/*}}}*/
    public static function registerAutoloader() {/*{{{*/
        if (!self::$registeredAutoload) {
            spl_autoload_register(array(__CLASS__, 'autoloader'));
            self::$registeredAutoload = true;
        }
    }/*}}}*/
    /**
     * Singleton pattern instance getter
     *
     * @return Validation The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        self::registerAutoloader();
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.create.fields', array($this, 'validate'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.fields', array($this, 'validate'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('REST.Validation', $this);
    }/*}}}*/
    public function addValidator($validator, $entity = '*', $field = null) {/*{{{*/
        if ($validator instanceof EntityValidator) return $this->addEntityValidator($validator, $entity);
        if ($validator instanceof FieldValidator) return $this->addFieldValidator($validator, $entity, $field);
        throw new \Exception('Invalid validator');
    }/*}}}*/
    public function addEntityValidator($validator, $entity = '*') {/*{{{*/
        if (!isset($this->entityValidators[$entity])) $this->entityValidators[$entity] = array();
        $this->entityValidators[$entity][] = $validator;
        return $validator;
    }/*}}}*/
    public function addFieldValidator($validator, $entity = '*', $field = null) {/*{{{*/
        if (!isset($this->aggregateFieldValidators[$entity])) $this->aggregateFieldValidators[$entity] = $this->addEntityValidator(new AggregateFieldValidator($entity), $entity);
        if (is_null($field) && is_callable(array($validator, 'getField'))) $field = $validator->getField(); // If it quacks like a duck
        $this->aggregateFieldValidators[$entity]->addValidator($validator, $field);
    }/*}}}*/
    public function validate($fields, $entity) {/*{{{*/
        $result = null;
        if (isset($this->entityValidators['*'])) {
            foreach ($this->entityValidators['*'] as $validator) {
                try {
                    $validationResult = $validator->validate($fields, $entity);
                } catch (ValidationException $e) {
                    if (is_null($result)) $result = new EntityValidationException($entity);
                    $result->addValidationException($e);
                }
            }
        }
        if (isset($this->entityValidators[$entity])) {
            foreach ($this->entityValidators[$entity] as $validator) {
                try {
                    $validationResult = $validator->validate($fields, $entity);
                } catch (ValidationException $e) {
                    if (is_null($result)) $result = new EntityValidationException($entity);
                    $result->addValidationException($e);
                }
            }
        }
        if (!is_null($result)) {
            /*#
             * Validation::validate is resulting in an error. Allow plugins to handle it
             *
             * Plugins may cancel the exception by returning null
             *
             * @param Exception The exception that will be thrown
             * @param array Fields being validated
             * @param string The REST entity
             * @return mixed Either an exception or or null
             */
            $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.validation.failed', $result, $fields, $entity);
        }
        if (!is_null($result)) throw $result;
        return $fields;
    }/*}}}*/
}

Validation::getInstance();

/*#
 * Single line
 *
 * Longer desc
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
