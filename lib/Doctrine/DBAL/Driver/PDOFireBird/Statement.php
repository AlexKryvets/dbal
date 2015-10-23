<?php

namespace Doctrine\DBAL\Driver\PDOFireBird;

class Statement extends \Doctrine\DBAL\Driver\PDOStatement
{
    /**
     * Private constructor.
     */
    protected function __construct()
    {
    }

    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR) {
        if (\PDO::PARAM_BOOL == $data_type) {
            $data_type = \PDO::PARAM_INT;
        }
        return parent::bindValue($parameter, $value, $data_type);
    }
}
