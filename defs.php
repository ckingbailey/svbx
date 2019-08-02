<?php
require_once 'vendor/autoload.php';
require_once 'session.php';

// check which view to show
$view = !empty(($_GET['view']))
    ? filter_var($_GET['view'], FILTER_SANITIZE_ENCODED) : '';
$orderBy = null;

// check for search params
// if no search params show all defs that are not 'deleted'
if(!empty($_GET)) {
    error_log("\$_GET before filtering:\n" . print_r($_GET, true));
    $get = array_filter($_GET, function ($val) {
        return (gettype($val) === 'string' && $val !== '') || (bool) $val;
    }); // filter to remove falsey values -- is this necessary?
    error_log("\$_GET after filtering:\n" . print_r($get, true));
    unset($get['search'], $get['view']);
    $get = filter_var_array($get, FILTER_SANITIZE_SPECIAL_CHARS);
    $orderBy = array_reduce(array_keys($get), function($acc, $key) use ($get) {
        if (strpos($key, 'sort_') === 0 && array_search($get[$key], $acc) === false)
            $acc[$key] = $get[$key];
        return $acc;
    }, []);
    unset($get['sort_1'], $get['sort_2'], $get['sort_3']);
} else {
    $get = null;
}

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader,
    [
        'debug' => $_ENV['PHP_ENV'] === 'dev'
    ]
);
$twig->addExtension(new Twig_Extension_Debug());

// add Twig filters
$filter_decode = new Twig_Filter('safe', function($str) {
    return html_entity_decode($str);
});
$twig->addFilter($filter_decode);    

// set view-dependent variables
$bartTableHeadings = [
    'ID' => [ 'value' => 'ID', 'cellWd' => '1', 'collapse' => 'none def-table__col-id', 'href' => '/def.php?bartDefID=' ],
    'status' => [ 'value' => 'Status', 'cellWd' => '2' ],
    'date_created'=> [ 'value' => 'Date created', 'cellWd' => '3', 'collapse' => 'xs' ],
    'descriptive_title_vta' => [ 'value' => 'Description', 'cellWd' => '', 'classList' => 'def-table__crop-content' ],
    'resolution_vta' => [ 'value' => 'Resolution', 'cellWd' => '', 'collapse' => 'xs', 'classList' => 'def-table__crop-content' ],
    'next_step'=> [ 'value' => 'Next step', 'cellWd' => '3', 'collapse' => 'xs' ],
    'edit'=> [ 'value' => 'Edit', 'cellWd' => '1', 'collapse' => 'sm', 'href' => '/updateDef.php?class=bart&id=' ]
];

$projectTableHeadings = [
    'ID' => [ 'value' => 'ID', 'cellWd' => '1', 'collapse' => 'none def-table__col-id', 'href' => '/def.php?defID=' ],
    'location' => [ 'value' => 'Location', 'cellWd' => '2', 'collapse' => 'sm' ],
    'severity' => [ 'value' => 'Severity', 'cellWd' => '2', 'collapse' => 'xs' ],
    'status' => [ 'value' => 'Status', 'cellWd' => '2' ],
    'systemAffected' => [ 'value' => 'System affected', 'cellWd' => '2', 'collapse' => 'sm', 'classList' => 'def-table__crop-content' ],
    'groupToResolve' => [ 'value' => 'Group to resolve', 'cellWd' => '2', 'collapse' => '', 'classList' => 'def-table__crop-content' ],
    'description' => [ 'value' => 'Description', 'cellWd' => '6', 'collapse' => 'xs', 'classList' => 'def-table__crop-content' ],
    'specLoc' => [ 'value' => 'Specific location', 'cellWd' => '2', 'collapse' => 'md' ],
    'requiredBy' => [ 'value' => 'Required prior to', 'cellWd' => '2', 'collapse' => '' ],
    'dueDate' => [ 'value' => 'Due date', 'cellWd' => '2', 'collapse' => 'md' ],
    'edit' => [ 'value' => 'Edit', 'cellWd' => '1', 'collapse' => 'sm', 'classList' => 'def-table__edit', 'href' => '/updateDef.php?id=' ]
];

$bartFields = [
    'ID',
    's.statusName as status',
    'date_created',
    'descriptive_title_vta',
    'resolution_vta',
    'n.nextStepName AS next_step'
];

$projectFields = [
    "c.defID AS ID",
    "l.locationName AS location",
    "s.severityName AS severity",
    "t.statusName AS status",
    "y.systemName AS systemAffected",
    "g.systemName AS groupToResolve",
    "c.description AS description",
    "c.specLoc AS specLoc",
    "r.requiredBy AS requiredBy",
    "DATE_FORMAT(c.dueDate, '%d %b %Y') AS dueDate"
];

$bartJoins = [
    'status s' => 'b.status = s.statusID',
    'bdNextStep n' => 'b.next_step = n.bdNextStepID'
];

$projectJoins = [
    "location l" => "c.location = l.locationID",
    "requiredBy r" => "c.requiredBy = r.reqByID",
    "severity s" => "c.severity = s.severityID",
    "status t" => "c.status = t.statusID",
    "system y" => "c.systemAffected = y.systemID",
    "system g" => "c.groupToResolve = g.systemID",
    'defType type' => 'c.defType = type.defTypeID'
];

$bartFilters = [
    'status' => [
        'table' => 'status s',
        'fields' => ['statusID', 'statusName'],
        'join' => [
            'joinTable' => 'BARTDL b',
            'joinOn' => 's.statusID = b.status',
            'joinType' => 'INNER'
        ],
        'groupBy' => 's.statusID',
        'where' => [
            'field' => 's.statusID',
            'value' => '3',
            'comparison' => '<>'
        ]
    ],
    'next_step' => [
        'table' => 'bdNextStep n',
        'fields' => ['bdNextStepID', 'nextStepName'],
        'join' => [
            'joinTable' => 'BARTDL b',
            'joinOn' => 'b.next_step = n.bdNextStepID',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'n.bdNextStepID',
        'where' => [
            'field' => 'n.bdNextStepID',
            'value' => '0',
            'comparison' => '<>'
        ]
    ],
    'bic' => [
        'table' => 'bdParties p',
        'fields' => ['partyID', 'partyName'],
        'join' => [
            'joinTable' => 'BARTDL b',
            'joinOn' => 'p.partyID = b.bic',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'p.partyID',
        'where' => [
            'field' => 'p.partyID',
            'value' => '0',
            'comparison' => '<>'
        ]
    ],
    'safety_cert_vta' => [
        'table' => 'yesNo y',
        'fields' => ['yesNoID', 'yesNoName'],
        'join' => [
            'joinTable' => 'BARTDL b',
            'joinOn' => 'y.yesNoID = b.safety_cert_vta',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'y.yesNoID'
    ],
    'resolution_disputed' => [
        'table' => 'BARTDL',
        'fields' => ['resolution_disputed', '(CASE WHEN resolution_disputed = 1 THEN "yes" ELSE "no" END) AS yesNoName'], // res_disp and structural use CASES to map 0 + 1 to 'no' + 'yes' b/c they don't line up nicely with our bool table, yesNo
        'groupBy' => 'resolution_disputed'
    ],
    'structural' => [
        'table' => 'BARTDL',
        'fields' => ['structural', '(CASE WHEN structural = 1 THEN "yes" ELSE "no" END) AS yesNoName'], // res_disp and structural use CASES to map 0 + 1 to 'no' + 'yes' b/c they don't line up nicely with our bool table, yesNo
        'groupBy' => 'structural'
    ]
];

$projectFilters = [
    "status" => [
        'table' => 'status s',
        'fields' => ['statusID', 'statusName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 'c.status = s.statusID',
            'joinType' => 'INNER'
        ],
        'groupBy' => 's.statusID',
        'where' => [
            'field' => 'statusID',
            'value' => '3',
            'comparison' => '<>'
        ]
    ],
    "safetyCert" => [
        'table' => 'yesNo y',
        'fields' => ['yesNoID', 'yesNoName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 'c.safetyCert = y.yesNoID',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'y.yesNoID'
    ],
    "severity" => [
        'table' => 'severity s',
        'fields' => ['severityID', 'severityName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 's.severityID = c.severity',
            'joinType' => 'INNER'
        ],
        'groupBy' => 's.severityID'
    ],
    "systemAffected" => [
        'table' => 'system s',
        'fields' => ['systemID', 'systemName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 's.systemID = c.systemAffected',
            'joinType' => 'INNER'
        ],
        'groupBy' => 's.systemID'
    ],
    "groupToResolve" => [
        'table' => 'system g',
        'fields' => ['systemID', 'systemName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 'g.systemID = c.groupToResolve',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'g.systemID'
    ],
    "location" => [
        'table' => 'location l',
        'fields' => ['locationID', 'locationName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 'l.locationID = c.location',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'l.locationID'
    ],
    "identifiedBy" => [
        'table' => 'CDL',
        'fields' => 'identifiedBy',
        'groupBy' => 'identifiedBy'
    ],
    'requiredBy' => [
        'table' => 'requiredBy r',
        'fields' => 'reqByID, r.requiredBy',
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 'r.reqByID = c.requiredBy',
            'joinType' => 'INNER'
        ],
        'groupBy' => 'reqByID'
    ]
];

$projectSort = [
    'location' => 'Location',
    'severity' => 'Severity',
    'systemAffected' => 'System affected',
    'groupToResolve' => 'Group to resolve',
    'requiredBy' => 'Required prior to',
    'dueDate' => 'Due date'
];

list($table, $tableAlias, $addPath, $tableHeadings, $fields, $joins, $filters, $sortOptions) = $view === 'BART'
    ? [ 'BARTDL b', 'b', 'newDef.php?class=bart', $bartTableHeadings, $bartFields, $bartJoins, $bartFilters, [] ]
    : [ 'CDL c', 'c', 'newDef.php', $projectTableHeadings, $projectFields, $projectJoins, $projectFilters, $projectSort ];

if ($_SESSION['role'] <= 10) unset($tableHeadings['edit']);

$queryParams = [ 'fields' => $fields, 'joins' => $joins ];

// function to get filter options to display in <select> elements
function getFilterOptions($link, $queryParams) {
    $options = [];
    foreach ($queryParams as $fieldName => $params) {
        $table = $params['table'];
        $fields = $params['fields'];
        if (!empty($params['join']))
            $link->join($params['join']['joinTable'], $params['join']['joinOn'], $params['join']['joinType']);
        if (!empty($params['where'])) {
            $whereParams = $params['where'];
            if (gettype($whereParams) === 'string')
            // if where is string, use it as raw where query
                $link->where($whereParams);
            elseif (!empty($whereParams['comparison']))
                $link->where($whereParams['field'], $whereParams['value'], $whereParams['comparison']);
            else $link->where($whereParams['field'], $whereParams['value']);
        }
        if (!empty($params['groupBy'])) $link->groupBy($params['groupBy']);
        if (!empty($params['orderBy'])) $link->orderBy($params['orderBy']);
        if ($result = $link->get($table, null, $fields)) {
            $options[$fieldName] = [];
            foreach ($result as $row) {
                $fieldNames = array_keys($row);
                $value = $row[$fieldNames[0]];
                if (count($fieldNames) > 1) $text = $row[$fieldNames[1]];
                else $text = $value;
                $options[$fieldName][$value] = $text;
            }
        } else {
            $options[$fieldName] = "Unable to retrieve $fieldName list";
        }
    }
    return $options;
}

function getBartStatusCount($link) {
    $table = 'BARTDL b';
    $fields = [
        'COUNT(CASE WHEN s.statusName = "open" THEN 1 ELSE NULL END) AS statusOpen',
        'COUNT(CASE WHEN s.statusName = "closed" THEN 1 ELSE NULL END) AS statusClosed'
    ];
    $link->join('status s', 'b.status = s.statusID', 'LEFT');
    return $link->getOne($table, $fields);
}

// base context
$context = [
    'session' => $_SESSION,
    'title' => 'Deficiencies List',
    'pageHeading' => 'Deficiencies',
    'info' => 'Click Deficiency ID number to see full details',
    'addPath' => $addPath,
    // filter vars
    'resetScript' => 'resetSearch',
    'values' => $get,
    'collapse' => empty($get),
    'view' => $view,
    'sortOptions' => $sortOptions,
    'curSort' => $orderBy,
    // table vars
    'tableName' => $table,
    'tableProps' => [
        'classList' => 'def-table'
    ],
    'dataDisplayName' => 'deficiency',
    'tableHeadings' => $tableHeadings
];

try {
    $link = new MySqliDB(DB_CREDENTIALS);

    if ($view === 'BART') $context['statusData'] = getBartStatusCount($link);

    // get filter select options, showing those that are currently filtered on
    $context['selectOptions'] = getFilterOptions($link, $filters);

    // build defs query
    foreach ($queryParams['joins'] as $tableName => $on) {
        $link->join($tableName, $on, 'LEFT');
    }

    // filter on user-selected query params
    if (!empty($get)) {
        error_log("filters params:\n" . print_r($get, true));
        foreach ($get as $param => $val) {
            if ($param === 'description'
                || $param === 'defID'
                || $param === 'specLoc') $link->where($param, "%{$val}%", 'LIKE');
            elseif ($param === 'systemAffected'
                || $param === 'groupToResolve'
                && is_array($val))
            {
                $arrayVals = [ array_shift($val) ];
                foreach ($val as $extraVal) {
                    array_push($arrayVals, $extraVal);
                }
                $link->where("$tableAlias.$param", $arrayVals, 'IN');
            }
            else $link->where("$tableAlias.$param", $val);
        }
    }

    $link->where('status', '3', '<>');
    if (!empty($orderBy)) {
        foreach ($orderBy as $field) {
            $link->orderBy($field, 'ASC');
        }
    }
    $link->orderBy('ID', 'ASC');
    
    // fetch table data and append it to $context for display by Twig template
    $data = $result = $link->get($table, null, $queryParams['fields']);
    $context['data'] = $data;
    if (!empty($get))
        error_log("query after filtering defs view:\n" . $link->getLastQuery());

    $context['count'] = $link->count;

    $twig->display('defs.html.twig', $context);
} catch (Twig_Error $e) {
    echo $e->getTemplateLine() . ' ' . $e->getRawMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}

$link->disconnect();

exit;