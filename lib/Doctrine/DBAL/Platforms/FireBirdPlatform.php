<?php

namespace Doctrine\DBAL\Platforms;


class FireBirdPlatform extends AbstractPlatform {
    /**
     * Returns the SQL snippet that declares a boolean column.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef) {
        // TODO: Implement getBooleanTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL snippet that declares a 4 byte integer column.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef) {
        // TODO: Implement getIntegerTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL snippet that declares an 8 byte integer column.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef) {
        // TODO: Implement getBigIntTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL snippet that declares a 2 byte integer column.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef) {
        // TODO: Implement getSmallIntTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL snippet used to declare a CLOB column type.
     *
     * @param array $field
     *
     * @return string
     */
    public function getClobTypeDeclarationSQL(array $field) {
        // TODO: Implement getClobTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL Snippet used to declare a BLOB column type.
     *
     * @param array $field
     *
     * @return string
     */
    public function getBlobTypeDeclarationSQL(array $field) {
        // TODO: Implement getBlobTypeDeclarationSQL() method.
    }

    /**
     * Returns the SQL snippet that declares common properties of an integer column.
     *
     * @param array $columnDef
     *
     * @return string
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) {
        // TODO: Implement _getCommonIntegerTypeDeclarationSQL() method.
    }

    /**
     * Lazy load Doctrine Type Mappings.
     *
     * @return void
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = array(
            'int' => 'integer',
            'timestamp' => 'datetime',
            'time' => 'datetime',
            'date' => 'date',
            'varchar' => 'string',
            'char' => 'string',
            'double' => 'float',
            'float' => 'float',
            'int64' => 'float',
            'smallint' => 'integer',
        );
    }

    public function getName() {
        return 'firebird';
    }

    /**
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        return 'SELECT rdb$relation_name FROM rdb$relations
        WHERE rdb$view_blr IS NULL --AND rdb$relation_name =\'O_MDM_ORDER_BASEREQUEST_INFO\'
        AND (rdb$system_flag IS NULL OR rdb$system_flag = 0);';
    }

    /**
     * {@inheritDoc}
     * @link http://stackoverflow.com/questions/12070162/how-can-i-get-the-table-description-fields-and-types-from-firebird-with-dbexpr
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        return "select rf.RDB\$FIELD_NAME AS NAME,
        CASE f.RDB\$FIELD_TYPE
             WHEN 7 THEN 'SMALLINT'
             WHEN 8 THEN 'int'
             WHEN 9 THEN 'QUAD'
             WHEN 10 THEN 'FLOAT'
             WHEN 11 THEN 'D_FLOAT'
             WHEN 12 THEN 'DATE'
             WHEN 13 THEN 'TIME'
             WHEN 14 THEN 'CHAR'
             WHEN 16 THEN 'INT64'
             WHEN 27 THEN 'DOUBLE'
             WHEN 35 THEN 'TIMESTAMP'
             WHEN 37 THEN 'VARCHAR'
             WHEN 40 THEN 'CSTRING'
             WHEN 261 THEN 'BLOB'
             ELSE 'UNKNOWN'
        END AS TYPE,
        f.RDB\$FIELD_LENGTH AS LENGTH,
        IIF(COALESCE(RF.RDB\$NULL_FLAG, 0) = 0, 0, 1) NOTNULL,
        REPLACE (COALESCE(RF.RDB\$DEFAULT_SOURCE, F.RDB\$DEFAULT_SOURCE), 'DEFAULT ', '') AS FIELD_DEFAULT ,
        (SELECT FIRST 1 DCO.RDB\$COLLATION_NAME FROM RDB\$COLLATIONS DCO WHERE DCO.RDB\$COLLATION_ID = F.RDB\$COLLATION_ID AND DCO.RDB\$CHARACTER_SET_ID = F.RDB\$CHARACTER_SET_ID ORDER BY 1 DESC) AS FIELD_COLLATION,
        --(SELECT FIRST 1 CH.RDB\$CHARACTER_SET_NAME FROM RDB\$CHARACTER_SETS CH WHERE CH.RDB\$CHARACTER_SET_ID = F.RDB\$CHARACTER_SET_ID) AS FIELD_CHARSET,
        null scale,
        null FIELD_precision,
        null autoincrement
        from rdb\$relation_fields rf
        JOIN RDB\$FIELDS f ON (f.RDB\$FIELD_NAME = rf.RDB\$FIELD_SOURCE)
        AND rf.rdb\$relation_name = '$table'
        AND (COALESCE(RF.RDB\$SYSTEM_FLAG, 0) = 0)
        order by rf.RDB\$FIELD_POSITION";
    }

    protected function getReservedKeywordsClass()
    {
        return 'Doctrine\DBAL\Platforms\Keywords\FireBirdKeywords';
    }

    public function getListTableIndexesSQL($table, $currentDatabase = null)
    {
        return 'SELECT RDB$INDEX_SEGMENTS.RDB$FIELD_NAME AS column_name,
            RDB$INDICES.RDB$INDEX_NAME key_name,
            IIF(COALESCE(RDB$INDICES.RDB$UNIQUE_FLAG, 0) = 0, 0, 1) is_unique,
            IIF(COALESCE(RDB$RELATION_CONSTRAINTS.RDB$CONSTRAINT_TYPE, \'\') = \'PRIMARY KEY\', 1, 0) is_primary,
            NULL flags

            FROM RDB$INDEX_SEGMENTS
            LEFT JOIN RDB$INDICES ON RDB$INDICES.RDB$INDEX_NAME = RDB$INDEX_SEGMENTS.RDB$INDEX_NAME
            LEFT JOIN RDB$RELATION_CONSTRAINTS ON RDB$RELATION_CONSTRAINTS.RDB$INDEX_NAME = RDB$INDEX_SEGMENTS.RDB$INDEX_NAME

            WHERE UPPER(RDB$INDICES.RDB$RELATION_NAME)=\'' . $table . '\'';
    }

    public function prefersIdentityColumns()
    {
        return true;
    }

    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        $replace = 'SELECT';
        if ($limit !== null) {
            $replace .= ' FIRST ' . $limit;
        }

        if ($offset !== null) {
            $replace .= ' SKIP ' . $offset;
        }

        return str_replace('SELECT', $replace, $query);
    }

    public function getSQLResultCasing($column)
    {
        return strtoupper($column);
    }

}