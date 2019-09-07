<?php
namespace SVBX;

use SVBX\Deficiency;

class DefCollection
{
    private $defs = [];
    private $fields = [];

    public function __construct($props) {
        return $props;
    }

    public static function getFetchable(
        array $select = [],
        array $where = [],
        array $groupBy = [],
        array $orderBy = [ 'id', 'ASC' ]
    ) {
        $fields = Deficiency::getFields();

        return [
            'select' => $select,
            'where' => $where,
            'groupBy' => $groupBy,
            'orderBy' => $orderBy
        ];
    }
}