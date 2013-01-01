<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class DB {
    protected static $singleton = null;
    protected $config = null;
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'ready'));
    }/*}}}*/
    public function init() {/*{{{*/
    }/*}}}*/
    public function ready() {/*{{{*/
        \com\sergiosgc\Facility::register('db', $this);
    }/*}}}*/
    public function insert($query) {/*{{{*/
        $args = func_get_args();
        if (count($args) > 1 && strpos($query, '?') === false) {
            $table = $args[0];
            $valueArray = $args[1];
            $fieldString = '';
            $valueString = '';
            $separator = '';
            foreach(array_keys($valueArray) as $field) {
                $fieldString .= sprintf("%s%s", $separator, $field);
                $valueString .= $separator . '?';
                $separator = ',';
            }
            $query = sprintf('INSERT INTO %s(%s) VALUES(%s);', $table, $fieldString, $valueString);
            $args = array_values($valueArray);
            array_unshift($args, $query);
        }
        $cursor = call_user_func_array(array($this, 'doQuery'), $args);
        $cursor->closeCursor();
    }/*}}}*/
    public function query($query) {/*{{{*/
        $args = func_get_args();
        $cursor = call_user_func_array(array($this, 'doQuery'), $args);
        $cursor->closeCursor();
    }/*}}}*/
    public function fetchAll($query) {/*{{{*/
        $args = func_get_args();
        $cursor = call_user_func_array(array($this, 'doQuery'), $args);
        $result = $cursor->fetchAll(\PDO::FETCH_ASSOC);
        $cursor->closeCursor();
        return $result;
    }/*}}}*/
    public function fetchRow($query) {/*{{{*/
        $args = func_get_args();
        $cursor = call_user_func_array(array($this, 'doQuery'), $args);
        $result = $cursor->fetch(\PDO::FETCH_ASSOC);
        $cursor->closeCursor();
        if (!$result) return null;

        return $result;
    }/*}}}*/
    public function fetchColumn($query) {/*{{{*/
        $args = func_get_args();
        $cursor = call_user_func_array(array($this, 'doQuery'), $args);
        $result = $cursor->fetchAll(\PDO::FETCH_COLUMN, 0);
        $cursor->closeCursor();
        return $result;
    }/*}}}*/
    public function fetchValue($query) {/*{{{*/
        $args = func_get_args();
        $result = call_user_func_array(array($this, 'fetchRow'), $args);
        if (count($result) == 0) return null;
        $result = array_values($result);
        return $result[0];
    }/*}}}*/
    protected function doQuery($query) {/*{{{*/
        $args = func_get_args();
        array_shift($args);
        $this->connect($args);
        $preparedStatement = $this->connection->prepare($query);
        if ($preparedStatement === false) throw new DBQueryException($this->connection);
        if ($preparedStatement->execute($args) === false) throw new DBQueryException($preparedStatement);

        return $preparedStatement;
    }/*}}}*/
    public function connect() {/*{{{*/
        if (isset($this->connection) && !is_null($this->connection)) return;
        $driverDSN = array(
            'mysql' => array(
                'dsnDriverName' => 'mysql',
                'host' => array(),
                'port' => array('default' => '3306'),
                'dbname' => array(),
                'username' => array(),
                'password' => array()
            ),
        );

        $config = \com\sergiosgc\Facility::get('config');
        $driver = $config->get('DB.driver');
        if (!array_key_exists($driver, $driverDSN)) throw new DBException('Unknown database driver: %s', $driver);
        $driverParams = array();
        foreach($driverDSN[$driver] as $key => $params) {
            if ($key == 'dsnDriverName') continue;
            try {
                $driverParams[$key] = $config->get('DB.' . $key);
            } catch (\Exception $e) {
                if (isset($params['default'])) {
                    $driverParams[$key] = $params['default'];
                } elseif (!isset($params['optional'])) {
                    throw new DBMissingConfigurationKeyException($key, $e);
                }
            }
        }
        $username = isset($driverParams['username']) ? $driverParams['username'] : null;
        $password = isset($driverParams['password']) ? $driverParams['password'] : null;
        if (isset($driverParams['username'])) unset($driverParams['username']);
        if (isset($driverParams['password'])) unset($driverParams['password']);
        $dsn = $driverDSN[$driver]['dsnDriverName'] . ':';
        $separator = '';
        foreach($driverParams as $key => $value) {
            $dsn .= $separator . $key . '=' . $value;
            $separator = ';';
        }

        $this->connection = new \PDO($dsn, $username, $password);
    }/*}}}*/
}

class DBException extends \Exception { }
class DBQueryException extends DBException {/*{{{*/
    public $driverErrorCode;
    public function __construct($connectionOrStatement) {
        $error = $connectionOrStatement->errorInfo();
        parent::__construct($error[2], $error[0]);
        $this->driverErrorCode = $error[1];
    }
}/*}}}*/
class DBMissingConfigurationKeyException extends DBException { /*{{{*/
    public $key;
    public function __construct($key) {
        $args = func_get_args();
        array_shift($args);
        call_user_func_array(array('\com\sergiosgc\DBException', '__construct'), $args);
        $this->key = $key;
    }
}/*}}}*/

DB::getInstance();

/*#
 * Database plugin
 *
 * Database facility provider based on PDO with a simpler interface
 *
 * # Usage summary 
 *
 *
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
