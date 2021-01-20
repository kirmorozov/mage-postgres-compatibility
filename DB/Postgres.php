<?php

namespace Morozov\PgCompat\DB;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface;
use Magento\Framework\DB;
use Morozov\PgCompat\DB\Adapter\Pdo\PostgresFactory;
use Magento\Framework\DB\SelectFactory;

// @codingStandardsIgnoreStart

class Postgres extends \Magento\Framework\Model\ResourceModel\Type\Db implements
    ConnectionAdapterInterface
// @codingStandardsIgnoreEnd
{
    /**
     * @var array
     */
    protected $connectionConfig;

    /**
     * @var PostgresFactory
     */
    private $PostgresFactory;

    /**
     * Constructor
     *
     * @param array $config
     * @param PostgresFactory|null $PostgresFactory
     */
    public function __construct(
        array $config,
        PostgresFactory $PostgresFactory = null
    ) {
        $this->connectionConfig = $this->getValidConfig($config);
        $this->PostgresFactory = $PostgresFactory ?: ObjectManager::getInstance()->get(PostgresFactory::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(DB\LoggerInterface $logger = null, SelectFactory $selectFactory = null)
    {
        $connection = $this->getDbConnectionInstance($logger, $selectFactory);

        $profiler = $connection->getProfiler();
        if ($profiler instanceof DB\Profiler) {
            $profiler->setType($this->connectionConfig['type']);
            $profiler->setHost($this->connectionConfig['host']);
        }

        return $connection;
    }

    /**
     * Create and return database connection object instance
     *
     * @param DB\LoggerInterface|null $logger
     * @param SelectFactory|null $selectFactory
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getDbConnectionInstance(DB\LoggerInterface $logger = null, SelectFactory $selectFactory = null)
    {
        return $this->PostgresFactory->create(
            $this->getDbConnectionClassName(),
            $this->connectionConfig,
            $logger,
            $selectFactory
        );
    }

    /**
     * Retrieve DB connection class name
     *
     * @return string
     */
    protected function getDbConnectionClassName()
    {
        return Adapter\Pdo\Postgres::class;
    }

    /**
     * Validates the config and adds default options, if any is missing
     *
     * @param array $config
     * @return array
     */
    private function getValidConfig(array $config)
    {
        $default = ['initStatements' => 'SET NAMES utf8', 'type' => 'pdo_postgres', 'active' => false];
        foreach ($default as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        $required = ['host'];
        foreach ($required as $name) {
            if (!isset($config[$name])) {
                throw new \InvalidArgumentException("Postgres adapter: Missing required configuration option '$name'");
            }
        }

        if (isset($config['port'])) {
            throw new \InvalidArgumentException(
                "Port must be configured within host (like '$config[host]:$config[port]') parameter, not within port"
            );
        }

        $config['active'] = !(
            $config['active'] === 'false'
            || $config['active'] === false
            || $config['active'] === '0'
        );

        return $config;
    }
}
