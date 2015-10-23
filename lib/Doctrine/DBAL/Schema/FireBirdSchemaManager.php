<?php

namespace Doctrine\DBAL\Schema;

class FireBirdSchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritdoc}
     */
    protected function _getPortableViewDefinition($view)
    {
        return new View($view['TABLE_NAME'], $view['VIEW_DEFINITION']);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableDefinition($table)
    {
        return trim(array_shift($table));
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableUserDefinition($user)
    {
        return array(
            'user' => $user['User'],
            'password' => $user['Password'],
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        return end($sequence);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['Database'];
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = trim(strtolower($tableColumn['type']));

        $options = array(
            'length' => null,
            'unsigned' => false,
            'fixed' => false,
            'default' => null,
            'notnull' => false,
            'scale' => $tableColumn['scale'],
            'precision' => $tableColumn['field_precision'],
            'autoincrement' => (bool) $tableColumn['autoincrement'],
        );

        try{
            $type = $this->_platform->getDoctrineTypeMapping($dbType);
            return new Column($tableColumn['name'], \Doctrine\DBAL\Types\Type::getType($type), $options);
        } catch (\Doctrine\DBAL\DBALException $e) {
            if ($dbType === 'blob') {
                return;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Lists the foreign keys for the given table.
     *
     * @param string      $table    The name of the table.
     * @param string|null $database
     *
     * @return \Doctrine\DBAL\Schema\ForeignKeyConstraint[]
     */
    public function listTableForeignKeys($table, $database = null)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableIndexesList($tableIndexRows, $tableName=null)
    {
        foreach ($tableIndexRows as &$tableIndex) {
            $tableIndex['key_name'] = trim($tableIndex['KEY_NAME']);
            $tableIndex['column_name'] = trim($tableIndex['COLUMN_NAME']);
            $tableIndex['non_unique'] = $tableIndex['IS_UNIQUE'] != 1;
            $tableIndex['primary'] = $tableIndex['IS_PRIMARY'] == 1;
            $tableIndex['flags'] = $tableIndex['FLAGS'] ? array($tableIndex['FLAGS']) : null;
            unset($tableIndex['COLUMN_NAME'], $tableIndex['KEY_NAME'], $tableIndex['IS_UNIQUE'], $tableIndex['IS_PRIMARY'], $tableIndex['FLAGS']);
        }

        return parent::_getPortableTableIndexesList($tableIndexRows, $tableName);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnList($table, $database, $tableColumns)
    {
        foreach ($tableColumns as &$tableColumn) {
            $tableColumn['NAME'] = trim($tableColumn['NAME']);
            $tableColumn['TYPE'] = trim($tableColumn['TYPE']);
        }
        return parent::_getPortableTableColumnList($table, $database, $tableColumns);
    }

}
