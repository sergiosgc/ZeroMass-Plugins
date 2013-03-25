<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;
abstract class ValidationException extends Exception { 
    public abstract function getValidationMessages();
}
?>
