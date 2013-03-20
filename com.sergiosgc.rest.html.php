<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest;

use com\sergiosgc\form;
class Html {
    protected static $singleton = null;
    protected $newUrlEntityMap = array();
    protected $editUrlEntityMap = array();
    protected $lastObservedRestOperation = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \com\sergiosgc\form\Form::registerAutoloader();
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.requestDone.raw', array($this, 'outputResult'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.create', array($this, 'storeCreateResult'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.read', array($this, 'storeReadResult'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update', array($this, 'storeUpdateResult'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.delete', array($this, 'storeDeleteResult'));

        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.registerEntity', array($this, 'handleRegisterEntity'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'));
    }/*}}}*/
    public function init() {/*{{{*/
    }/*}}}*/
    public function handleRegisterEntity($args) {/*{{{*/
        if (!in_array($args['entityName'], $this->newUrlEntityMap)) {
            $this->setEntityNewUrl($args['entityName'], $args['url'] . ($args['url'][strlen($args['url']) - 1] == '/' ? '' : '/') . 'new/');
        }
        if (!in_array($args['entityName'], $this->editUrlEntityMap)) {
            $this->setEntityEditUrl($args['entityName'], $args['url'] . ($args['url'][strlen($args['url']) - 1] == '/' ? '' : '/') . 'edit/');
        }
        return $args;
    }/*}}}*/
    public function setEntityNewUrl($entity, $url) {/*{{{*/
        $this->newUrlEntityMap[$url] = $entity;
    }/*}}}*/
    public function getEntityNewUrl($entity) {/*{{{*/
        foreach ($this->newUrlEntityMap as $url => $candidate) if ($candidate == $entity) return $url;
        throw new \Exception('Unknown entity: ' . $entity);
    }/*}}}*/
    public function setEntityEditUrl($entity, $url) {/*{{{*/
        $this->editUrlEntityMap[$url] = $entity;
    }/*}}}*/
    public function getEntityEditUrl($entity) {/*{{{*/
        foreach ($this->editUrlEntityMap as $url => $candidate) if ($candidate == $entity) return $url;
        throw new \Exception('Unknown entity: ' . $entity);
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        if ($_SERVER['REQUEST_METHOD'] != 'GET') return $handled;
        $url = $_SERVER['REQUEST_URI'];
        if ('?' == $url[strlen($url) - strlen($_SERVER['QUERY_STRING']) - 1]) $url = substr($url, 0, strlen($url) - strlen($_SERVER['QUERY_STRING']) - 1);
        if (!isset($this->newUrlEntityMap[$url]) && !isset($this->editUrlEntityMap[$url]) ) return $handled;
        if ( isset($this->newUrlEntityMap[$url]) ) {
            $requestedEntity = $this->newUrlEntityMap[$url];
            $this->create($requestedEntity);
            return true;
        } elseif ( isset($this->editUrlEntityMap[$url]) ) {
            $requestedEntity = $this->editUrlEntityMap[$url];
            $this->update($requestedEntity);
            return true;
        }
        throw new \Exception('Unexpected condition. Unreachable code reached');
    }/*}}}*/
    public function create($entity) {/*{{{*/
        $result = new \com\sergiosgc\RestNoData();
        /*#
         * The plugin is about to produce a form for entity creation. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string The entity being created
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') {
            print $result;
            return;
        }
        $reflection = \com\sergiosgc\db\Reflection::create(\com\sergiosgc\Facility::get('db'));
        $table = \com\sergiosgc\Rest::getInstance()->getTableFromEntity($entity);
        $fields = $reflection->getFields($table);
        foreach(array_keys($fields) as $field) $fields[$field]['primaryKey'] = false;
        foreach($reflection->getPrimaryKeys($table) as $key) $fields[$key]['primaryKey'] = true;
        foreach(array_keys($fields) as $field) {
            $fields[$field]['show'] = true;
            if ($fields[$field]['primaryKey'] && !is_null($fields[$field]['default'] )) $fields[$field]['show'] = false;
            $fields[$field]['ui name'] = $fields[$field]['name'];
        }
        /*#
         * The plugin is producing a form for entity creation. Allow field information to be manipulated
         *
         * @param array Field metadata
         * @param string The entity being created
         * @return array Field metadata
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.metadata', $fields, $entity);
        $formAction = \com\sergiosgc\Rest::getInstance()->getUrlFromEntity($entity);
        /*#
         * The plugin is producing a form for entity creation. Allow the form action URL to be manipulated
         *
         * @param string The form action url
         * @param string The entity being created
         * @return string The form action url
         */
        $formAction = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.formaction', $formAction, $entity);

        $form = new form\Form($formAction, 'Create', '');
        $form->addMember($input = new form\Input_Hidden('com_sergiosgc_rest_httpmethod', 'PUT'));
        foreach($fields as $id => $field) if ($field['show']) {
            $form->addMember($input = new form\Input_Text($id));
            $input->setLabel($field['ui name']);
        }
        $form->addMember($input = new form\Input_Button('create'));
        $input->setLabel('Create');

        /*#
         * The plugin has just created a form for REST create. Allow it to be mangled
         *
         * @param \com\sergiosgc\form\Form The form
         * @param string The entity being created
         * @return \com\sergiosgc\form\Form The form
         */
        $form = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form', $form, $entity);
        $serializer = new \com\sergiosgc\form\Serializer_TwitterBootstrap();
        $serializer->setLayout('horizontal');

        /*#
         * The plugin is about to serialize the REST create form. Allow the serializer to be mangled
         *
         * @param \com\sergiosgc\form\Form_Serializer The form serializer
         * @param string The entity being created
         * @return \com\sergiosgc\form\Form_Serializer The form serializer
         */
        $serializer = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.serializer', $serializer, $entity);
        $serialized = $serializer->serialize($form);
        /*#
         * The plugin is about to output the create form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        $serialized = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.html', $serialized);
        if ($serialized == '' || is_null($serialized)) return;
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        echo $serialized;
    }/*}}}*/
    public function update($entity) {/*{{{*/
        $result = new \com\sergiosgc\RestNoData();
        /*#
         * The plugin is about to produce a form for entity update. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string The entity being updated
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') {
            print $result;
            return;
        }
        $reflection = \com\sergiosgc\db\Reflection::create(\com\sergiosgc\Facility::get('db'));
        $table = \com\sergiosgc\Rest::getInstance()->getTableFromEntity($entity);
        $fields = $reflection->getFields($table);
        foreach(array_keys($fields) as $field) $fields[$field]['primaryKey'] = false;
        foreach($reflection->getPrimaryKeys($table) as $key) $fields[$key]['primaryKey'] = true;
        foreach(array_keys($fields) as $field) {
            $fields[$field]['show'] = true;
            if ($fields[$field]['primaryKey'] && !is_null($fields[$field]['default'] )) $fields[$field]['show'] = false;
            $fields[$field]['ui name'] = $fields[$field]['name'];
        }

        /*#
         * The plugin is producing a form for entity update. Allow field information to be manipulated
         *
         * @param array Field metadata
         * @param string The entity being updated
         * @return array Field metadata
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.metadata', $fields, $entity);
        $formAction = \com\sergiosgc\Rest::getInstance()->getUrlFromEntity($entity);
        $separator = '?';
        foreach ($_GET as $key => $value) {
            $formAction .= sprintf('%s%s=%s', $separator, urlencode($key), urlencode($value));
            $separator = '&';
        }
        /*#
         * The plugin is producing a form for entity update. Allow the form action URL to be manipulated
         *
         * @param string The form action url
         * @param string The entity being updated
         * @return string The form action url
         */
        $formAction = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.formaction', $formAction, $entity);
        
        $data = \com\sergiosgc\Rest::getInstance()->read($entity);
        /*#
         * The plugin is producing a form for entity update. Allow the data to be edited to be manipulated.
         *
         * @param array The data
         * @param string The entity being updated
         * @param array The entity fields obtained by reflection
         * @return array The data
         */
        $data = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.data', $data, $entity, $fields);
        if (count($data) == 0) {
            /*#
             * The plugin obtained no data from get while producing a form for entity update. Allow this to be corrected
             *
             * @param array The data
             * @param string The entity being updated
             * @param array The entity fields obtained by reflection
             * @return array The data
             */
            $data = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.nodata', $data, $entity, $fields);
        } elseif (count($data) > 1) {
            /*#
             * The plugin obtained more than one row from get while producing a form for entity update. Allow this to be corrected
             *
             * @param array The data
             * @param string The entity being updated
             * @param array The entity fields obtained by reflection
             * @return array The data
             */
            $data = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.nodata', $data, $entity, $fields);
        }
        if (count($data) == 0) throw new \Exception('No data read from entity when creating update form');
        if (count($data) > 1) throw new \Exception('More than one row read from entity when creating update form');
        $data = $data[0];
        
        $form = new form\Form($formAction, 'Update', '');
        foreach($fields as $id => $field) if ($field['show']) {
            $form->addMember($input = new form\Input_Text($id));
            $input->setLabel($field['ui name']);
            $input->setValue($data[$id]);
        }
        $form->addMember($input = new form\Input_Button('update'));
        $input->setLabel('Update');

        /*#
         * The plugin has just created a form for REST update. Allow it to be mangled
         *
         * @param \com\sergiosgc\form\Form The form
         * @param string The entity being created
         * @return \com\sergiosgc\form\Form The form
         */
        $form = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form', $form, $entity);
        $serializer = new \com\sergiosgc\form\Serializer_TwitterBootstrap();
        $serializer->setLayout('horizontal');

        /*#
         * The plugin is about to serialize the REST update form. Allow the serializer to be mangled
         *
         * @param \com\sergiosgc\form\Form_Serializer The form serializer
         * @param string The entity being created
         * @return \com\sergiosgc\form\Form_Serializer The form serializer
         */
        $serializer = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.serializer', $serializer, $entity);
        $serialized = $serializer->serialize($form);
        /*#
         * The plugin is about to output the update form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        $serialized = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update_form.html', $serialized);
        if ($serialized == '' || is_null($serialized)) return;
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        /*#
         * The plugin is producing an update form. Allow output to be produced before the form
         *
         * @param string The entity being read
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update.preoutput', $entity);
        echo $serialized;
        /*#
         * The plugin is producing an update form. Allow output to be produced before the form
         *
         * @param string The entity being read
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update.postoutput', $entity);
    }/*}}}*/
    public function storeCreateResult($result, $entity) {/*{{{*/
        $this->lastObservedRestOperation = 'create';
        return $result;
    }/*}}}*/
    public function storeReadResult($result, $entity) {/*{{{*/
        $this->lastObservedRestOperation = 'read';
        return $result;
    }/*}}}*/
    public function storeUpdateResult($result, $entity) {/*{{{*/
        $this->lastObservedRestOperation = 'update';
        return $result;
    }/*}}}*/
    public function storeDeleteResult($result, $entity) {/*{{{*/
        $this->lastObservedRestOperation = 'delete';
        return $result;
    }/*}}}*/
    public function outputResult($result, $entity) {/*{{{*/
        switch ($this->lastObservedRestOperation) {
            case 'create': return $this->redirectAfterCreate($result, $entity);
            case 'read': return $this->read($result, $entity);
            case 'update': return $this->redirectAfterUpdate($result, $entity);
            case 'delete': return $this->redirectAfterDelete($result, $entity);
        }
        return $result;
    }/*}}}*/
    public function redirectAfterCreate($result, $entity) {/*{{{*/
        if ($result) { // Create was successful
            $url = \com\sergiosgc\Rest::getInstance()->getUrlFromEntity($entity);
            /*#
             * The plugin is redirecting the user after a successful create. Allow the destination to be filtered
             *
             * @param string Destination url
             * @param string Entity being processed
             * @result string Destination url or null to cancel the redirect
             */
            $url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create', $url, $entity);
            if (is_null($url)) return $result;
            header('Location: ' . $url);
            exit;
        }
        return $result;
    }/*}}}*/
    public function read($result, $entity) {/*{{{*/
        if (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) return $result;
        $rows = $result;
        $result = new \com\sergiosgc\RestNoData();
        /*#
         * The plugin is about to answer a read request
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string The entity being read
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') {
            print $result;
            return;
        }
        $reflection = \com\sergiosgc\db\Reflection::create(\com\sergiosgc\Facility::get('db'));
        $table = \com\sergiosgc\Rest::getInstance()->getTableFromEntity($entity);
        $fields = $reflection->getFields($table);
        foreach(array_keys($fields) as $field) $fields[$field]['primaryKey'] = false;
        foreach($reflection->getPrimaryKeys($table) as $key) $fields[$key]['primaryKey'] = true;
        foreach(array_keys($fields) as $field) {
            $fields[$field]['show'] = true;
            if ($fields[$field]['primaryKey'] && !is_null($fields[$field]['default'] )) $fields[$field]['show'] = false;
            $fields[$field]['ui name'] = $fields[$field]['name'];
        }
        /*#
         * The plugin is producing a table listing an entity (read). Allow field information to be manipulated
         *
         * @param array Field metadata
         * @param string The entity being read
         * @return array Field metadata
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.metadata', $fields, $entity);
        $table = new \com\sergiosgc\ui\Table();
        foreach($rows as $row) $table->addRow($row);
        /*#
         * The plugin is producing a table listing an entity (read). Allow the result table to be manipulated
         *
         * @param \com\sergiosgc\ui\Table Data table
         * @param string The entity being read
         * @return \com\sergiosgc\ui\Table Data table
         */
        $table = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.table', $table, $entity);
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        /*#
         * The plugin is producing a table listing an entity (read). Allow output to be produced before the table listing
         *
         * @param string The entity being read
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.preoutput', $entity);
        $table->output();
        /*#
         * The plugin is producing a table listing an entity (read). Allow output to be produced after the table listing
         *
         * @param string The entity being read
         */
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.postoutput', $entity);
        return $rows;
    }/*}}}*/
    public function redirectAfterUpdate($result, $entity) {/*{{{*/
        if ($result) { // Create was successful
            $url = \com\sergiosgc\Rest::getInstance()->getUrlFromEntity($entity);
            /*#
             * The plugin is redirecting the user after a successful update. Allow the destination to be filtered
             *
             * @param string Destination url
             * @param string Entity being processed
             * @result string Destination url or null to cancel the redirect
             */
            $url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update', $url, $entity);
            if (is_null($url)) return $result;
            header('Location: ' . $url);
            exit;
        }
        return $result;
    }/*}}}*/
    public function redirectAfterDelete($result, $entity) {/*{{{*/
        if (is_int($result)) { // Delete was successful
            $url = \com\sergiosgc\Rest::getInstance()->getUrlFromEntity($entity);
            /*#
             * The plugin is redirecting the user after a successful create. Allow the destination to be filtered
             *
             * @param string Destination url
             * @param string Entity being processed
             * @result string Destination url or null to cancel the redirect
             */
            $url = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.delete', $url, $entity);
            if (is_null($url)) return $result;
            header('Location: ' . $url);
            exit;
        }
        return $result;
    }/*}}}*/
}
Html::getInstance();
/*#
 * CRUD request handler that sits on top of com.sergiosgc.rest
 *
 * Request handler that implements Create/Read/Update/Delete operations for entities
 * available via the REST interface implemented by com.sergiosgc.rest
 *
 * # Usage summary 
 *
 * Drop this plugin in your plugin directory. It will attach itself to registrations
 * of REST entities as defined in `com.sergiosgc.rest` and will produce URLs for 
 * CRUD operations that emit HTML.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
