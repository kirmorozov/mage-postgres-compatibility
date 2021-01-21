<?php

namespace Morozov\PgCompat\DB\Adapter\Pdo\Functions;

trait DDLCache
{
    public function setCacheAdapter(\Magento\Framework\Cache\FrontendInterface $cacheAdapter)
    {
        $this->_cacheAdapter = $cacheAdapter;
        return $this;
    }

    public function allowDdlCache()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::allowDdlCache()');
    }

    public function disallowDdlCache()
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::disallowDdlCache()');
    }

    public function resetDdlCache($tableName = null, $schemaName = null)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::resetDdlCache()');
    }

    public function saveDdlCache($tableCacheKey, $ddlType, $data)
    {
        // Todo: add implementation
        return $this;
    }

    public function loadDdlCache($tableCacheKey, $ddlType)
    {
        // Todo: add implementation
        return false;
    }
}
