<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Length implements FieldValidator {
    public function __construct($min, $max, $message) {
        $this->min = $min;
        $this->max = $max;
        $this->message = $message;
    }
    public function validate($value, $field, $entity) {
        if (!is_null($this->min) && strlen($value) < $this->min) throw new LengthValidationException($field, $this->message);
        if (!is_null($this->max) && strlen($value) > $this->max) throw new LengthValidationException($field, $this->message);
    }
}
