<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

interface FieldValidator {
    /**
     * Validate the field
     *
     * This function validates the field. It should throw a
     * FieldValidationException if the field does not pass validation
     */
    public function validate($value, $field, $entity, $fields);
}
