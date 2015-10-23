<?php

namespace Doctrine\DBAL\Platforms;


class SQLServer2000Platform extends SQLServerPlatform
{

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset = null)
    {
        if ($limit !== null) {
            $query= str_replace('SELECT', 'SELECT TOP ' . $limit, $query);
        }

        if ($offset !== null) {
            throw new \DBALException("OFFSET don't support");
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:s.u';
    }

    protected function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();
        $this->doctrineTypeMapping['datetime2'] = 'datetime';
        $this->doctrineTypeMapping['date'] = 'date';
        $this->doctrineTypeMapping['time'] = 'time';
        $this->doctrineTypeMapping['timestamp'] = 'timestamp';
    }

}
