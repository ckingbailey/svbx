<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    protected $defs = [];

    protected static $where = [
        'defID' => 'LIKE',
        'safetyCert' => '=',
        'systemAffected' => '=',
        'location' => '=',
        'status' => '=',
        'severity' => '=',
        'groupToResolve' => '=',
        'requiredBy' => '=',
        'contractID' => '=',
        'defType' => '=',
        'description' => 'LIKE',
        'evidenceType' => '=',
        'repo' => '=',
        'updated_by' => '=',
        'created_by' => '=',
        'closureRequestedBy' => '='
    ];

    public function __construct($props) {
        return $props;
    }

    public static function getFetchable(
        array $select = [],
        array $where = [],
        array $groupBy = [],
        array $orderBy = [ 'id', 'ASC' ]
    ) {
        // get fields from Def and return those that match strings in $select
        $fields = array_intersect($select, Deficiency::getFields());

        $where = array_reduce(array_keys($where),
            function ($output, $field) use ($where, $fields) {
                if (in_array($field, $fields)) {
                    $comparator = is_array($fields[$field]) ? 'IN' : '=';
                    $output[] = [
                        $field,
                        $fields[$field],
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
}