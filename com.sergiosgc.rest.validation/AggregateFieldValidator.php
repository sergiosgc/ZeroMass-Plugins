<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class AggregateFieldValidator implements EntityValidator {
    protected $validators = array();
    public function validate($fields, $entity) {
        $result = null;
        foreach ($fields as $field => $value) if (isset($this->validators[$field])) foreach($this->validators[$field] as $validator) {
            try {
                $validator->validate($value, $field, $entity);
            } catch (FieldValidationException $e) {
                if (is_null($result)) $result = new EntityValidationException($entity);
                $result->addValidationException($e);
            }
        }
        if (!is_null($result)) throw $result;
    }
    public function addValidator($validator, $field) {
        if (!isset($this->validators[$field])) $this->validators[$field] = array();
        $this->validators[$field][] = $validator;
    }
}
