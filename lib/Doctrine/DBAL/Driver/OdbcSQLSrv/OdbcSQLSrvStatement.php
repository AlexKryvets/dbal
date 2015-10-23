<?php

namespace Doctrine\DBAL\Driver\OdbcSQLSrv;

use Doctrine\DBAL\DBALException;
use PDO;
use IteratorAggregate;
use Doctrine\DBAL\Driver\Statement;

class OdbcSQLSrvStatement implements IteratorAggregate, Statement
{
    /**
     * The SQLSRV Resource.
     *
     * @var resource
     */
    private $conn;

    /**
     * The SQL statement to execute.
     *
     * @var string
     */
    private $sql;

    /**
     * The SQLSRV statement resource.
     *
     * @var resource
     */
    private $stmt;

    /**
     * Parameters to bind.
     *
     * @var array
     */
    private $params = array();

    /**
     * Translations.
     *
     * @var array
     */
    private static $fetchMap = array(
        PDO::FETCH_ASSOC => PDO::FETCH_ASSOC,
    );

    /**
     * The fetch style.
     *
     * @param integer
     */
    private $defaultFetchMode = PDO::FETCH_ASSOC;

    /**
     * The last insert ID.
     *
     * @var \Doctrine\DBAL\Driver\SQLSrv\LastInsertId|null
     */
    private $lastInsertId;

    /**
     * @return \Doctrine\DBAL\Driver\SQLSrv\LastInsertId|null
     */
    public function getLastInsertId() {
        return $this->lastInsertId->getId();
    }

    /**
     * Append to any INSERT query to retrieve the last insert id.
     *
     * @var string
     */
    const LAST_INSERT_ID_SQL = ';SELECT SCOPE_IDENTITY() AS LastInsertId;';

    private $typeMapping;

    /**
     * @param resource     $conn
     * @param string       $sql
     * @param integer|null $lastInsertId
     */
    public function __construct($conn, $sql, $lastInsertId = null)
    {
        $this->conn = $conn;
        $this->sql = $sql;

        if (stripos($sql, 'INSERT INTO ') === 0) {
            //$this->sql .= self::LAST_INSERT_ID_SQL;
            $this->lastInsertId = $lastInsertId;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
        return $this->bindParam($param, $value, $type,null);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        if (!is_numeric($column)) {
            throw new SQLSrvException("sqlsrv does not support named parameters to queries, use question mark (?) placeholders instead.");
        }

        if ($type === \PDO::PARAM_LOB) {
            $this->params[$column-1] = array($variable, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'));
        } else if (is_string($variable)) {
            $this->params[$column-1] = iconv( 'utf-8', 'cp1251', $variable);
        } else {
            $this->params[$column-1] = $variable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        if ($this->stmt) {
            @odbc_free_result($this->stmt);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return sqlsrv_num_fields($this->stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if ($errors) {
            return $errors[0]['code'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return sqlsrv_errors(SQLSRV_ERR_ERRORS);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null)
    {
        if ($params) {
            $hasZeroIndex = array_key_exists(0, $params);
            foreach ($params as $key => $val) {
                $key = ($hasZeroIndex && is_numeric($key)) ? $key + 1 : $key;
                $this->bindValue($key, $val);
            }
        }

        if ($this->lastInsertId) {
            odbc_exec($this->conn, 'SET ARITHABORT ON');
        }

        $this->stmt = odbc_prepare($this->conn, $this->sql);
        if ( ! $this->stmt) {
            throw new DBALException("odbc_prepare failed.");
        }
        if (!odbc_execute($this->stmt, $this->params)) {
            throw new DBALException("odbc_execute failed.");
        }

        $this->initializeTypeMappings();

        if ($this->lastInsertId) {
            $this->closeCursor();
            $this->lastInsertId->setId((int)odbc_result(odbc_exec($this->conn, "select @@identity"), 1));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        $this->defaultFetchMode = $fetchMode;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $data = $this->fetchAll();

        return new \ArrayIterator($data);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null)
    {
        $fetchMode = $fetchMode ?: $this->defaultFetchMode;
        if (isset(self::$fetchMap[$fetchMode]) && self::$fetchMap[$fetchMode] == \PDO::FETCH_ASSOC) {
            if ($result = odbc_fetch_array($this->stmt)) {
                foreach ($result as $columnName => $value) {
                    $type = @$this->typeMapping[$columnName];
                    if ($type === 'varchar' && $value !== null) {
                        $result[$columnName] = iconv('cp1251', 'utf-8', $value);
                    }
                }
            }
            return $result;
        } else if ($fetchMode == PDO::FETCH_OBJ || $fetchMode == PDO::FETCH_CLASS) {
            throw new \Exception('TODO');
            $className = null;
            $ctorArgs = null;
            if (func_num_args() >= 2) {
                $args = func_get_args();
                $className = $args[1];
                $ctorArgs = (isset($args[2])) ? $args[2] : array();
            }
            return odbc_fetch_object($this->stmt, $className, $ctorArgs);
        }

        throw new DBALException("Fetch mode is not supported!");
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null)
    {
        $className = null;
        $ctorArgs = null;
        if (func_num_args() >= 2) {
            $args = func_get_args();
            $className = $args[1];
            $ctorArgs = (isset($args[2])) ? $args[2] : array();
        }

        $rows = array();
        while ($row = $this->fetch($fetchMode, $className, $ctorArgs)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = array_values($this->fetch(PDO::FETCH_ASSOC));

        return $row[$columnIndex];
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
        return odbc_num_rows($this->stmt);
    }

    private function initializeTypeMappings () {
        $fieldsNum = odbc_num_fields($this->stmt);
        $this->typeMapping = array();
        for ($i = 1; $i <= $fieldsNum; $i++) {
            $this->typeMapping[odbc_field_name($this->stmt, $i)] = odbc_field_type($this->stmt, $i);
        }
    }
}
