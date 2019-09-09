<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    private $defs = [];
    private $fields = [];
    private $joins = [];

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
        $this->fields = array_intersect($select, Deficiency::getFields());
        $this->joins = array_filter(Deficiency::getJoins($this->fields));

        return [
            'select' => $this->fields,
            'join' => $this->joins,
            'where' => $where,
            'groupBy' => $groupBy,
            'orderBy' => $orderBy
        ];
    }
}