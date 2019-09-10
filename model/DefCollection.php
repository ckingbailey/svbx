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

    public static function getFetchable(
        array $select = [],
        array $where = [],
        array $groupBy = [],
        array $orderBy = [ 'id', 'ASC' ]): array
    {
        // get fields from Def and return those that match strings in $select
        $defFields = Deficiency::getFields();

        $fetchable = [
            'select' => array_reduce($select,
                function ($output, $field) use ($defFields) {
                    if (!empty($defFields[$field])) {
                        $output[$defFields[$field]] = "CDL.$defFields[$field]"
                        . ($defFields[$field] === $field ? '' : " AS $field");
                    }
                    return $output;
                }, [])
            ];

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

        $fetchable['join'] = Deficiency::getJoins($fetchable['select']);

        if (!empty($groupBy))
            $fetchable['groupBy'] = $groupBy;

        if (!empty($orderBy))
            $fetchable['orderBy'] = $orderBy;

        return $fetchable;
    }

    protected static function getComparator($field) {
        if (in_array($field, static::$whereLike)) return 'LIKE';
        if (is_array($field)) return 'IN';
        else return '=';
    }
}