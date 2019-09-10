<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    protected $defs = [];

    protected static $whereLike = [
        'defID',
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
        $fields = array_intersect($select, Deficiency::getFields());

        $where = array_reduce(array_keys($where),
            function ($output, $field) use ($where, $fields) {
                if (in_array($field, $fields)) {
                    $comparator = static::getComparator($where[$field]);
                    $output[] = [
                        $field,
                        $where[$field],
                        $comparator
                    ];
                }
                return $output;
            }, []);

        return [
            'select' => $fields,
            'join' => Deficiency::getJoins($fields),
            'where' => $where,
            'groupBy' => $groupBy,
            'orderBy' => $orderBy
        ];
    }

    protected static function getComparator($field) {
        if (in_array($field, static::$whereLike)) return 'LIKE';
        if (is_array($field)) return 'IN';
    }
}