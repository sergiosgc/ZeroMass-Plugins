<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Rest {
    protected static $singleton = null;
    protected $baseURL = '/rest/';
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
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.replaced_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        \com\sergiosgc\Facility::register('REST', $this);
    }/*}}}*/
    public function config() {/*{{{*/
        $this->baseURL = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rest.baseurl', false, $this->baseURL);
        /*#
         * Filter the base URL. URLs answered by the REST class will be of the form baseurl/entity_name
         *
         * @param string The base URL
         * @return string The base URL
         */
        $this->baseURL = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.baseurl', $this->baseURL);
    }/*}}}*/
    public function registerEntity($urlName, $dbTable) {/*{{{*/
        $toFilter = array($urlName, $dbTable);

        /*#
         * Allow the entity being registered to be filtered
         *
         * @param array The entity, as two strings, urlName on index 0 and dbTable on index 1
         * @return array The entity, as two strings, urlName on index 0 and dbTable on index 1, or null to abort registration
         */
        $toFilter = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.registerEntity', $toFilter);
        if (is_null($toFilter)) return;
        $urlName = $toFilter[0];
        $dbTable = $toFilter[1];
        $this->entities[$urlName] = $dbTable;
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;
        if ($this->baseURL != substr($_SERVER['REQUEST_URI'], 0, strlen($this->baseURL))) return $handled;
        $requestedEntity = substr($_SERVER['REQUEST_URI'], strlen($this->baseURL));
        $requestedEntity = preg_replace('_([^/]*).*_', '\1', $requestedEntity);
        if (!is_string($requestedEntity) || 0 == strlen($requestedEntity)) return $handled;
        if ($requestedEntity[strlen($requestedEntity) - 1] == '/') $requestedEntity = substr($requestedEntity, 0, strlen($requestedEntity) - 1);
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
        if (!isset($this->entities[$requestedEntity])) return $handled;

        switch ($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            $result = $this->create($requestedEntity);
            break;
        case 'GET':
            $result = $this->read($requestedEntity);
            break;
        case 'POST':
        case 'PATCH':
            $result = $this->update($requestedEntity);
            break;
        case 'DELETE':
            $result = $this->delete($requestedEntity);
            break;
        default:
            return $handled;
        }
        header('Content-type: application/json');
        /*#
         * The plugin has the result for a REST request. Allow it to be filtered
         *
         * @param mixed The result as a PHP native type
         * @return mixed The result as a PHP native type
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.requestDone.raw', $result);
        $result = json_encode($result);
        /*#
         * The plugin has the result for a REST request. Allow it to be filtered, after being encoded
         *
         * @param string The result as JSON
         * @return string The result as JSON
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.requestDone.json', $result);

        print($result);

        return true;
    }/*}}}*/
    public function create($entity) {/*{{{*/
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

        $table = $this->entities[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        /*#
         * The plugin is answering a REST create (PUT) request. Allow the list of fields to insert to be filtered
         *
         * @param array Associative array of fields to be inserted
         * @param string Entity being processed
         * @param return Associative array of fields to be inserted
         */
        $fields = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.create.fields', $_REQUEST, $entity );
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
    public function read($entity) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST read (GET) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.read.pre', $result);
        if (!is_object($result) || get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entities[$entity];
        $db = \com\sergiosgc\Facility::get('db');
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
    public function update($entity) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST update (POST or PATCH) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.update.pre', $result);
        if (get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entities[$entity];
        $db = \com\sergiosgc\Facility::get('db');
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

        $toUpdateArray = array();
        parse_str(file_get_contents("php://input"), $toUpdateArray);
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
    public function delete($entity) {/*{{{*/
        $result = new RestNoData();
        /*#
         * The plugin is about to answer a REST delete (DELETE) request. Allow it to be short-circuited
         *
         * To prevent execution, and return a value immediately, capture this hook and return the value to use as result.
         * This hook may, of course also be used to change the superglobals, affecting the request
         *
         * @param mixed The result. 
         * @return mixed The result. An instance of RestNoData causes execution to continue
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete.pre', $result);
        if (get_class($result) != 'com\sergiosgc\RestNoData') return $result;

        $table = $this->entities[$entity];
        $db = \com\sergiosgc\Facility::get('db');
        /*#
         * The plugin is answering a REST delete (DELETE) request. Allow the SQL WHERE clause to be filtered
         *
         * The SQL where clause is represented by an array with two elements:
         *
         *  - A parameterized string to be included in the SQL statement as a WHERE clause, with ? as argument placeholders
         *  - An array of arguments to be passed on to DB::fetchAll
         *
         * @param array The where clause
         * @return array The where clause
         */
        $where = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete.where', $this->buildWhereClause());
        $args = $where[1];
        $where = $where[0];
        
        array_unshift($args, sprintf('DELETE FROM %s%s', $table, $where));
        $result = call_user_func_array(array($db, 'query'), $args);
        /*#
         * The plugin has the result for a REST delete (DELETE) request. Allow it to be filtered
         *
         * @param array The result
         * @return array The result
         */
        $result = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rest.delete', $result);
        return $result;
    }/*}}}*/
    protected function buildWhereClause() {/*{{{*/
        $db = \com\sergiosgc\Facility::get('db');
        $where = '';
        $args = array();
        $separator = ' WHERE ';
        foreach ($_GET as $key => $value) {
            $where .= $separator . $db->quoteColumn($key) . ' = ?';
            $separator = ' AND ';
            $args[] = $value;
        }
        return array($where, $args);
    }/*}}}*/
}
class RestNoData { }

class RestException extends \Exception { }
class KeyNotFoundException extends RestException { }

Rest::getInstance();

/*#
 * REST request handler
 *
 * Request handler that implements Representational state transfer on top of the DB facility
 *
 * # Usage summary 
 *
 * Drop this plugin in your plugin directory. It will, by default, pickup requests under /rest/_entity_/. Register entities
 * and associated tables using `registerEntity`. It will, from then on, implement REST.
 *
 * This plugin answers the four CRUD operations under five HTTP request methods:
 *
 *  - **C**reate under the `PUT` method
 *  - **R**ead under the `GET` method
 *  - **U**pdate under the `PATCH` and `POST` methods
 *  - **D**elete under the `DELETE` method
 *
 * The plugin needs to associate database tables with request entities. Do so, using `Rest::registerEntity`. For example, to 
 * associate the entity `person` with database table `user`, call:
 *
 *     Rest::getInstance()->registerEntity('person', 'user');
 *
 * Then, the plugin answers requests for that table under `/rest/person/`. It will use arguments passed on the URL as 
 * row selectors. For example, a `GET` on `/rest/person/?name=John` will return all rows of table `user` with column `name` 
 * equal to `John`.
 *
 * For requests that require data to write, the data must be passed in the request body. Using _curl_ to exemplify, this request:
 *
 *     curl -i -H "Accept: application/json" -X PUT -d "name=Mary" http://example.com/rest/person/
 *
 * will create a new row on table user, with the column `name` set to `Mary`.
 *
 * Requests may require selectors and data to write, such as PATCH requests. This request:
 *
 *     curl -i -H "Accept: application/json" -X PATCH -d "name=Mary%20Jane" http://example.com/rest/person/?name=Mary
 *
 * will update all rows of the table `user` where the name equals `Mary` setting the name to `Mary Jane`.
 *
 * The plugin returns results as JSON. 
 *
 * The default behaviour is sensible, but even so most of the plugin behaviour can be changed via ZeroMass hooks to accommodate special needs
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
