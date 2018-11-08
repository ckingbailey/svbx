<?php
require_once 'session.php';
require_once 'vendor/autoload.php';
require_once 'SQLFunctions.php';

// check which view to show
$view = !empty(($_GET['view']))
    ? filter_var($_GET['view'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

// check for search params
// if no search params show all defs that are not 'deleted'
if(!empty($_GET['search'])) {
    $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
    $get = array_filter($get); // filter to remove falsey values -- is this necessary?
    unset($get['search'], $get['view']);
} else {
    $get = null;
}

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader,
    [
        'debug' => true
    ]
);
$twig->addExtension(new Twig_Extension_Debug());
$template = $twig->load('defs.html.twig');

// set view-dependent variables
$bartTableHeadings = [
    'ID' => [ 'value' => 'ID', 'cellWd' => '', 'href' => '/viewDef.php?bartDefID=' ],
    'status' => [ 'value' => 'Status', 'cellWd' => '' ],
    'date_created'=> [ 'value' => 'Date created', 'cellWd' => '' ],
    'descriptive_title_vta' => [ 'value' => 'Description', 'cellWd' => '' ],
    'resolution_vta' => [ 'value' => 'Resolution', 'cellWd' => '' ],
    'next_step'=> [ 'value' => 'Next step', 'cellWd' => '' ],
    'edit'=> [ 'value' => 'Edit', 'cellWd' => '', 'collapse' => 'sm', 'href' => '/updateBartDef.php?bartDefID=' ]
];

$projectTableHeadings = [
    'ID' => [ 'value' => 'ID', 'cellWd' => '', 'href' => '/viewDef.php?defID=' ],
    'location' => [ 'value' => 'Location', 'cellWd' => '', 'collapse' => 'sm' ],
    'severity' => [ 'value' => 'Severity', 'cellWd' => '', 'collapse' => 'xs' ],
    'status' => [ 'value' => 'Status', 'cellWd' => '' ],
    'systemAffected' => [ 'value' => 'System affected', 'cellWd' => '', 'collapse' => 'sm' ],
    'description' => [ 'value' => 'Description', 'cellWd' => '' ],
    'specLoc' => [ 'value' => 'Specific location', 'cellWd' => '', 'collapse' => 'md' ],
    'requiredBy' => [ 'value' => 'Required By', 'cellWd' => '', 'collapse' => 'md' ],
    'dueDate' => [ 'value' => 'Due date', 'cellWd' => '', 'collapse' => 'md' ],
    'edit' => [ 'value' => 'Edit', 'cellWd' => '', 'collapse' => 'sm', 'href' => '/updateDef.php?defID=' ]
];

$bartFields = [
    'ID',
    's.statusName as status',
    'date_created',
    'SUBSTR(descriptive_title_vta, 1, 132) AS descriptive_title_vta',
    'SUBSTR(resolution_vta, 1, 132) AS resolution_vta',
    'n.nextStepName AS next_step'
];

$projectFields = [
    "c.defID AS ID",
    "l.locationName AS location",
    "s.severityName AS severity",
    "t.statusName AS status",
    "y.systemName AS systemAffected",
    "SUBSTR(c.description, 1, 50) AS description",
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
    "system y" => "c.systemAffected = y.systemID"
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
            'joinOn' => 'p.partyID = b.creator',
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
        'table' => 'system s',
        'fields' => ['systemID', 'systemName'],
        'join' => [
            'joinTable' => 'CDL c',
            'joinOn' => 's.systemID = c.groupToResolve',
            'joinType' => 'INNER'
        ],
        'groupBy' => 's.systemID'
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
    "specLoc" => [
        'table' => 'CDL',
        'fields' => 'specLoc',
        'groupBy' => 'specLoc'
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

list($table, $tableAbbrev, $addPath, $tableHeadings, $fields, $joins, $filters) = $view === 'BART'
    ? [ 'BARTDL b', 'b', 'newBartDef.php', $bartTableHeadings, $bartFields, $bartJoins, $bartFilters ]
    : [ 'CDL c', 'c', 'NewDef.php', $projectTableHeadings, $projectFields, $projectJoins, $projectFilters ];

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
    'navbarHeading' => !empty($_SESSION['username'])
        ? ( !empty($_SESSION['firstname']) && !empty($_SESSION['lastname'])
            ? $_SESSION['firstname'] . ' ' . $_SESSION['lastname']
            : $_SESSION['username'] )
        : '',
    'title' => 'Deficiencies List',
    'pageHeading' => 'Deficiencies',
    'bartDefs' => $_SESSION['bdPermit'],
    'role' => $_SESSION['role'],
    'info' => 'Click Deficiency ID number to see full details',
    'addPath' => $addPath,
    // filter vars
    'resetScript' => 'resetSearch',
    'values' => $get,
    'collapse' => empty($get),
    'view' => $view,
    // table vars
    'tableName' => $table,
    'dataDisplayName' => 'deficiency',
    'tableHeadings' => $tableHeadings
];

// get nav items for user permissions level
$context['navItems'] = $_SESSION['inspector']
    ? [
        'Home' => '/dashboard.php',
        'Help' => '/help.php',
        'Deficiencies' => $_SESSION['bdPermit']
            ? [ 'Project deficiencies' => '/defs.php', 'BART deficiencies' => '/defs.php?view=BART' ]
            : '/defs.php',
        'Safety Certs' => '/ViewSC.php',
        'Daily Report' => 'idr.php',
        'Logout' => '/logout.php'
    ]
    : [
        'Home' => '/dashboard.php',
        'Help' => '/help.php',
        'Deficiencies' => $_SESSION['bdPermit']
            ? [ 'Project deficiencies' => '/defs.php', 'BART deficiencies' => '/defs.php?view=BART' ]
            : '/defs.php',
        'Safety Certs' => '/ViewSC.php',
        'Logout' => '/logout.php'
    ];

try {
    $link = connect();

    if ($view === 'BART') $context['statusData'] = getBartStatusCount($link);

    // get filter select options, showing those that are currently filtered on
    $context['selectOptions'] = getFilterOptions($link, $filters);

    // build defs query
    foreach ($queryParams['joins'] as $tableName => $on) {
        $link->join($tableName, $on, 'LEFT');
    }

    // filter on user-selected query params
    if ($get) {
        foreach ($get as $param => $val) {
            if ($param === 'description' || $param === 'defID') $link->where($param, "%{$val}%", 'LIKE');
            else $link->where("$tableAbbrev.$param", $val);
        }
    }

    $link->orderBy('ID', 'ASC');
    
    // fetch table data and append it to $context for display by Twig template
    $data = $result = $link->get($table, null, $queryParams['fields']);
    $context['data'] = $data;
    $context['fileLink'] = json_encode($data);

    $template->display($context);
} catch (Twig_Error $e) {
    echo $e->getTemplateLine() . ' ' . $e->getRawMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}

$link->disconnect();

exit;