<?php

namespace Morozov\PgCompat\DB;

class PostgresQuote extends \Magento\Framework\DB\Platform\Quote
{
    protected function getQuoteIdentifierSymbol()
    {
        return '"';
    }
}
