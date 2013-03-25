<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;
class EntityValidationException extends ValidationException { 
    protected $fieldExceptions = array();
    public function __construct($entity) {
        $this->entity = $entity;
        parent::__construct("Validation failed for $entity", 0, null);
    }
    public function addValidationException($exception) {
        if (is_callable(array($exception, 'getField'))) {
            $field = $exception->getField();
            if (!isset($this->fieldExceptions[$field])) $this->fieldExceptions[$field] = array();
            $this->fieldExceptions[$field][] = $exception;
        } elseif (is_callable(array($exception, 'getFields'))) {
            $fields = $exception->getFields();
            foreach ($fields as $field) {
                if (!isset($this->fieldExceptions[$field])) $this->fieldExceptions[$field] = array();
                $subExceptions = $exception->getFieldExceptions($field);
                foreach ($subExceptions as $subException) $this->fieldExceptions[$field][] = $subException;
            }
        } else {
            throw new \Exception('Unable to add exception of type ' . get_class($exception));
        }
        $this->updateMessage();
    }
    protected function updateMessage() {
        $message = "Validation failed for " . $this->entity . "\n";
        foreach ($this->fieldExceptions as $field => $exceptionArray) {
            foreach ($exceptionArray as $exception) {
                $message .= "  " . $exception->getMessage() . "\n";
            }
        }
        $this->message = $message;
    }
    public function __toString() {
        return 'aha';
    }
    public function getFields() {
        return array_keys($this->fieldExceptions);
    }
    public function getFieldExceptions($field) {
        if (!isset($this->fieldExceptions[$field])) return $this->fieldExceptions;
        return $this->fieldExceptions[$field];
    }
    public function getValidationMessages() {
        $result = array();
        foreach ($this->fieldExceptions as $exceptions) foreach ($exceptions as $e) {
            foreach ($e->getValidationMessages() as $field => $messages) {
                if (!isset($result[$field])) $result[$field] = array();
                $result[$field] = array_merge($result[$field], $messages);
            }
        }
        return $result;
    }

}
?>
