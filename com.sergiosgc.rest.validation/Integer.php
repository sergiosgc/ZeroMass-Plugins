<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Integer extends Regexp {
    public function __construct($message) {
        parent::__construct('_^[0-9]*$_', $message);
    }
}
