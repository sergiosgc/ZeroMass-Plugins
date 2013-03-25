<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;
class FieldValidationException extends ValidationException { 
    public function __construct($field, $message) {
        $this->userMessage = $message;
        $this->field = $field;
        parent::__construct("Validation failed for $field: $message", 0, null);
    }
    public function getField() { 
        return $this->field; 
    }
    public function getValidationMessages() {
        return array( $this->field => array($this->userMessage) );
    }
}
?>
