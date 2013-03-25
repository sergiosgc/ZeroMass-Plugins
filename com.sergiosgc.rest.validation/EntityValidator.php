<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

interface EntityValidator {
    /**
     * Validate the fields
     *
     * This function validates the fields in an entity. It should throw an
     * EntityValidationException if the fields do not pass validation.
     *
     * @param array Fields to be validated
     * @param string Entity
     */
    public function validate($fields, $entity);
}
