<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Regexp implements FieldValidator {
    public function __construct($regexp, $message) {
        if (preg_match($regexp, '') === false) throw new \Exception('Invalid regexp: ' . $regexp);
        $this->regexp = $regexp;
        $this->message = $message;
    }
    public function validate($value, $field, $entity, $fields) {
        if (preg_match($this->regexp, $value) === 0) throw new RegexpValidationException($field, $this->message);
    }
}
