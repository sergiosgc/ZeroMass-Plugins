<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Unique implements FieldValidator {
    public function __construct($message = null) {
        $this->message = $message;
    }
    public function validate($value, $field, $entity, $fields) {
        $rest = \com\sergiosgc\Facility::get('REST');
        $existing = $rest->read($entity, array($field => $value));
        if (count($existing) == 0) return $value;

        /*
        $reflection = \com\sergiosgc\db\Reflection::create(\com\sergiosgc\Facility::get('db'));
        $table = \com\sergiosgc\Rest::getInstance()->getTableFromEntity($entity);
        $pkeys = $reflection->getPrimaryKeys($table);

        foreach (array_reverse(array_keys($existing)) as $i) {
            $targetRow = true;
            foreach ($pkeys as $pkey) {
            }

        }
        var_dump($fields); exit;
        */



        if (is_null($this->message)) {
            throw new UniqueValidationException($field, __('%s must be unique', $field));
        }
        throw new UniqueValidationException($field, $this->message);
    }
}
