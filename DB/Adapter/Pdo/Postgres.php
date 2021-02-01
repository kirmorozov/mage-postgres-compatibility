<?php

namespace Morozov\PgCompat\DB\Adapter\Pdo;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\DB\Adapter\TableNotFoundException;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\DB\Statement\Parameter;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;

class Postgres extends \Zend_Db_Adapter_Pdo_Pgsql implements AdapterInterface
{
    use Functions\Fixes;

    public const DDL_DESCRIBE = 1;
    public const DDL_CREATE = 2;
    public const DDL_INDEX = 3;
    public const DDL_FOREIGN_KEY = 4;
    private const DDL_EXISTS = 5;
    public const DDL_CACHE_PREFIX = 'DB_PDO_MYSQL_DDL';
    public const DDL_CACHE_TAG = 'DB_PDO_MYSQL_DDL';

    protected $string;
    protected $dateTime;
    protected $logger;
    protected $selectFactory;
    protected $serializer;
    protected $exceptionMap;

    protected $_cacheAdapter;

    /**
     * Current Transaction Level
     *
     * @var int
     */
    protected $_transactionLevel = 0;

    /**
     * Constructor
     *
     * @param StringUtils $string
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param SelectFactory $selectFactory
     * @param array $config
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        StringUtils $string,
        DateTime $dateTime,
        LoggerInterface $logger,
        SelectFactory $selectFactory,
        array $config = [],
        SerializerInterface $serializer = null
    )
    {
        $this->string = $string;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->selectFactory = $selectFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        $this->exceptionMap = [
            // SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
            2006 => ConnectionException::class,
            // SQLSTATE[HY000]: General error: 2013 Lost connection to MySQL server during query
            2013 => ConnectionException::class,
            // SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
            1205 => LockWaitException::class,
            // SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock
            1213 => DeadlockException::class,
            // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
            1062 => DuplicateException::class,
            // SQLSTATE[42S02]: Base table or view not found: 1146
            1146 => TableNotFoundException::class,
        ];
        try {
            parent::__construct($config);
        } catch (Zend_Db_Adapter_Exception $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function _connect()
    {
        unset($this->_config['model'], $this->_config['engine'], $this->_config['active'], $this->_config['type']);
        $initStatements = "";
        if (isset($this->_config['initStatements'])) {
            $initStatements = $this->_config['initStatements'];
            unset($this->_config['initStatements']);
        }
        parent::_connect();

        if (!empty($initStatements)) {
            $this->query($initStatements);
        }
    }

    public function select()
    {
        return $this->selectFactory->create($this);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * Method revrited for handle empty arrays in value param
     *
     * @param string $text The text with a placeholder.
     * @param array|null|int|string|float|Expression|Select|\DateTimeInterface $value The value to quote.
     * @param int|string|null $type OPTIONAL SQL datatype of the given value e.g. Zend_Db::FLOAT_TYPE or "INT"
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        if (is_array($value) && empty($value)) {
            $value = new \Zend_Db_Expr('NULL');
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return parent::quoteInto($text, $value, $type, $count);
    }

    public function newTable($tableName = null, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::newTable()');
    }

    public function createTable(Table $table)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::createTable()');
    }

    public function dropTable($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropTable()');
    }

    public function createTemporaryTable(Table $table)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::createTemporaryTable()');
    }

    public function createTemporaryTableLike($temporaryTableName, $originTableName, $ifNotExists = false)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::createTemporaryTableLike()');
    }

    public function dropTemporaryTable($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropTemporaryTable()');
    }

    public function renameTablesBatch(array $tablePairs)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::renameTablesBatch()');
    }

    public function truncateTable($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::truncateTable()');
    }

    public function isTableExists($tableName, $schemaName = null)
    {
        return count($this->query("SELECT 1 FROM pg_tables WHERE tablename = ? ", [$tableName])->fetchAll());
    }

    public function showTableStatus($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::showTableStatus()');
    }

    public function createTableByDdl($tableName, $newTableName)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::createTableByDdl()');
    }

    public function modifyColumnByDdl($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::modifyColumnByDdl()');
    }

    public function renameTable($oldTableName, $newTableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::renameTable()');
    }

    public function addColumn($tableName, $columnName, $definition, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::addColumn()');
    }

    public function changeColumn($tableName, $oldColumnName, $newColumnName, $definition, $flushData = false, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::changeColumn()');
    }

    public function modifyColumn($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::modifyColumn()');
    }

    public function dropColumn($tableName, $columnName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropColumn()');
    }

    public function tableColumnExists($tableName, $columnName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::tableColumnExists()');
    }

    public function addIndex($tableName, $indexName, $fields, $indexType = self::INDEX_TYPE_INDEX, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::addIndex()');
    }

    public function dropIndex($tableName, $keyName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropIndex()');
    }

    public function getIndexList($tableName, $schemaName = null)
    {
        $cacheKey = $tableName;
        $ddl = $this->loadDdlCache($cacheKey, self::DDL_INDEX);
        if ($ddl === false) {
            $ddl = [];

            $sql = "select
                        i.relname as key_name,
                        case
                            when ix.indisprimary = true then 'primary'
                            when ix.indisunique = true then 'unique'
                            else 'index'
                        end as index_type,
                        a.attname as column_name
                    from
                        pg_class t,
                        pg_class i,
                        pg_index ix,
                        pg_attribute a
                    where
                            t.oid = ix.indrelid
                      and i.oid = ix.indexrelid
                      and a.attrelid = t.oid
                      and a.attnum = ANY(ix.indkey)
                      and t.relkind = 'r'
                      and t.relname like ?
                    order by
                        t.relname,
                        i.relname;
                    ";
            foreach ($this->fetchAll($sql, [$tableName]) as $row) {
                $fieldKeyName = 'key_name';
                $fieldColumn = 'column_name';
                $fieldIndexType = 'index_type';

                if ($row[$fieldIndexType] == AdapterInterface::INDEX_TYPE_PRIMARY) {
                    $indexType = AdapterInterface::INDEX_TYPE_PRIMARY;
                } elseif ($row[$fieldIndexType] == AdapterInterface::INDEX_TYPE_UNIQUE) {
                    $indexType = AdapterInterface::INDEX_TYPE_UNIQUE;
                } elseif ($row[$fieldIndexType] == AdapterInterface::INDEX_TYPE_FULLTEXT) {
                    // TODO: Add FULLTEXT search
                    $indexType = AdapterInterface::INDEX_TYPE_FULLTEXT;
                } else {
                    $indexType = AdapterInterface::INDEX_TYPE_INDEX;
                }

                $upperKeyName = strtolower($row[$fieldKeyName]);
                if (isset($ddl[$upperKeyName])) {
                    $ddl[$upperKeyName]['fields'][] = $row[$fieldColumn]; // for compatible
                    $ddl[$upperKeyName]['COLUMNS_LIST'][] = $row[$fieldColumn];
                } else {
                    $ddl[$upperKeyName] = [
                        'SCHEMA_NAME' => $schemaName,
                        'TABLE_NAME' => $tableName,
                        'KEY_NAME' => $row[$fieldKeyName],
                        'COLUMNS_LIST' => [$row[$fieldColumn]],
                        'INDEX_TYPE' => $indexType,
                        'INDEX_METHOD' => $row[$fieldIndexType],
                        'type' => strtolower($indexType), // for compatibility
                        'fields' => [$row[$fieldColumn]], // for compatibility
                    ];
                }
            }
            $this->saveDdlCache($cacheKey, self::DDL_INDEX, $ddl);
        }

        return $ddl;
    }

    public function addForeignKey($fkName, $tableName, $columnName, $refTableName, $refColumnName, $onDelete = self::FK_ACTION_CASCADE, $purge = false, $schemaName = null, $refSchemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::addForeignKey()');
    }

    public function dropForeignKey($tableName, $fkName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropForeignKey()');
    }

    public function getForeignKeys($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getForeignKeys()');
    }

    public function insertOnDuplicate($table, array $data, array $fields = [])
    {
        $indexes = array_filter($this->getIndexList($table), function ($idx) {
            return in_array($idx['INDEX_TYPE'], [AdapterInterface::INDEX_TYPE_PRIMARY, AdapterInterface::INDEX_TYPE_UNIQUE]);
        });
        $cols = [];
        $vals = "";
        if (is_array(reset($data))) {
            $cols = array_keys($data[0]);
            $vals = implode(
                '), (',
                array_map(function ($row) {
                    return implode(', ', array_map(function ($val) {
                        return $this->quote($val);
                    }, $row));
                }, $data)
            );
        } else {
            $cols = array_keys($data);
            $vals = implode(', ', array_map(function ($val) {
                return $this->quote($val);
            }, $data));
        }

        if (empty($fields)) {
            $fields = $cols;
        }

        $sql = "INSERT INTO "
            . $this->quoteIdentifier($table, true) . ' as _tgt'
            . ' (' . implode(', ', $cols) . ') '
            . ' VALUES (' . $vals . ')';

        foreach ($indexes as $index) {
            $condition = array_map(function ($col) {
                return $this->quoteIdentifier($col);
            }, $index['COLUMNS_LIST']);
            $sql .= "\n ON CONFLICT (" . implode(', ', $condition) . ") ";
            $updateExprs = [];
            foreach ($fields as $k => $v) {
                if (!is_numeric($k)) {
                    $field = $this->quoteIdentifier($k);
                    if ($v instanceof \Zend_Db_Expr) {
                        $value = '_tgt.' . $v->__toString();
                    } elseif ($v instanceof \Laminas\Db\Sql\Expression) {
                        $value = '_tgt.' . $v->getExpression();
                    } elseif (is_string($v)) {
                        $value = 'excluded.' . $this->quoteIdentifier($v);
                    } elseif (is_numeric($v)) {
                        $value = $this->quoteInto('?', $v);
                    }
                } elseif (is_string($v)) {
                    $value = "excluded.{$this->quoteIdentifier($v)}";
                    $field = $this->quoteIdentifier($v);
                }
                if ($field && is_string($value) && $value !== '') {
                    $updateExprs[] = "$field = $value";
                }
            }
            $sql .= "DO UPDATE SET " . implode(', ', $updateExprs);
        }

        $res = $this->query($sql);
        return $res->rowCount();
    }

    /**
     * Inserts a table multiply rows with specified data.
     *
     * @param string|array|\Zend_Db_Expr $table The table to insert data into.
     * @param array $data Column-value pairs or array of Column-value pairs.
     * @return int The number of affected rows.
     * @throws \Zend_Db_Exception
     */
    public function insertMultiple($table, array $data)
    {
        $row = reset($data);
        // support insert syntaxes
        if (!is_array($row)) {
            return $this->insert($table, $data);
        }

        $res = 0;
        foreach ($data as $row) {
            $res +=$this->insert($table, $row);
        }
        return $res;
    }

    public function insertArray($table, array $columns, array $data)
    {
        $res = 0;
        foreach ($data as $row) {
            $res += $this->insert($table, array_combine($columns, $row));
        }
        return $res;
    }

    public function insertForce($table, array $bind)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::insertForce()');
    }

    /**
     * Format Date to internal database date format
     *
     * @param int|string|\DateTimeInterface $date
     * @param bool $includeTime
     * @return \Zend_Db_Expr
     */
    public function formatDate($date, $includeTime = true)
    {
        $date = $this->dateTime->formatDate($date, $includeTime);

        if ($date === null) {
            return new \Zend_Db_Expr('NULL');
        }

        return new \Zend_Db_Expr($this->quote($date));
    }

    public function startSetup()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::startSetup()');
    }

    public function endSetup()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::endSetup()');
    }

    use Functions\DDLCache;

    /**
     * Build SQL statement for condition
     *
     * If $condition integer or string - exact value will be filtered ('eq' condition)
     *
     * If $condition is array is - one of the following structures is expected:
     * - array("from" => $fromValue, "to" => $toValue)
     * - array("eq" => $equalValue)
     * - array("neq" => $notEqualValue)
     * - array("like" => $likeValue)
     * - array("in" => array($inValues))
     * - array("nin" => array($notInValues))
     * - array("notnull" => $valueIsNotNull)
     * - array("null" => $valueIsNull)
     * - array("gt" => $greaterValue)
     * - array("lt" => $lessValue)
     * - array("gteq" => $greaterOrEqualValue)
     * - array("lteq" => $lessOrEqualValue)
     * - array("finset" => $valueInSet)
     * - array("nfinset" => $valueNotInSet)
     * - array("regexp" => $regularExpression)
     * - array("seq" => $stringValue)
     * - array("sneq" => $stringValue)
     *
     * If non matched - sequential array is expected and OR conditions
     * will be built using above mentioned structure
     *
     * @param string $fieldName
     * @param integer|string|array $condition
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareSqlCondition($fieldName, $condition)
    {
        $conditionKeyMap = [
            'eq' => "{{fieldName}} = ?",
            'neq' => "{{fieldName}} != ?",
            'like' => "{{fieldName}} LIKE ?",
            'nlike' => "{{fieldName}} NOT LIKE ?",
            'in' => "{{fieldName}} IN(?)",
            'nin' => "{{fieldName}} NOT IN(?)",
            'is' => "{{fieldName}} IS ?",
            'notnull' => "{{fieldName}} IS NOT NULL",
            'null' => "{{fieldName}} IS NULL",
            'gt' => "{{fieldName}} > ?",
            'lt' => "{{fieldName}} < ?",
            'gteq' => "{{fieldName}} >= ?",
            'lteq' => "{{fieldName}} <= ?",
            'finset' => "? = ANY (string_to_array({{fieldName}},','))",
            'nfinset' => "NOT( ? = ANY (string_to_array({{fieldName}},',')))",
            'regexp' => "{{fieldName}} REGEXP ?", //  TODO: implement REGEXP search
            'from' => "{{fieldName}} >= ?",
            'to' => "{{fieldName}} <= ?",
            'seq' => null,
            'sneq' => null,
            'ntoa' => "INET_NTOA({{fieldName}}) LIKE ?", // TODO: implement INET_NTOA
        ];

        $query = '';
        if (is_array($condition)) {
            $key = key(array_intersect_key($condition, $conditionKeyMap));

            if (isset($condition['from']) || isset($condition['to'])) {
                if (isset($condition['from'])) {
                    $from = $this->_prepareSqlDateCondition($condition, 'from');
                    $query = $this->_prepareQuotedSqlCondition($conditionKeyMap['from'], $from, $fieldName);
                }

                if (isset($condition['to'])) {
                    $query .= empty($query) ? '' : ' AND ';
                    $to = $this->_prepareSqlDateCondition($condition, 'to');
                    $query = $query . $this->_prepareQuotedSqlCondition($conditionKeyMap['to'], $to, $fieldName);
                }
            } elseif (array_key_exists($key, $conditionKeyMap)) {
                $value = $condition[$key];
                if (($key == 'seq') || ($key == 'sneq')) {
                    $key = $this->_transformStringSqlCondition($key, $value);
                }
                if (($key == 'in' || $key == 'nin') && is_string($value)) {
                    $value = explode(',', $value);
                }
                $query = $this->_prepareQuotedSqlCondition($conditionKeyMap[$key], $value, $fieldName);
            } else {
                $queries = [];
                foreach ($condition as $orCondition) {
                    $queries[] = sprintf('(%s)', $this->prepareSqlCondition($fieldName, $orCondition));
                }

                $query = sprintf('(%s)', implode(' OR ', $queries));
            }
        } else {
            $query = $this->_prepareQuotedSqlCondition($conditionKeyMap['eq'], (string)$condition, $fieldName);
        }

        return $query;
    }

    /**
     * Prepare Sql condition
     *
     * @param string $text Condition value
     * @param mixed $value
     * @param string $fieldName
     * @return string
     */
    protected function _prepareQuotedSqlCondition($text, $value, $fieldName)
    {
        $sql = $this->quoteInto($text, $value);
        $sql = str_replace('{{fieldName}}', $fieldName, $sql);
        return $sql;
    }

    /**
     * Prepare sql date condition
     *
     * @param array $condition
     * @param string $key
     * @return string
     */
    protected function _prepareSqlDateCondition($condition, $key)
    {
        if (empty($condition['date'])) {
            if (empty($condition['datetime'])) {
                $result = $condition[$key];
            } else {
                $result = $this->formatDate($condition[$key]);
            }
        } else {
            $result = $this->formatDate($condition[$key]);
        }

        return $result;
    }

    /**
     * Transforms sql condition key 'seq' / 'sneq' that is used for comparing string values to its analog:
     * - 'null' / 'notnull' for empty strings
     * - 'eq' / 'neq' for non-empty strings
     *
     * @param string $conditionKey
     * @param mixed $value
     * @return string
     */
    protected function _transformStringSqlCondition($conditionKey, $value)
    {
        $value = (string)$value;
        if ($value == '') {
            return ($conditionKey == 'seq') ? 'null' : 'notnull';
        } else {
            return ($conditionKey == 'seq') ? 'eq' : 'neq';
        }
    }

    /**
     * Prepare value for save in column
     *
     * Return converted to column data type value
     *
     * @param array $column the column describe array
     * @param mixed $value
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function prepareColumnValue(array $column, $value)
    {
        if ($value instanceof \Zend_Db_Expr) {
            return $value;
        }
        if ($value instanceof Parameter) {
            return $value;
        }

        // return original value if invalid column describe data
        if (!isset($column['DATA_TYPE'])) {
            return $value;
        }

        // return null
        if ($value === null && $column['NULLABLE']) {
            return null;
        }

        switch ($column['DATA_TYPE']) {
            case 'smallint':
            case 'int':
                $value = (int)$value;
                break;
            case 'bigint':
                if (!is_integer($value)) {
                    $value = sprintf('%.0f', (float)$value);
                }
                break;

            case 'decimal':
                $precision = 10;
                $scale = 0;
                if (isset($column['SCALE'])) {
                    $scale = $column['SCALE'];
                }
                if (isset($column['PRECISION'])) {
                    $precision = $column['PRECISION'];
                }
                $format = sprintf('%%%d.%dF', $precision - $scale, $scale);
                $value = (float)sprintf($format, $value);
                break;

            case 'float':
                $value = (float)sprintf('%F', $value);
                break;

            case 'date':
                $value = $this->formatDate($value, false);
                break;
            case 'datetime':
            case 'timestamp':
                $value = $this->formatDate($value);
                break;

            case 'varchar':
            case 'mediumtext':
            case 'text':
            case 'longtext':
                $value = (string)$value;
                if ($column['NULLABLE'] && $value == '') {
                    $value = null;
                }
                break;

            case 'varbinary':
            case 'mediumblob':
            case 'blob':
            case 'longblob':
                // No special processing for MySQL is needed
                break;
        }

        return $value;
    }

    /**
     * Generate fragment of SQL, that check condition and return true or false value
     *
     * @param \Zend_Db_Expr|\Magento\Framework\DB\Select|string $expression
     * @param string $true true value
     * @param string $false false value
     * @return \Zend_Db_Expr
     */
    public function getCheckSql($expression, $true, $false)
    {
        if ($expression instanceof \Zend_Db_Expr || $expression instanceof \Zend_Db_Select) {
            $expression = sprintf("CASE WHEN (%s) THEN %s ELSE %s END", $expression, $true, $false);
        } else {
            $expression = sprintf("CASE WHEN %s THEN %s ELSE %s END", $expression, $true, $false);
        }

        return new \Zend_Db_Expr($expression);
    }

    /**
     * Returns valid IFNULL expression
     *
     * @param \Zend_Db_Expr|\Magento\Framework\DB\Select|string $expression
     * @param string|int $value OPTIONAL. Applies when $expression is NULL
     * @return \Zend_Db_Expr
     */
    public function getIfNullSql($expression, $value = 0)
    {
        if ($expression instanceof \Zend_Db_Expr || $expression instanceof \Zend_Db_Select) {
            $expression = sprintf("COALESCE((%s), %s)", $expression, $value);
        } else {
            $expression = sprintf("COALESCE(%s, %s)", $expression, $value);
        }

        return new \Zend_Db_Expr($expression);
    }

    public function getConcatSql(array $data, $separator = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getConcatSql()');
    }

    public function getLengthSql($string)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getLengthSql()');
    }

    public function getLeastSql(array $data)
    {
        return new \Zend_Db_Expr('LEAST(' . implode(', ', $data) . ')');
    }

    public function getFieldSql($field, array $sequence)
    {
        $sql = "CASE ";
        foreach ($sequence as $i => $v) {
            $sql .= "WHEN $field = $v THEN $i ";
        }
        $sql .= " END";

        return new \Zend_Db_Expr($sql);
    }

    public function getGreatestSql(array $data)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getGreatestSql()');
    }

    public function getDateAddSql($date, $interval, $unit)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getDateAddSql()');
    }

    public function getDateSubSql($date, $interval, $unit)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getDateSubSql()');
    }

    public function getDateFormatSql($date, $format)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getDateFormatSql()');
    }

    public function getDatePartSql($date)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getDatePartSql()');
    }

    public function getSubstringSql($stringExpression, $pos, $len = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getSubstringSql()');
    }

    public function getStandardDeviationSql($expressionField)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getStandardDeviationSql()');
    }

    public function getDateExtractSql($date, $unit)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getDateExtractSql()');
    }

    /**
     * Generates case SQL fragment
     *
     * Generate fragment of SQL, that check value against multiple condition cases
     * and return different result depends on them
     *
     * @param string $valueName Name of value to check
     * @param array $casesResults Cases and results
     * @param string $defaultValue value to use if value doesn't confirm to any cases
     * @return \Zend_Db_Expr
     */
    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        $expression = 'CASE ' . $valueName;
        foreach ($casesResults as $case => $result) {
            $expression .= ' WHEN ' . $case . ' THEN ' . $result;
        }
        if ($defaultValue !== null) {
            $expression .= ' ELSE ' . $defaultValue;
        }
        $expression .= ' END';

        return new \Zend_Db_Expr($expression);
    }

    public function getTableName($tableName)
    {
        return $tableName;
    }

    public function getTriggerName($tableName, $time, $event)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getTriggerName()');
    }

    public function getIndexName($tableName, $fields, $indexType = '')
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getIndexName()');
    }

    public function getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getForeignKeyName()');
    }

    public function disableTableKeys($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::disableTableKeys()');
    }

    public function enableTableKeys($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::enableTableKeys()');
    }

    public function selectsByRange($rangeField, \Magento\Framework\DB\Select $select, $stepCount = 100)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::selectsByRange()');
    }

    public function insertFromSelect(\Magento\Framework\DB\Select $select, $table, array $fields = [], $mode = false)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::insertFromSelect()');
    }

    public function updateFromSelect(\Magento\Framework\DB\Select $select, $table)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::updateFromSelect()');
    }

    public function deleteFromSelect(\Magento\Framework\DB\Select $select, $table)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::deleteFromSelect()');
    }

    public function getTablesChecksum($tableNames, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getTablesChecksum()');
    }

    public function supportStraightJoin()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::supportStraightJoin()');
    }

    public function orderRand(\Magento\Framework\DB\Select $select, $field = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::orderRand()');
    }

    public function forUpdate($sql)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::forUpdate()');
    }

    public function getPrimaryKeyName($tableName, $schemaName = null)
    {
        $indexes = $this->getIndexList($tableName, $schemaName);
        $data = array_filter($indexes, function ($x) {
            return $x['INDEX_TYPE'] == 'primary';
        });
        $mainKey = reset($data);
        return $mainKey['KEY_NAME'];
    }

    public function decodeVarbinary($value)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::decodeVarbinary()');
    }

    use Functions\Transaction;

    public function createTrigger(\Magento\Framework\DB\Ddl\Trigger $trigger)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::createTrigger()');
    }

    public function dropTrigger($triggerName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::dropTrigger()');
    }

    public function getTables($likeCondition = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getTables()');
    }

    /**
     * Returns auto increment field if exists
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @return string|bool
     * @since 100.1.0
     */
    public function getAutoIncrementField($tableName, $schemaName = null)
    {
        $indexName = $this->getPrimaryKeyName($tableName, $schemaName);
        $indexes = $this->getIndexList($tableName);
        if ($indexName && count($indexes[$indexName]['COLUMNS_LIST']) == 1) {
            return current($indexes[$indexName]['COLUMNS_LIST']);
        }
        return false;
    }
}
