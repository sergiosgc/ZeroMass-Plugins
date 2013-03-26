<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Rest {
    protected static $singleton = null;
    protected $entityTableMap = array();
    protected $urlEntityMap = array();
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.replaced_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.create.fields', array($this, 'ignoreHttpMethod'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.read.where.fields', array($this, 'ignoreHttpMethod'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.fields', array($this, 'ignoreHttpMethod'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.where.fields', array($this, 'ignoreHttpMethod'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.delete.where.fields', array($this, 'ignoreHttpMethod'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('REST', $this);
    }/*}}}*/
    public function config() {/*{{{*/
        $keys = \com\sergiosgc\Facility::get('config')->getKeys('com.sergiosgc.rest.entityUrl');
        $entitites = array();
        foreach ($keys as $key) $entities[$key] = array('url' => \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rest.entityUrl.' . $key));
        foreach ($entities as $entity => $params) { 
            $entities[$entity]['dbTable'] = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rest.entityTable.' . $key, false, $entity);
        }
        foreach ($entities as $entity => $params) $this->registerEntity($entity, $params['url'], $params['dbTable']);
    }/*}}}*/
    public function registerEntity($entityName, $url = null, $dbTable = null) {/*{{{*/
        if (is_null($url)) $url = $entityName;
        if (is_null($dbTable)) $dbTable = $entityName;
        $toFilter = array(
            'entityName' => $entityName, 
            'url' => $url,
            'dbTable' => $dbTable);

        /*#
         * Allow the entity being registered to be filtered
         *
         * The filtered parameter is an associative array with three elements: entityName, url, dbTable matching the three parameters
         * to Rest::registerEntity
         *
         * @param array The entity as an associative array
         * @return array The entity as an associative array or null to abort registration
         */
        $toFilter = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.registerEntity', $toFilter);
        if (is_null($toFilter)) return;
        $entityName = $toFilter['entityName'];
        $url = $toFilter['url'];
        $dbTable = $toFilter['dbTable'];
        unset($toFilter);
        $this->entityTableMap[$entityName] = $dbTable;
        $this->urlEntityMap[$url] = $entityName;
    }/*}}}*/
    public function ignoreHttpMethod($fields) {/*{{{*/
        unset($fields['com_sergiosgc_rest_httpmethod']);
        return $fields;
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        $url = $_SERVER['REQUEST_URI'];
        if ('?' == $url[strlen($url) - strlen($_SERVER['QUERY_STRING']) - 1]) $url = substr($url, 0, strlen($url) - strlen($_SERVER['QUERY_STRING']) - 1);
        if (!isset($this->urlEntityMap[$url])) return $handled;

        $requestedEntity = $this->urlEntityMap[$url];
        /*#
         * The plugin is about to answer a REST request. Allow it to be filtered
         *
         * Note that plugins may change the requested entity in the filter, as well as the request via PHP superglobals
         *
         * @param string Target entity for the request
         * @return string Target entity for the request. Null will abort the request
         */
        $requestedEntity = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.request', $requestedEntity);
        if (is_null($requestedEntity) || $requestedEntity === false) return $handled;
        if (!isset($this->entityTableMap[$requestedEntity])) return $handled;
        $method = $_SERVER['REQUEST_METHOD'];
        if (isset($_REQUEST['com_sergiosgc_rest_httpmethod'])) $method = $_REQUEST['com_sergiosgc_rest_httpmethod'];
        switch ($method) {
        case 'PUT':
            $type = 'create';
            $result = $this->create($requestedEntity);
            break;
        case 'GET':
            $type = 'read';
            $result = $this->read($requestedEntity);
            break;
        case 'POST':
        case 'PATCH':
            $type = 'update';
            $result = $this->update($requestedEntity);
            break;
        case 'DELETE':
            $type = 'delete';
            $result = $this->delete($requestedEntity);
            break;
        default:
            return $handled;
        }
        /*#
         * The plugin has the result for a REST request. Allow it to be filtered
         *
         * @param mixed The result as a PHP native type
         * @param string Entity being processed
         * @param string Type of request. One of create,read,update,delete
         * @return mixed The result as a PHP native type
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.requestDone.raw', $result, $requestedEntity, $type);

        return true;
    }/*}}}*/
    public function create($entity, $fields = null) {/*{{{*/
        parse_str(file_get_contents("php://input"), $_REQUEST);
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST create (PUT) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string Entity being processed
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.create.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entityTableMap[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        if (is_null($fields)) $fields = $_REQUEST;
        /*#
         * The plugin is answering a REST create (PUT) request. Allow the list of fields to insert to be filtered
         *
         * @param array Associative array of fields to be inserted
         * @param string Entity being processed
         * @param return Associative array of fields to be inserted
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.create.fields', $fields, $entity );
        $db->insert($table, $fields);
        $result = true;

        /*#
         * The plugin has the result for a REST create (PUT) request. Allow it to be filtered
         *
         * @param array The result
         * @param string Entity being processed
         * @return array The result
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.create', $result, $entity);
        return $result;
    }/*}}}*/
    public function read($entity, $fields = null) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST read (GET) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string Entity being processed
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.read.pre', $result, $entity);
        if (!is_object($result) || get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entityTableMap[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        if (is_null($fields)) $fields = $_GET;
        /*#
         * The plugin is answering a REST read (GET) request. Allow the fields for creating the WHERE clause to be filtered
         *
         * @param array The fields
         * @param string Entity being processed
         * @return array fields
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.read.where.fields', $fields, $entity);
        /*#
         * The plugin is answering a REST read (GET) request. Allow the SQL WHERE clause to be filtered
         *
         * The SQL where clause is represented by an array with two elements:
         *
         *  - A parameterized string to be included in the SQL statement as a WHERE clause, with ? as argument placeholders
         *  - An array of arguments to be passed on to DB::fetchAll
         *
         * @param array The where clause
         * @param string Entity being processed
         * @return array The where clause
         */
        $where = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.read.where', $this->buildWhereClause($fields), $entity);
        $args = $where[1];
        $where = $where[0];
        
        array_unshift($args, sprintf('SELECT * FROM %s%s', $db->quoteColumn($table), $where));
        $result = call_user_func_array(array($db, 'fetchAll'), $args);
        /*#
         * The plugin has the result for a REST read (GET) request. Allow it to be filtered
         *
         * @param array The result
         * @param string Entity being processed
         * @return array The result
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.read', $result, $entity);
        return $result;
    }/*}}}*/
    public function update($entity, $queryFields = null, $updateFields = null) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST update (POST or PATCH) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string Entity being processed
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entityTableMap[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        $fields = is_null($queryFields) ? $_GET : $queryFields;
        /*#
         * The plugin is answering a REST update (POST or PATCH) request. Allow the fields for creating the WHERE clause to be filtered
         *
         * @param array The fields
         * @param string Entity being processed
         * @return array fields
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.where.fields', $fields, $entity);
        /*#
         * The plugin is answering a REST update (POST or PATCH) request. Allow the SQL WHERE clause to be filtered
         *
         * The SQL where clause is represented by an array with two elements:
         *
         *  - A parameterized string to be included in the SQL statement as a WHERE clause, with ? as argument placeholders
         *  - An array of arguments to be passed on to DB::fetchAll
         *
         * @param array The where clause
         * @param string Entity being processed
         * @return array The where clause
         */
        $where = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.where', $this->buildWhereClause($fields), $entity);
        $args = $where[1];
        $where = $where[0];

        if (is_null($updateFields)) {
            $toUpdateArray = array();
            parse_str(file_get_contents("php://input"), $toUpdateArray);
        } else {
            $toUpdateArray = $updateFields;
        }
        /*#
         * The plugin is answering a REST update (POST or PATCH) request. Allow the list of fields to update to be filtered
         *
         * @param array Associative array of fields to be updated
         * @param string Entity being processed
         * @param return Associative array of fields to be updated
         */
        $toUpdateArray = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.fields', $toUpdateArray, $entity );
        $setClause = '';
        $separator = ' SET ';
        foreach ($toUpdateArray as $key => $value) {
            $setClause .= $separator . $db->quoteColumn($key) . ' = ?';
            $separator = ', ';
        }
        $toUpdateArray = array_values($toUpdateArray);
        $setClause = array($setClause, $toUpdateArray);
        /*#
         * The plugin is answering a REST update (POST or PATCH) request. Allow the SQL SET clause to be filtered
         *
         * The SQL set clause is represented by an array with two elements:
         *  - A parameterized string to be included in the SQL statement as a WHERE clause, with ? as argument placeholders
         *  - An array of arguments to be passed on to DB::fetchAll
         *
         * @param array The set clause
         * @param string Entity being processed
         * @return array The set clause
         */
        $setClause = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.set', $setClause, $entity);
        $toUpdateArray = $setClause[1];
        $setClause = $setClause[0];

        $args = array_merge($toUpdateArray, $args);

        
        array_unshift($args, sprintf('UPDATE %s%s%s', $db->quoteColumn($table), $setClause, $where));
        $result = call_user_func_array(array($db, 'query'), $args);
        /*#
         * The plugin has the result for a REST update (POST or PATCH) request. Allow it to be filtered
         *
         * @param array The result
         * @param string Entity being processed
         * @return array The result
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update', $result, $entity);
        return $result;
    }/*}}}*/
    public function delete($entity, $fields = null) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST delete (DELETE) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @param string Entity being processed
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete.pre', $result, $entity);
        if (get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entityTableMap[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        if (is_null($fields)) $fields = $_GET;
        /*#
         * The plugin is answering a REST delete (DELETE) request. Allow the fields for creating the WHERE clause to be filtered
         *
         * @param array The fields
         * @param string Entity being processed
         * @return array fields
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete.where.fields', $fields, $entity);
        /*#
         * The plugin is answering a REST delete (DELETE) request. Allow the SQL WHERE clause to be filtered
         *
         * The SQL where clause is represented by an array with two elements:
         *
         *  - A parameterized string to be included in the SQL statement as a WHERE clause, with ? as argument placeholders
         *  - An array of arguments to be passed on to DB::fetchAll
         *
         * @param array The where clause
         * @param string Entity being processed
         * @return array The where clause
         */
        $where = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete.where', $this->buildWhereClause($fields), $entity);
        $args = $where[1];
        $where = $where[0];
        
        array_unshift($args, sprintf('DELETE FROM %s%s', $db->quoteColumn($table), $where));
        $result = call_user_func_array(array($db, 'query'), $args);
        /*#
         * The plugin has the result for a REST delete (DELETE) request. Allow it to be filtered
         *
         * @param array The result
         * @param string Entity being processed
         * @return array The result
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete', $result, $entity);
        return $result;
    }/*}}}*/
    protected function buildWhereClause($fields) {/*{{{*/
        $db = \com\sergiosgc\Facility::get('db');
        $where = '';
        $args = array();
        $separator = ' WHERE ';
        foreach ($fields as $key => $value) {
            $where .= $separator . $db->quoteColumn($key) . ' = ?';
            $separator = ' AND ';
            $args[] = $value;
        }
        return array($where, $args);
    }/*}}}*/
    public function getTableFromEntity($entity) {/*{{{*/
        if (!isset($this->entityTableMap[$entity])) throw new RestException('Unknown entity: ' . $entity);
        return $this->entityTableMap[$entity];
    }/*}}}*/
    public function getUrlFromEntity($entity) {/*{{{*/
        if (!in_array($entity, $this->urlEntityMap)) throw new RestException('Unknown entity: ' . $entity);
        foreach($this->urlEntityMap as $url => $candidate) if ($candidate == $entity) return $url;
    }/*}}}*/
    // public function registerFieldMangler($entity, $field, $callback, $mangleOnRead = false, $callOnMissingfield = false) /*{{{*/
    /**
     * Helper class to register a callback that changes a field on REST update and on REST create
     *
     * The callback signature is someFunction($field, $entity). It should return the (un)changed field.
     * The callback may return an instance of \com\sergiosgc\RestNoData to have the field removed from 
     * the request.
     *
     * If mangleOnRead is set to true, fields use for querying read operations are also filtered. This
     * is useful if the callback normalizes the field. Note that this affects read update and delete 
     * operations.
     *
     * If callOnMissingfield is set to true, when the field is missing on the request, the callback
     * is called with an instance of \com\sergiosgc\RestNoData as a placeholder for the field. 
     *
     * @param string Entity
     * @param mixed Field
     * @param callable Callback 
     * @param boolean True if the callback should be called even when the field is missing in the request (optional, defaults to false)
     * @return mixed The field value
     */
    public function registerFieldMangler($entity, $field, $callback, $mangleOnRead = false, $callOnMissingfield = false) {
        new RestFieldMangler($entity, $field, $callback, $mangleOnRead, $callOnMissingfield);
    }/*}}}*/
}
class RestNoData { }

class RestException extends \Exception { }
class KeyNotFoundException extends RestException { }

Rest::getInstance();

class RestFieldMangler {
    protected $entity;
    protected $field;
    protected $callback;
    protected $callOnMissingfield;
    public function __construct($entity, $field, $callback, $mangleOnRead = false, $callOnMissingfield = false) {/*{{{*/
        if (!is_callable($callback)) throw new \ZeroMassException('Non-callable passed as $callback argument');
        $this->entity = $entity;
        $this->field = $field;
        $this->callback = $callback;
        $this->callOnMissingfield = $callOnMissingfield;
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.fields', array($this, 'mangleField'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.create.fields', array($this, 'mangleField'));
        if ($mangleOnRead) {
            \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.read.where.fields', array($this, 'mangleField'));
            \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.update.where.fields', array($this, 'mangleField'));
            \ZeroMass::getInstance()->register_callback('com.sergiosgc.rest.delete.where.fields', array($this, 'mangleField'));
        }
    }/*}}}*/
    public function mangleField($fields, $entity) {/*{{{*/
        if ($entity != $this->entity) return $fields;
        if (isset($fields[$this->field])) {
            $fields[$this->field] = call_user_func($this->callback, $fields[$this->field], $entity);
            if (is_object($fields[$this->field]) && get_class( $fields[$this->field] ) == 'com\sergiosgc\RestNoData') unset($fields[$this->field]);
            return $fields;
        }
        if (!$this->callOnMissingfield) return $fields;
        $newVal = new RestNoData();
        $newVal = call_user_func($this->callback, $newVal, $entity);
        if (is_object($newVal) && get_class($newVal) != 'com\sergiosgc\RestNoData') {
            $fields[$this->field] = $newVal;
        }
        return $fields;
    }/*}}}*/
}


/*#
 * REST request handler
 *
 * Request handler that implements Representational state transfer on top of 
 * the DB facility
 *
 * # Usage summary 
 *
 * Drop this plugin in your plugin directory. It will, by default, pickup 
 * requests under /rest/_entity_/. Register entities and associated tables 
 * using `registerEntity`. It will, from then on, implement REST.
 *
 * This plugin answers the four CRUD operations under five HTTP request methods:
 *
 *  - **C**reate under the `PUT` method
 *  - **R**ead under the `GET` method
 *  - **U**pdate under the `PATCH` and `POST` methods
 *  - **D**elete under the `DELETE` method
 *
 * The plugin needs to associate database tables with request entities. Do so, 
 * using `Rest::registerEntity`. For example, to associate the entity `person` 
 * with database table `user` at URI /person/, call:
 *
 *     Rest::getInstance()->registerEntity('person', '/person/', 'user');
 *
 * Then, the plugin answers requests for that table under `/person/`. It will 
 * use arguments passed on the URL as row selectors. For example, a `GET` on 
 * `/person/?name=John` will return all rows of table `user` with column 
 * `name` equal to `John`.
 *
 * For requests that require data to write, the data must be passed in the 
 * request body. Using _curl_ to exemplify, this request:
 *
 *     curl -i -H "Accept: application/json" -X PUT -d "name=Mary" http://example.com/person/
 *
 * will create a new row on table user, with the column `name` set to `Mary`.
 *
 * Requests may require selectors and data to write, such as PATCH requests. 
 * This request:
 *
 *     curl -i -H "Accept: application/json" -X PATCH -d "name=Mary%20Jane" http://example.com/person/?name=Mary
 *
 * will update all rows of the table `user` where the name equals `Mary` 
 * setting the name to `Mary Jane`.
 *
 * The plugin outputs no result. This plugin should be coupled with appropriate
 * output plugins, such as com.sergiosgc.rest.json or com.sergiosgc.rest.html 
 * for output to be produced.
 *
 * The default behaviour is sensible, but even so most of the plugin behaviour
 * can be changed via ZeroMass hooks to accommodate special needs.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
