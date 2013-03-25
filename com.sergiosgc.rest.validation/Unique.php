<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Unique implements FieldValidator {
    public function __construct($message = null) {
        $this->message = $message;
    }
    public function validate($value, $field, $entity) {
        $rest = \com\sergiosgc\Facility::get('REST');
        $existing = $rest->read($entity, array($field => $value));
        if (count($existing) == 0) return $value;
        if (is_null($this->message)) {
            throw new UniqueValidationException($field, __('%s must be unique', $field));
        }
        throw new UniqueValidationException($field, $this->message);
    }
}
