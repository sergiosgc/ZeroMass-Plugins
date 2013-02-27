<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\db\reflection;

class Pgsql {
    protected $db = null;
    public function __construct(\com\sergiosgc\DB $db) {/*{{{*/
        $this->db = $db;
    }/*}}}*/
    public function getFields($table)/*{{{*/
    {
        if ($table[0] != '"') $table = '"' . $table . '"';
        $result = $this->db->fetchAll(<<<EOQ
select
 pg_attribute.attname as name,
 pg_type.typname as type,
 pg_attribute.atthasdef as default,
 pg_attribute.attnotnull as notnull
from
  pg_attribute
 join
  pg_class
 on (pg_class.oid = pg_attribute.attrelid)
 join
  pg_type
 on (pg_attribute.atttypid = pg_type.oid)
where
 attnum > 0 and
 not pg_attribute.attisdropped and
 pg_attribute.attrelid = ?
order by
 pg_attribute.attnum
EOQ
        , $this->getTableOID($table));
        foreach ($result as $index => $field) {
            $result[$index]['sql92 type'] = $this->nativeTypeToSQL92($result[$index]['type']);
        }
        $temp = $result;
        $result = array();
        foreach($temp as $field) $result[$field['name']] = $field;
        unset($temp);
        foreach ($result as $index => $field) {
            if (!$result[$index]['default']) {
                $result[$index]['default'] = null;
            } else {
                $result[$index]['default'] = $this->db->fetchValue(<<<EOQ
select
 pg_attrdef.adsrc
from
  pg_class
 join
  pg_attrdef
 on (pg_class.oid = pg_attrdef.adrelid)
 join
  pg_attribute
 on (pg_attrdef.adnum = pg_attribute.attnum)
where
 not pg_attribute.attisdropped and
 pg_class.oid = pg_attribute.attrelid and
 pg_attribute.attname = ? and
 pg_class.oid = ?
EOQ
                            , $field['name'], $this->getTableOID($table));
                /* fix for a postgresql bug where sequences get referred without namespace in the default source expression */
                if (preg_match('_^nextval\(\'([^\']*)\'::regclass\)$_', $result[$index]['default'], $matches) && // Looks like a sequence
                    !preg_match('_^(("[^"]*"\."[^"]*")|("[^"]*"\..*)|(.*\."[^"]*")|(.*\..*))$_', $matches[1])) // And seems to have no schema
                {

                    $result[$index]['default'] = sprintf("nextval('\"%s\".%s'::regclass)", $this->getNamespace($table), $matches[1]);
                }
            }
        }
        return $result;
    }/*}}}*/
    /* getPrimaryKeys {{{ */
    public function getPrimaryKeys($table)
    {
        if ($table[0] != '"') $table = '"' . $table . '"';
        $result = array();
        $currentKeyIndex = 0;
        do {
            $key = $this->db->fetchValue(<<<EOQ
select
 pg_attribute.attname as name
from
  pg_attribute  
 join
  pg_class
 on (pg_class.oid = pg_attribute.attrelid)
 join
  pg_index
 on (pg_class.oid = pg_index.indrelid)
where
 not pg_attribute.attisdropped and
 pg_attribute.attnum > 0 and
 pg_index.indisprimary and
 pg_class.oid = ? and
 pg_attribute.attnum = pg_index.indkey[?]
order by
 pg_attribute.attnum
EOQ
                , $this->getTableOID($table), $currentKeyIndex);
            if ($key != '') {
                $result[] = $key;
                $currentKeyIndex++;
            }
        } while ($key != '');
        $temp = $result;
        $result = array();
        foreach ($temp as $index => $name) {
            $result[$name] = $name;
        }
        return $result;
    }
    /* }}} */
    /* getNamespace {{{ */
    private function getNamespace($table)
    {
        return $this->db->fetchValue(<<<EOQ
SELECT
 pg_namespace.nspname as namespace
FROM
  pg_class
 JOIN
  pg_namespace ON (pg_class.relnamespace = pg_namespace.oid)
WHERE
 pg_class.oid = ?
EOQ
        , $this->getTableOID($table));
    }
    /* }}} */
    /*     getTableOID {{{ */
    /**
     *     getTableOID. Get Postgresql classoid for this table
     *
     * @return int Table OID
     */
    private function getTableOID($table)
    {
        static $oids = array();
        $parts = explode('.', $table);
        if (count($parts) != 2) {
            $schemas = $this->db->fetchValue('SHOW search_path');
            $schemas = explode(',', $schemas);
            foreach (array_keys($schemas) as $i) if ($schemas[$i] == '"$user"') $schemas[$i] = $this->db->getUsername();
            $exception = null;
            foreach($schemas as $schema) {
                try {
                    return $this->getTableOID('"' . $schema . '".' . $table);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            }
            if ($exception) throw $exception;
        }
        if (array_key_exists($table, $oids)) return $oids[$table];
        $schema = explode('"', $parts[0]);
        if (count($schema) != 3) throw new Exception('Table name is not in the format "schema"."name"');
        $schema = $schema[1];
        $name = explode('"', $parts[1]);
        if (count($name) != 3) throw new Exception('Table name is not in the format "schema"."name"');
        $name = $name[1];
        $oids[$table] = $this->db->fetchValue(<<<EOQ
SELECT
 pg_class.oid
FROM
  pg_class
 JOIN
  pg_namespace ON (pg_namespace.oid = pg_class.relnamespace)
WHERE
  pg_class.relname = ?
 AND
  pg_namespace.nspname = ?
EOQ
        , $name, $schema);
        if (is_null($oids[$table])) {
            unset($oids[$table]);
            throw new NonExistantTableException('Unknown table: ' . $table);
        }
        return $oids[$table];
    }
    /* }}} */
    /* nativeTypeToSQL92 {{{ */
    /**
     * Convert native types to SQL92
     * 
     * The result is one of:
     *   'boolean', 'character', 'date', 'decimal','float','smallint','integer','interval','numeric','time','timestamp'
     *
     * @param string The native type
     * @return string The SQL92 type for the given native type
     */
    public static function nativeTypeToSQL92($type)
    {
        switch ($type) {
            case 'bool':
                return 'boolean';
            case 'char':
            case 'box':
            case 'varchar':
            case 'text':
                return 'character';
            case 'date':
                return 'date';
            case 'decimal':
            case 'money':
                return 'decimal';
            case 'float4':
            case 'float8':
                return 'float';
            case 'int2':
                return 'smallint';
            case 'oid':
            case 'xid':
            case 'int4':
            case 'int8':
                return 'integer';
            case 'interval':
                return 'interval';
            case 'numeric':
                return 'numeric';
            case 'time':
            case 'timetz':
                return 'time';
            case 'timestamp':
            case 'timestamptz':
                return 'timestamp';
            case '_bool':
                return 'boolean[]';
            case '_char':
            case '_box':
            case '_varchar':
            case '_text':
                return 'character[]';
            case '_date':
                return 'date[]';
            case '_decimal':
            case '_money':
                return 'decimal[]';
            case '_float4':
            case '_float8':
                return 'float[]';
            case '_int2':
                return 'smallint[]';
            case '_oid':
            case '_xid':
            case '_int4':
            case '_int8':
                return 'integer[]';
            case '_interval':
                return 'interval[]';
            case '_numeric':
                return 'numeric[]';
            case '_time':
            case '_timetz':
                return 'time[]';
            case '_timestamp':
            case '_timestamptz':
                return 'timestamp[]';
            default:
                throw new Exception('Unknown SQL92 type for native type \'' . $type . '\'');
        }

    }
    /* }}} */
}

class Exception extends \Exception { }
class NonExistantTableException extends Exception { }
