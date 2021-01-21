<?php

namespace Morozov\PgCompat\Compat\AsynchronousOperations\Model\BulkStatus;

use Magento\AsynchronousOperations\Api\Data\BulkSummaryInterface;

class CalculatedStatusSql extends \Magento\AsynchronousOperations\Model\BulkStatus\CalculatedStatusSql
{
    /**
     * Get sql to calculate bulk status
     *
     * @param string $operationTableName
     * @return \Zend_Db_Expr
     */
    public function get($operationTableName)
    {
        return new \Zend_Db_Expr(
            '(CASE WHEN
                (SELECT count(*)
                    FROM ' . $operationTableName . '
                    WHERE bulk_uuid = main_table.uuid
                ) = 0
                THEN ' . BulkSummaryInterface::NOT_STARTED . '
                ELSE (SELECT MAX(status) FROM ' . $operationTableName . ' WHERE bulk_uuid = main_table.uuid)
                END
            )'
        );
    }
}
