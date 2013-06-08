<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Mandatory implements FieldValidator {
    public function __construct($message = null) {
        $this->message = $message;
    }
    public function validate($value, $field, $entity, $fields) {
        if (is_null($value) || $value == '') {
            if (is_null($this->message)) {
                throw new MandatoryValidationException($field, __('%s is mandatory', $field));
            }
            throw new MandatoryValidationException($field, $message);
        }
    }
}
