<?php

namespace Doctrine\DBAL\Driver\OdbcSQLSrv;

use Doctrine\DBAL\Driver\SQLSrv\LastInsertId;

class OdbcSQLSrvConnection implements \Doctrine\DBAL\Driver\Connection
{
    /**
     * @var resource
     */
    protected $conn;

    /**
     * @var \Doctrine\DBAL\Driver\SQLSrv\LastInsertId
     */
    protected $lastInsertId;

    /**
     * @param $dsn
     * @param $username
     * @param $password
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct($dsn, $username, $password)
    {
        $this->conn = odbc_connect($dsn, $username, $password);
        if ( ! $this->conn) {
            throw new \Doctrine\DBAL\DBALException('Connection failed');
        }
        $this->lastInsertId = new LastInsertId();
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($sql)
    {
        return new OdbcSQLSrvStatement($this->conn, $sql, $this->lastInsertId);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * {@inheritDoc}
     * @license New BSD, code from Zend Framework
     */
    public function quote($value, $type=\PDO::PARAM_STR)
    {
        if (is_int($value)) {
            return $value;
        } else if (is_float($value)) {
            return sprintf('%F', $value);
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name !== null) {
            $sql = "SELECT IDENT_CURRENT(".$this->quote($name).") AS LastInsertId";
            $stmt = $this->prepare($sql);
            $stmt->execute();

            return $stmt->fetchColumn();
        }

        return $this->lastInsertId->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        if ( ! odbc_autocommit($this->conn, false)) {
            throw SQLSrvException::fromSqlSrvErrors();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        if ( ! odbc_commit($this->conn)) {
            throw SQLSrvException::fromSqlSrvErrors();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        if ( ! odbc_rollback($this->conn)) {
            throw SQLSrvException::fromSqlSrvErrors();
        }
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        return sqlsrv_errors(SQLSRV_ERR_ERRORS);
    }
}
