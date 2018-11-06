<?php
require_once 'session.php';
require_once 'vendor/autoload.php';
require_once 'SQLFunctions.php';
// require_once 'routes/assetRoutes.php';

include 'html_functions/htmlTables.php';

$table = 'CDL';

// instantiate Twig
$loader = new Twig_Loader_Filesystem('./templates');
$twig = new Twig_Environment($loader,
    [
        'debug' => true
    ]
);
$twig->addExtension(new Twig_Extension_Debug());
$template = $twig->load('defs.html.twig');

// base context
$context = [
    'navbarHeading' => !empty($_SESSION['username'])
        ? ( !empty($_SESSION['firstname']) && !empty($_SESSION['lastname'])
            ? $_SESSION['firstname'] . ' ' . $_SESSION['lastname']
            : $_SESSION['username'] )
        : '',
    'title' => 'Deficiencies List',
    'pageHeading' => 'Deficiencies',
    'tableName' => $table,
    'dataDisplayName' => 'deficiency',
    'info' => 'Click Deficiency ID number to see full details',
    'addPath' => 'newDef.php',
    'tableHeadings' => [
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
    ]
];

$title = "View Deficiencies";
$role = $_SESSION['role'];
$view = isset($_GET['view']) ? $_GET['view'] : '';

// query to see if user has permission to view BART defs
try {
    $link = connect();
    // $link->where('userid', $_SESSION['userID']);
    // $result = $link->getOne('users_enc', [ 'bdPermit' ]);
    // $bartPermit = $result['bdPermit'];
} catch (Exception $e) {
    echo "<h1 style='font-size: 4rem; font-family: monospace; color: red;'>{$e->getMessage()}</h1>";
    exit;
}

// check for search params
// if no search params show all defs that are not 'deleted'
if(!empty($_GET['search'])) {
    $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
    $get = array_filter($get); // filter to remove falsey values -- is this necessary??
    unset($get['search']);
} else {
    $get = null;
}
// render Project Defs table and Search Fields
try {
    // printSearchBar($link, $get, ['method' => 'GET', 'action' => 'defs.php']);
} catch (Exception $e) {
    echo "<h1 id='searchBarCatch' style='color: #fa0;'>print search bar got issues: {$e}</h1>";
}


try {
    $fields = [
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
    $joins = [
        "location l" => "c.location = l.locationID",
        "requiredBy r" => "c.requiredBy = r.reqByID",
        "severity s" => "c.severity = s.severityID",
        "status t" => "c.status = t.statusID",
        "system y" => "c.systemAffected = y.systemID"
    ];
    foreach ($joins as $tableName => $on) {
        $link->join($tableName, $on, 'LEFT');
    }

    if ($get) {
        foreach ($get as $param => $val) {
            if ($param === 'description') $link->where($param, "%{$val}%", 'LIKE');
            else $link->where($param, $val);
        }
    }

    $link->orderBy('ID', 'ASC');
    $link->where('c.status', 'closed', '<>');
    
    $context['data'] = $result = $link->get("$table c", null, $fields);
    $template->display($context);
} catch (Twig_Error $e) {
    echo $e->getTemplateLine() . ' ' . $e->getRawMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}

$link->disconnect();

