<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    protected $defs = [];

    protected static $whereLike = [
        'id',
        'bartDefID',
        'specLoc',
        'description'
    ];

    public function __construct($props) {
        return $props;
    }

    public static function getFetchableAssoc(
        array $select,
        $where = [],
        $groupBy = null,
        $orderBy = [ 'id ASC' ]) : array
    {
        if (empty($orderBy)) $orderBy = [ 'id ASC' ];
        // get fields from Def and return those that match strings in $select
        $defFields = Deficiency::getFields();

        $fetchable = [
            'table' => Deficiency::getTable(),
            'select' => array_reduce($select,
                function ($output, $field) use ($defFields) {
                    if (!empty($defFields[$field])) {
                        $output[] = "CDL.$defFields[$field]"
                        . ($defFields[$field] === $field ? '' : " AS $field");
                    }
                    return $output;
                }, [])
            ];

        $fetchable['join'] = Deficiency::getJoins($fetchable['select']);

        $fetchable['where'] = array_reduce(array_keys($where),
            function ($output, $field) use ($where, $select, $fetchable, $defFields) {
                if (!empty($defFields[$field])) {
                    $comparator = static::getComparator($field);
                    $val = $where[$field];
                    $field = "CDL.{$defFields[$field]}";
                    $output[] = [
                        $field,
                        $val,
                        $comparator
                    ];
                }
                return $output;
            }, []);

        if (!empty($groupBy))
            $fetchable['groupBy'] = $groupBy;

        if (!empty($orderBy)) {
            $fetchable['orderBy'] = array_map(function ($str) {
                $arr = explode(' ', $str);
                return [ $arr[0], $arr[1] ];
            }, $orderBy);
        }

        return $fetchable;
    }

    /**
     * @return [
     *   0 => string TABLE
     *   1 => array SELECT
     *   2 => array JOIN
     *   3 => array WHERE @default []
     *   4 => array GROUP BY @default []
     *   5 => string ORDER BY @default []
     *   6 => int LIMIT @default null
     * ]
     */
    public static function getFetchableNum(
        array $select,
        $where = [],
        $groupBy = null,
        $orderBy = null) : array
    {
        $assoc = static::getFetchableAssoc($select, $where, $groupBy, $orderBy);

        $_groupBy = empty($assoc['groupBy']) ? [] : $assoc['groupBy'];
        $_orderBy = empty($assoc['orderBy']) ? [] : $assoc['orderBy'];
        $_limit = empty($assoc['limit']) ? null : $assoc['limit'];

        return [
            $assoc['table'],
            $assoc['select'],
            $assoc['join'],
            $assoc['where'],
            $_groupBy,
            $_orderBy,
            $_limit
        ];
    }

    protected static function getComparator($field) {
        if (in_array($field, static::$whereLike)) return 'LIKE';
        if (is_array($field)) return 'IN';
        else return '=';
    }
}