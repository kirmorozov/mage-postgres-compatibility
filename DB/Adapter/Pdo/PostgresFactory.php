<?php

namespace Morozov\PgCompat\DB\Adapter\Pdo;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for Postgres adapter
 */
class PostgresFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create instance of Postgres adapter
     *
     * @param string $className
     * @param array $config
     * @param LoggerInterface|null $logger
     * @param SelectFactory|null $selectFactory
     * @return Postgres
     * @throws \InvalidArgumentException
     */
    public function create(
        $className,
        array $config,
        LoggerInterface $logger = null,
        SelectFactory $selectFactory = null
    ) {
        if (!in_array(Postgres::class, class_parents($className, true) + [$className => $className])) {
            throw new \InvalidArgumentException('Invalid class, ' . $className . ' must extend ' . Postgres::class . '.');
        }
        $arguments = [
            'config' => $config
        ];
        if ($logger) {
            $arguments['logger'] = $logger;
        }
        if ($selectFactory) {
            $arguments['selectFactory'] = $selectFactory;
        }
        return $this->objectManager->create(
            $className,
            $arguments
        );
    }
}
