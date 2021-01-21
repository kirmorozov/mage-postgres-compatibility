<?php
namespace Morozov\PgCompat\DB\Adapter\Pdo\Functions;

trait Transaction
{
    public function beginTransaction()
    {
        if ($this->_transactionLevel == 0) {
            parent::beginTransaction();
        }
        $this->_transactionLevel++;
        return $this;
    }

    public function commit()
    {
        if ($this->_transactionLevel == 1) {
            parent::commit();
        }
        $this->_transactionLevel--;
        return $this;
    }

    public function rollBack()
    {
        parent::rollBack();
        if ($this->_transactionLevel == 0) {
            throw new \Exception("Symetric transaction");
        }
        $this->_transactionLevel--;
        return $this;
    }


    /**
     * Get adapter transaction level state. Return 0 if all transactions are complete
     *
     * @return int
     */
    public function getTransactionLevel()
    {
        return $this->_transactionLevel;
    }
}
