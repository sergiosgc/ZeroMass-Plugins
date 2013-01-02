<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class DB {
    protected static $singleton = null;
    protected $config = null;
    protected $connection = null;
    protected $driver = null;
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
        if ($preparedStatement === false) throw new DBQueryException($this->connection, $query, $args);
        if ($preparedStatement->execute($args) === false) throw new DBQueryException($preparedStatement, $query, $args);

        return $preparedStatement;
    }/*}}}*/
    public function connect() {/*{{{*/
        if (isset($this->connection) && !is_null($this->connection)) return;
        $driverDSN = array(
            'cubrid' => array(
                'dsnDriverName' => 'cubrid',
                'host' => array(),
                'port' => array('default' => '33000'),
                'dbname' => array(),
                'username' => array('default' => ''),
                'password' => array('default' => '')
            ),
            'sybase' => array(
                'dsnDriverName' => 'sybase',
                'host' => array(),
                'dbname' => array(),
                'appname' => array('default' => 'PHP ZeroMass'),
                'charset' => array('default' => 'UTF-8'),
                'username' => array(),
                'password' => array()
            ),
            'mssql' => array(
                'dsnDriverName' => 'mssql',
                'host' => array(),
                'dbname' => array(),
                'appname' => array('default' => 'PHP ZeroMass'),
                'charset' => array('default' => 'UTF-8'),
                'username' => array(),
                'password' => array()
            ),
            'dblib' => array(
                'dsnDriverName' => 'dblib',
                'host' => array(),
                'dbname' => array(),
                'appname' => array('default' => 'PHP ZeroMass'),
                'charset' => array('default' => 'UTF-8'),
                'username' => array(),
                'password' => array()
            ),
            'sqlsrv' => array(
                'dsnDriverName' => 'sqlsrv',
                'APP' => array('default' => 'PHP ZeroMass'),
                'ConnectionPooling' => array('default' => '1'),
                'Database' => array(),
                'Encrypt' => array('default' => '0'),
                'Server' => array(),
                'TrustServerCertificate' => array('default' => '0'),
                'username' => array(),
                'password' => array()
            ),
            'firebird' => array(
                'dsnDriverName' => 'firebird',
                'dbname' => array(),
                'charset' => array('default' => 'UTF-8'),
                'role' => array(),
                'username' => array(),
                'password' => array()
            ),
            'ibm' => array(
                'dsnDriverName' => 'ibm',
                'host' => array(),
                'port' => array('default' => '3700'),
                'database' => array(),
                'username' => array(),
                'password' => array()
            ),
            'informix' => array(
                'dsnDriverName' => 'informix',
                'DSN' => array(),
            ),
            'oci' => array(
                'dsnDriverName' => 'oci',
                'dbname' => array(),
                'charset' => array('default' => 'UTF-8'),
            ),
            'odbc' => array(
                'dsnDriverName' => 'odbc',
                'DSN' => array(),
            ),
            'sqlite' => array(
                'dsnDriverName' => 'sqlite',
                'memory' => array('default' => '0'),
                'path' => array('default' => '')
            ),
            'mysql' => array(
                'dsnDriverName' => 'mysql',
                'host' => array(),
                'port' => array('default' => '3306'),
                'dbname' => array(),
                'username' => array(),
                'password' => array()
            ),
            'pgsql' => array(
                'dsnDriverName' => 'pgsql',
                'host' => array(),
                'port' => array('default' => '5432'),
                'dbname' => array(),
                'username' => array(),
                'password' => array('default' => '')
            ),
            '4D' => array(
                'dsnDriverName' => 'pgsql',
                'host' => array(),
                'port' => array('default' => '1919'),
                'dbname' => array(),
                'charset' => array('default' => 'UTF-8'),
                'username' => array(),
                'password' => array('default' => '')
            ),
        );

        $config = \com\sergiosgc\Facility::get('config');
        $driver = $config->get('DB.driver');
        if (!array_key_exists($driver, $driverDSN)) throw new DBException(sprintf('Unknown database driver: %s', $driver));
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
        if ($driver == 'sqlite') {
            if ($driverParams['path'] == '' && $driverParams['memory'] == '0') throw new DBMissingConfigurationKeyException('path');
            if ($driverParams['memory'] == '0') {
                $dsn = 'sqlite::memory:';
            } else {
                $dsn = 'sqlite:' . $driverParams['path'];
            }
        }
        $this->driver = $driver;
        $this->connection = new \PDO($dsn, $username, $password);
    }/*}}}*/
}

class DBException extends \Exception { }
class DBQueryException extends DBException {/*{{{*/
    public $driverErrorCode;
    public function __construct($connectionOrStatement, $query, $args) {
        $error = $connectionOrStatement->errorInfo();
        parent::__construct($error[2], $error[1]);
        ob_start();
?>
Error executing SQL query:
<dl>
<dt>Message</dt><dd><?php echo $error[2] ?></dd>
<dt>ANSI SQL error code</dt><dd><?php echo $error[0] ?></dd>
<dt>Driver error code</dt><dd><?php echo $error[1] ?></dd>
<dt>Query</dt>
<dd><?php echo htmlspecialchars($query); ?></dd>
<dt>Parameters</dt>
<dd><ul><?php foreach ($args as $arg) printf('<li>%s (%s)</li>', htmlspecialchars($arg), gettype($arg)); ?></ul></dd>
</dl>
<?php
        $this->htmlMessage = ob_get_clean();
    }
    public function getHtmlMessage() { return $this->htmlMessage; }
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
