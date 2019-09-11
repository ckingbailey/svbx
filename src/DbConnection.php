<?php
namespace SVBX;

use MysqliDb;

class DbConnection extends MysqliDb
{
    public function lazyGet(
        string $tableName,
        array $select,
        $join = [],
        $where = [],
        $groupBy = [],
        $orderBy = [],
        $limit = null
    ) : array
    {
        echo 'arguments to lazyGet ' . print_r([
            $tableName, $select, $join, $where, $groupBy, $orderBy, $limit
        ], true);
        foreach ($where as $condition) $this->where(...$condition);

        foreach ($join as $condition) $this->join(...$condition);

        foreach ($groupBy as $condition) $this->groupBy($condition);

        foreach ($orderBy as $condition) $this->orderBy(...$condition);

        $res = $this->get($tableName, $limit, $select);
        return $res;
    }
}