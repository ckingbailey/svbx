<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    protected $defs = [];

    // These are the fields that use "LIKE %x%" in the WHERE clause
    protected static $whereLike = [
        'id',
        'bartDefID',
        'specLoc',
        'description',
        'certElID',
        'CEID_PDCC'
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
        if (empty($where)) $where = [];
        // get fields from Def and return those that match strings in $select
        $defFields = Deficiency::getFields();
        $lookup = Deficiency::getLookup();

        // This is the output array from this function
        // it contains the input values to this object, transformed into a form the Db object understands
        $fetchable = [
            'table' => Deficiency::getTable(),
            'select' => array_reduce($select,
                function ($output, $field) use ($defFields, $lookup) {
                    if (!empty($defFields[$field])) {
                        $output[] = "CDL.$defFields[$field]"
                        . ($defFields[$field] === $field ? '' : " $field");
                    } elseif (stripos($field, 'concat(') === 0) {
                        $fieldArr = preg_split('/(?<=\)) /', $field);

                        if (!empty($lookup[$fieldArr[1]]) && $lookup[$fieldArr[1]] = $fieldArr[0]) {
                            $select = 'CONCAT('
                            . implode(', ', array_map(function ($str) use ($fieldArr) {
                                    return $str === '" "' ? $str : "{$fieldArr[1]}.$str";
                                }, explode(', ', substr($fieldArr[0], strpos($fieldArr[0], '(') + 1, -1))))
                            . ')';
                            $output[] = "$select {$fieldArr[1]}";
                        }
                    } elseif (count($fieldArr = explode(' ', $field)) === 2) {
                        if (!empty($lookup[$fieldArr[1]]) && $lookup[$fieldArr[1]] = $fieldArr[0]) {
                            $output[] = "$fieldArr[1].{$fieldArr[0]} {$fieldArr[1]}";
                        }
                    }
                    return $output;
                }, [])
            ];

        $fetchable['join'] = Deficiency::getJoins($fetchable['select']);

        $fetchable['where'] = array_reduce(array_keys($where),
            function ($output, $field) use ($where, $select, $fetchable, $defFields) {
                if (!empty($defFields[$field])) {
                    $comparator = static::getComparator($field, $where[$field]);
                    $val = $comparator === 'LIKE' ? "%{$where[$field]}%" : $where[$field];
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
                if (empty($arr[1])) $arr[1] = 'ASC';
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

    protected static function getComparator($key, $val) {
        if (in_array($key, static::$whereLike)) return 'LIKE';
        if (is_array($val)) return 'IN';
        else return '=';
    }
}