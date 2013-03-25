<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest\validation;

class Html {
    protected static $singleton = null;
    /**
     * Singleton pattern instance getter
     *
     * @return Html The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.validation.failed', array($this, 'validationFailed'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.html.create_form', array($this, 'injectValidationIntoForm'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.html.update_form', array($this, 'injectValidationIntoForm'));
    }/*}}}*/
    public function validationFailed($exception, $fields, $entity) {/*{{{*/
        $failedValidation = array(
            'fieldValues' => $fields,
            'entity' => $entity,
            'messages' => $exception->getValidationMessages());
        \com\sergiosgc\Facility::get('session')->set('com_sergiosgc_rest_validation_' . $entity, $failedValidation);
        header('Location: ' . $_SERVER['HTTP_REFERER']); 
        exit;
    }/*}}}*/
    public function injectValidationIntoForm($form, $entity) {/*{{{*/
        $validation = \com\sergiosgc\Facility::get('session')->get('com_sergiosgc_rest_validation_' . $entity);
        if (is_null($validation)) return $form;
        \com\sergiosgc\Facility::get('session')->delete('com_sergiosgc_rest_validation_' . $entity);
        foreach ($validation['fieldValues'] as $field => $value) $form->setValue($field, $value);
        foreach ($validation['messages'] as $field => $messages) {
            $message = '';
            foreach ($messages as $msg) $message .= $msg . "\n";
            $form->getInput($field)->setError($message);
        }
        return $form;
    }/*}}}*/
}

Html::getInstance();

/*#
 * Single line
 *
 * Longer desc
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
