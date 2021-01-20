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
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;

class Postgres extends \Zend_Db_Adapter_Pdo_Pgsql implements AdapterInterface
{
    protected $string;
    protected $dateTime;
    protected $logger;
    protected $selectFactory;
    protected $serializer;
    protected $exceptionMap;

    protected $_cacheAdapter;

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
        throw new \RuntimeException('Not implemented ' . self::class . '::isTableExists()');
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
        throw new \RuntimeException('Not implemented ' . self::class . '::getIndexList()');
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
        throw new \RuntimeException('Not implemented ' . self::class . '::insertOnDuplicate()');
    }

    public function insertMultiple($table, array $data)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::insertMultiple()');
    }

    public function insertArray($table, array $columns, array $data)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::insertArray()');
    }

    public function insertForce($table, array $bind)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::insertForce()');
    }

    public function formatDate($date, $includeTime = true)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::formatDate()');
    }

    public function startSetup()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::startSetup()');
    }

    public function endSetup()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::endSetup()');
    }

    public function setCacheAdapter(\Magento\Framework\Cache\FrontendInterface $cacheAdapter)
    {
        $this->_cacheAdapter = $cacheAdapter;
        return $this;
    }

    use Functions\DDLCache;

    public function prepareSqlCondition($fieldName, $condition)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::prepareSqlCondition()');
    }

    public function prepareColumnValue(array $column, $value)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::prepareColumnValue()');
    }

    public function getCheckSql($condition, $true, $false)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getCheckSql()');
    }

    public function getIfNullSql($expression, $value = 0)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getIfNullSql()');
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
        throw new \RuntimeException('Not implemented ' . self::class . '::getLeastSql()');
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

    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getCaseSql()');
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
        throw new \RuntimeException('Not implemented ' . self::class . '::getPrimaryKeyName()');
    }

    public function decodeVarbinary($value)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::decodeVarbinary()');
    }

    public function getTransactionLevel()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getTransactionLevel()');
    }

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

    public function getAutoIncrementField($tableName, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::getAutoIncrementField()');
    }
}
