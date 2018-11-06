<?php
require_once 'session.php';
require_once 'vendor/autoload.php';
require_once 'SQLFunctions.php';
// require_once 'routes/assetRoutes.php';

include 'html_functions/htmlTables.php';

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
    'resolution_vta' => [ 'value' => 'Resolution', 'cellWd' => '' ],
    'next_step'=> [ 'value' => 'Next step', 'cellWd' => '' ],
    'edit'=> [ 'value' => 'Edit', 'cellWd' => '', 'collapse' => 'sm', 'href' => '/updateBartDef.php?bartDefID=' ]
];

$projectTableHeadings = [
    'ID' => [ 'value' => 'ID', 'cellWd' => '', 'href' => '/viewDef.php?defID=' ],
    'location' => [ 'value' => 'Location', 'cellWd' => '', 'collapse' => 'sm' ],
    'severity' => [ 'value' => 'Severity', 'cellWd' => '', 'collapse' => 'xs' ],
    'dueDate' => [ 'value' => 'Due date', 'cellWd' => '', 'collapse' => 'md' ],
    'status' => [ 'value' => 'Status', 'cellWd' => '' ],
    'systemAffected' => [ 'value' => 'System affected', 'cellWd' => '', 'collapse' => 'sm' ],
    'description' => [ 'value' => 'Description', 'cellWd' => '' ],
    'specLoc' => [ 'value' => 'Specific location', 'cellWd' => '', 'collapse' => 'md' ],
    'requiredBy' => [ 'value' => 'Required By', 'cellWd' => '', 'collapse' => 'md' ],
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
    "DATE_FORMAT(c.dueDate, '%d %b %Y') AS dueDate",
    "t.statusName AS status",
    "y.systemName AS systemAffected",
    "SUBSTR(c.description, 1, 50) AS description",
    "c.specLoc AS specLoc",
    "r.requiredBy AS requiredBy"
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

list($table, $addPath, $tableHeadings, $fields, $joins) = $view === 'BART'
    ? [ 'BARTDL b', 'newBartDef.php', $bartTableHeadings, $bartFields, $bartJoins ]
    : [ 'CDL c', 'NewDef.php', $projectTableHeadings, $projectFields, $projectJoins ];

$queryParams = [ 'fields' => $fields, 'joins' => $joins ];

// set filter fields and define function to get filter params
$filterSelects = [
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

function getFilterOptions($link, $queryParams) {
    $options = [];
    foreach ($queryParams as $fieldName => $params) {
        $table = $params['table'];
        $fields = $params['fields'];
        if (!empty($params['join']))
            $link->join($params['join']['joinTable'], $params['join']['joinOn'], $params['join']['joinType']);
        if (!empty($params['where'])) {
            if (gettype($params['where']) === 'string')
            // if where is string, use it as raw where query
                $link->where($params['where']);
            elseif (!empty($params['where']['comparison']))
                $link->where($params['where']['field'], $params['where']['value'], $params['where']['comparison']);
            else $link->where($params['where']['field'], $params['where']['value']);
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

// base context
$context = [
    'navbarHeading' => !empty($_SESSION['username'])
        ? ( !empty($_SESSION['firstname']) && !empty($_SESSION['lastname'])
            ? $_SESSION['firstname'] . ' ' . $_SESSION['lastname']
            : $_SESSION['username'] )
        : '',
    'navItems' => [
        'Home' => '/dashboard.php',
        'Help' => '/help.php',
        'Deficiencies' => $_SESSION['bdPermit']
            ? [ 'Project deficiencies' => '/defs.php', 'BART deficiencies' => '/defs.php?view=BART' ]
            : '/defs.php',
        'Safety Certs' => '/ViewSC.php',
        'Logout' => '/logout.php'
    ],
    'title' => 'Deficiencies List',
    'pageHeading' => 'Deficiencies',
    'bartDefs' => $_SESSION['bdPermit'],
    'resetScript' => 'resetSearch',
    'values' => $get,
    'collapse' => empty($get),
    'view' => $view,
    'tableName' => $table,
    'dataDisplayName' => 'deficiency',
    'info' => 'Click Deficiency ID number to see full details',
    'addPath' => $addPath,
    'tableHeadings' => $tableHeadings
];

$title = "View Deficiencies";
$role = $_SESSION['role'];
$view = isset($_GET['view']) ? $_GET['view'] : '';

try {
    $link = connect();

    // get filter select options, showing those that are currently filtered on
    $context['selectOptions'] = getFilterOptions($link, $filterSelects);

    // build defs query
    foreach ($queryParams['joins'] as $tableName => $on) {
        $link->join($tableName, $on, 'LEFT');
    }

    if ($get) {
        foreach ($get as $param => $val) {
            if ($param === 'description') $link->where($param, "%{$val}%", 'LIKE');
            else $link->where($param, $val);
        }
    }

    $link->orderBy('ID', 'ASC');
    // $link->where('c.status', 'closed', '<>');
    
    $context['data'] = $result = $link->get("$table", null, $queryParams['fields']);
    $template->display($context);
} catch (Twig_Error $e) {
    echo $e->getTemplateLine() . ' ' . $e->getRawMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}

$link->disconnect();

