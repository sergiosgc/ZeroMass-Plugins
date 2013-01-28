<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rest;
use com\sergiosgc\form;

class Html {
    protected static $singleton = null;
    protected $baseURL = '/';
    protected $entities = array();
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.registerEntity', array($this, 'registerEntity'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'));
        \com\sergiosgc\form\Form::registerAutoloader();
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
    }/*}}}*/
    public function config() {/*{{{*/
        $this->baseURL = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rest.html.baseurl', false, $this->baseURL);
        /*#
         * Filter the base URL. URLs answered by the REST class will be of the form baseurl/entity_name
         *
         * @param string The base URL
         * @return string The base URL
         */
        $this->baseURL = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.baseurl', $this->baseURL);
    }/*}}}*/
    public function registerEntity($entity) {/*{{{*/
        /*#
         * An entity has been registered with com.sergiosgc.Rest and is now being registered by com.sergiosgc.rest.html. Allow it to be filtered
         *
         * If you wish to prevent registration of the entity with com.sergiosgc.rest.html, capture the hook and return null
         *
         * @param array The entity being registered
         * @return array The entity being registered, or null if registration should not happen
         */
        $toRegister = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.registerEntity', $entity);
        if (is_array($toRegister) && count($toRegister) == 2) $this->entities[$toRegister[0]] = $toRegister[1];

        return $entity;
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        if ($this->baseURL != substr($_SERVER['REQUEST_URI'], 0, strlen($this->baseURL))) return $handled;
        $requestedEntity = substr($_SERVER['REQUEST_URI'], strlen($this->baseURL));
        $requestedEntity = preg_replace('_([^/]*).*_', '\1', $requestedEntity);
        if (!is_string($requestedEntity) || 0 == strlen($requestedEntity)) return $handled;
        if (!isset($this->entities[$requestedEntity])) return $handled;

        $action = substr($_SERVER['REQUEST_URI'], strlen($this->baseURL) + strlen($requestedEntity) + 1);
        if ($action) $action = preg_replace('_([^/]*).*_', '\1', $action);
        switch ($action) {
            case 'new':
                $this->create($requestedEntity);
                break;
            case 'update':
                $this->update($requestedEntity);
                break;
            case 'delete':
                $this->delete($requestedEntity);
                break;
            default:
                $this->read($requestedEntity);
                break;
        }

        return true;
    }/*}}}*/
    public function create($entity) {/*{{{*/
        if ($_SERVER['REQUEST_METHOD'] == 'GET') return $this->create_form($entity);
        if (\com\sergiosgc\Rest::getInstance()->create($entity)) {
            $redirectUrl = $this->baseURL . $entity . '/';
            /*#
             * The plugin has just executed a create request. Allow the redirect URL to be manipulated.
             *
             * @param string The redirect URL
             * @param string The entity being created
             * @return string The redirect URL
             */
            $redirectUrl = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create.redirect', $redirectUrl, $entity);
            if ($redirectUrl) {
                header('Location: ' . $redirectUrl);
            }
        } else {
            throw RestException('Rest::create did not return true. Unexpected result');
        }
    }/*}}}*/
    public function create_form($entity) {/*{{{*/
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
        $table = $this->entities[$entity];
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
        $formAction = $this->baseURL . $entity . '/new/';
        /*#
         * The plugin is producing a form for entity creation. Allow the form action URL to be manipulated
         *
         * @param string The form action url
         * @param string The entity being created
         * @return string The form action url
         */
        $formAction = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.formaction', $formAction, $entity);

        $form = new form\Form($formAction, 'Create', '');
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
         * The plugin is about to output the login form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        $serialized = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.html', $serialized);
        if ($serialized == '' || is_null($serialized)) return;
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        echo $serialized;
    }/*}}}*/
    public function read($entity) {/*{{{*/
        $result = new \com\sergiosgc\RestNoData();
        /*#
         * The plugin is about to answer a read request
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string The entity being created
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') {
            print $result;
            return;
        }
        $reflection = \com\sergiosgc\db\Reflection::create(\com\sergiosgc\Facility::get('db'));
        $table = $this->entities[$entity];
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
         * @return array Field metadata
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.read.metadata', $fields);
        $rows = \com\sergiosgc\Rest::getInstance()->read($entity);
        $table = new \com\sergiosgc\ui\Table();
        foreach($rows as $row) $table->addRow($row);
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        $table->output();
    }/*}}}*/
    public function delete($entity) {/*{{{*/
        $result = new \com\sergiosgc\RestNoData();
        /*#
         * The plugin is about to answer a delete request
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string The entity being created
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.delete.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') {
            print $result;
            return;
        }
        \com\sergiosgc\Rest::getInstance()->delete($entity);
        $redirectUrl = $this->baseURL . $entity . '/';
        /*#
         * The plugin has just executed a delete request. Allow the redirect URL to be manipulated.
         *
         * @param string The redirect URL
         * @param string The entity being deleted
         * @return string The redirect URL
         */
        $redirectUrl = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.delete.redirect', $redirectUrl, $entity);
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
        }


    }/*}}}*/
    public function update($entity) {/*{{{*/
        if ($_SERVER['REQUEST_METHOD'] == 'GET') return $this->update_form($entity);
        if (\com\sergiosgc\Rest::getInstance()->update($entity)) {
            $redirectUrl = $this->baseURL . $entity . '/';
            /*#
             * The plugin has just executed a create request. Allow the redirect URL to be manipulated.
             *
             * @param string The redirect URL
             * @param string The entity being updated
             * @return string The redirect URL
             */
            $redirectUrl = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.update.redirect', $redirectUrl, $entity);
            if ($redirectUrl) {
                header('Location: ' . $redirectUrl);
            }
        } else {
            throw RestException('Rest::create did not return true. Unexpected result');
        }
    }/*}}}*/
    public function update_form($entity) {/*{{{*/
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
        $table = $this->entities[$entity];
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
        $formAction = $this->baseURL . $entity . '/update/';
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
        if (count($data) == 0) throw new Exception('No data read from entity when creating update form');
        if (count($data) > 1) throw new Exception('More than one row read from entity when creating update form');
        $data = $data[0];
        
        $form = new form\Form($formAction, 'Create', '');
        foreach($fields as $id => $field) if ($field['show']) {
            $form->addMember($input = new form\Input_Text($id));
            $input->setLabel($field['ui name']);
            $input->setValue($data[$id]);
        }
        $form->addMember($input = new form\Input_Button('update'));
        $input->setLabel('Update');

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
         * The plugin is about to output the login form. Allow the html to be mangled
         *
         * @param string The form
         * @return string The form
         */
        $serialized = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.html.create_form.html', $serialized);
        if ($serialized == '' || is_null($serialized)) return;
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');
        echo $serialized;
        return;
        \ZeroMass::getInstance()->do_callback('com.sergiosgc.contentType', 'text/html');

?>
<form class="<?php echo $formClass ?>" method="post" action="<?php echo $formAction; ?>">
<?php foreach($fields as $id => $field) if ($field['show']) { ?>
 <label for="<?php echo $id ?>"><?php echo $field['ui name'] ?></label>
 <input type="text" value="<?php if (isset($data[$id])) echo htmlspecialchars($data[$id]); ?>" name="<?php echo $id ?>">
<?php } ?>
<button class="<?php echo $submitButtonClass ?>" type="submit">Update</button>
</form>
<?php
    }/*}}}*/
}

class Exception extends \com\sergiosgc\RestException { }

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
