<?php

namespace Doctrine\DBAL\Driver\OdbcSQLSrv;

class Driver implements \Doctrine\DBAL\Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        if (!isset($params['host'])) {
            throw new \Exception("Missing 'host' in configuration for odbc driver.");
        }
        if (!isset($params['dbname'])) {
            throw new \Exception("Missing 'dbname' in configuration for odbc driver.");
        }

        return new OdbcSQLSrvConnection($this->_constructDsn($params), $username, $password);
    }

    private function _constructDsn(array $params)
    {
        $dsn = 'Driver={SQL Server};Server=';

        if (isset($params['host'])) {
            $dsn .= $params['host'];
        }

        if (isset($params['dbname'])) {
            $dsn .= ';Database=' .  $params['dbname'];
        }

        return $dsn;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new \Doctrine\DBAL\Platforms\SQLServer2000Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\SQLServerSchemaManager($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'odbc_sqlsrv';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }
}
