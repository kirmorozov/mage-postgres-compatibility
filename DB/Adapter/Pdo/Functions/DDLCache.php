<?php

namespace Morozov\PgCompat\DB\Adapter\Pdo\Functions;

trait DDLCache
{

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
        throw new \RuntimeException('Not implemented ' . self::class . '::saveDdlCache()');
    }

    public function loadDdlCache($tableCacheKey, $ddlType)
    {
        throw new \RuntimeException('Not implemented ' . self::class . '::loadDdlCache()');
    }
}
