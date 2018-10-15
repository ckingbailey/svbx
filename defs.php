<?php
include('session.php');
include('SQLFunctions.php');
include('utils/utils.php');
include('html_functions/htmlTables.php');
$title = "View Deficiencies";
$role = $_SESSION['role'];
$view = !empty(($_GET['view']))
    ? filter_var($_GET['view'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

include('filestart.php');

// init Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, [
    'debug' => true
]);
$twig->addExtension(new Twig_Extension_Debug());

// query to see if user has permission to view BART defs
try {
    $link = connect();
    $link->where('userid', $_SESSION['userID']);
    $result = $link->getOne('users_enc', [ 'bdPermit' ]);
    $bartPermit = $result['bdPermit'];
} catch (Exception $e) {
    echo "<h1 style='font-size: 4rem; font-family: monospace; color: red;'>{$e->getMessage()}</h1>";
    exit;
}

function printInfoBox($role, $href, $dataGraphic = false) {
    $dataContainer = $dataGraphic
        ? "<div class='row mb-3'><div id='dataContainer' class='col-md-4 offset-md-4 d-flex flex-row flex-wrap justify-content-start'></div></div>"
        : '';
    $box ="
        <div class='card item-margin-bottom'>
            <div class='card-body grey-bg'>
                $dataContainer
                <div class='row'>
                    <div class='col-12 d-flex flex-row flex-wrap justify-content-between align-items-center'>
                        <span class='mb-2'>Click Deficiency ID number to see full details</span>
                        %s
                    </div>
                </div>
            </div>
        </div>";
    $btn = $role > 10 ? "<a href='%s' class='btn btn-primary'>Add New Deficiency</a>" : '';

    $box = sprintf($box, $btn);

    return printf($box, $href);
}

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

function printDefsTable($result, $tableElements, $userLvl) {
    // echo "<pre style='color: #39d;'>";
    // var_dump($result);
    // echo "</pre>";
    if (count($result)) {
        $keys = array_keys($tableElements);
        $headers = array_combine(
            $keys, array_column($tableElements, 'header')
        );
        $cells = array_combine(
            $keys, array_column($tableElements, 'cell')
        );
        echo "<table class='table table-striped table-responsive svbx-table'>";
        printTableHeadings($headers, $userLvl);
        populateTable($result, $cells, $userLvl);
        echo "</table>";
    } else
        print "<h4 class='text-secondary text-center'>No results found for your search</h4>";
}

function printProjectDefsTable($result, $userLvl) {
    $tdClassList = 'svbx-td';
    $thClassList = 'svbx-th';
    $collapseXs = 'collapse-xs';
    $collapseSm = 'collapse-sm collapse-xs';
    $collapseMd = 'collapse-md  collapse-sm collapse-xs';
    $tableElements = [
        'ID' => [
            'header' => [ 'text' => 'ID', 'classList' => $thClassList ],
            'cell' => [ 'classList' => $tdClassList, 'innerHtml' => "<a href='viewDef.php?defID=%s'>%s</a>" ]
        ],
        'location' => [
            'header' => [ 'text' => 'Location', 'classList' => "$thClassList $collapseSm" ],
            'cell' => [ 'classList' => "$tdClassList $collapseSm" ]
        ],
        'severity' => [
            'header' => [ 'text' => 'Severity', 'classList' => "$thClassList $collapseXs" ],
            'cell' => [ 'classList' => "$tdClassList $collapseXs" ]
        ],
        'dateCreated' => [
            'header' => [ 'text' => 'Date Created', 'classList' => "$thClassList $collapseMd" ],
            'cell' => [ 'classList' => "$tdClassList $collapseMd" ]
        ],
        'status' => [
            'header' => [ 'text' => 'Status', 'classList' => $thClassList ],
            'cell' => [ 'classList' => $tdClassList ]
        ],
        'systemAffected' => [
            'header' => [ 'text' => 'System Affected', 'classList' => "$thClassList $collapseSm" ],
            'cell' => [ 'classList' => "$tdClassList $collapseSm" ]
        ],
        'description' => [
            'header' => [ 'text' => 'Brief Description', 'classList' => $thClassList ],
            'cell' => [ 'classList' => $tdClassList ]
        ],
        'specLoc' => [
            'header' =>  [ 'text' => 'Specific Location', 'classList' => "$thClassList $collapseMd" ],
            'cell' => [ 'classList' => "$tdClassList $collapseMd" ]
        ],
        'lastUpdated' => [
            'header' => [ 'auth' => 20, 'text' => 'Last Updated', 'classList' => "$thClassList $collapseMd" ],
            'cell' => [ 'auth' => 20, 'classList' => "$tdClassList $collapseMd" ]
        ],
        'edit' => [
            'header' => ['auth' => 15, 'text' => 'Edit', 'classList' => "$thClassList $collapseSm" ],
            'cell' => [
                'auth' => 15,
                'classList' => "$tdClassList $collapseSm",
                'innerHtml' => "<a id='updateDef%s' href='updateDef.php?defID=%s' class='btn btn-outline'><i class='typcn typcn-edit'></i></button>"
            ]
        ]
    ];
    printDefsTable($result, $tableElements, $userLvl);
}

function printBartDefsTable($result, $role) {
    $thF = "<th class='%s'>%s</th>";
    $tdClassList = 'svbx-td';
    $thClassList = 'svbx-th';
    $collapseXs = 'collapse-xs';
    $collapseSm = 'collapse-sm collapse-xs';
    $collapseMd = 'collapse-md  collapse-sm collapse-xs';
    $tableElements = [
        'ID' => [
            'header' => [ 'text' => 'ID' ],
            'cell' => [ 'innerHtml' => "<a href='viewDef.php?bartDefID=%s'>%s</a>" ]
        ],
        'status' => [
            'header' => [ 'text' => 'Status' ],
            'cell' => []
        ],
        'date_created' => [
            'header' => [ 'text' => 'Date created' ],
            'cell' => []
        ],
        'descriptive_title_vta' => [
            'header' => [ 'text' => 'Description' ],
            'cell' => []
        ],
        'resolution_vta' => [
            'header' => [ 'text' => 'Resolution' ],
            'cell' => []
        ],
        'next_step' => [
            'header' => [ 'text' => 'Next step' ],
            'cell' => []
        ],
        'edit' => [
            'header' => [ 'text' => 'Edit', 'element' => sprintf($thF, "$thClassList $collapseSm", 'Edit')],
            'cell' => [
                'element' => sprintf("<td class='%s'><a id='updateDef%s'  href='updateBartDef.php?bartDefID=%s'><i class='typcn typcn-edit'></i></a></td>", "$thClassList $collapseSm", '%s', '%s'),
                'innerHtml' => "<a id='updateDef%s' href='updateBartDef.php?bartDefID=%s' class='btn btn-outline'><i class='typcn typcn-edit'></i></button></form>"
            ]
        ]
    ];

    printDefsTable($result, $tableElements, $role);
}

// check for search params
// if no search params show all defs that are not 'deleted'
if(!empty($_GET['search'])) {
    $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
    $get = array_filter($get); // filter to remove falsey values -- is this necessary?
    unset($get['search'], $get['view']);
} else {
    $get = null;
}

?>
<header class="container page-header">
    <h1 class="page-title">Deficiencies</h1>
    <?php
        $btnSelected = 'btn-light border-dark-blue box-shadow-blue';
        $btnNotSelected = 'btn-secondary text-white';
        list($bartBtn, $projBtn) = $view === 'BART'
            ? [$btnSelected, $btnNotSelected]
            : [$btnNotSelected, $btnSelected];
        if ($bartPermit) {
            echo "
                <div class='row'>
                    <div class='col-12 d-flex'>
                        <a href='defs.php' class='btn $projBtn flex-grow item-margin-right text-wrap'>Project deficiencies</a>
                        <a href='defs.php?view=BART' class='btn $bartBtn flex-grow item-margin-right text-wrap'>BART deficiencies</a>
                    </div>
                </div>
            ";
        }
    ?>
</header>
<main class='container main-content'>
<?php
    // render Project Defs table and Search Fields
    if ($view !== 'BART' || !$bartPermit) {
        printInfoBox($role, 'NewDef.php');

        try {
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
            
            $twig->display('defsFilter.html.twig', [
                'resetScript' => 'resetSearch',
                'selectOptions' => getFilterOptions($link, $filterSelects),
                'values' => $get,
                'collapse' => empty($get)
            ]);
        } catch (Twig_Error $e) {
            echo "<p class='pad' style='border: 1px solid var(--grey); background-color: var(--yellow); color: white'>There was a problem displaying search fields</p>";
            error_log("{$e->getFile()}: Line {$e->getTemplateLine()}: {$e->getMessage()}");
        } catch (Exception $e) {
            echo "<p class='pad' style='border: 1px solid var(--grey); background-color: var(--yellow); color: white'>There was a problem displaying search fields</p>";
            error_log($e->getMessage());
        }

        try {
            $fields = [
                "c.defID AS ID",
                "l.locationName AS location",
                "s.severityName AS severity",
                "DATE_FORMAT(c.dateCreated, '%d %b %Y') AS dateCreated",
                "t.statusName AS status",
                "y.systemName AS systemAffected",
                "SUBSTR(c.description, 1, 50) AS description",
                "c.specLoc AS specLoc",
                "c.lastUpdated AS lastUpdated"
            ];
            $joins = [
                "location l" => "c.location = l.locationID",
                "severity s" => "c.severity = s.severityID",
                "status t" => "c.status = t.statusID",
                "system y" => "c.systemAffected = y.systemID"
            ];
            foreach ($joins as $tableName => $on) {
                $link->join($tableName, $on, 'LEFT');
            }

            if ($get) {
                foreach ($get as $param => $val) {
                    if ($param === 'description' || $param === 'defID')
                        $link->where($param, "%{$val}%", 'LIKE');
                    else $link->where($param, $val);
                }
            }

            $link->orderBy('ID', 'ASC');
            $link->where('c.status', 3, '<>');
            $result = $link->get('CDL c', null, $fields);
            printProjectDefsTable($result, $_SESSION['role']);
        } catch (Exception $e) {
            echo "<h1 style='color: #da0;'>{$e->getMessage()}</h1>";
        }
    } elseif ($bartPermit) {
        $statusSql = "SELECT COUNT(CASE WHEN s.statusName='open' THEN 1
            ELSE NULL END) AS statusOpen,
            COUNT(CASE WHEN s.statusName='closed' THEN 1
            ELSE NULL END) AS statusClosed
            FROM BARTDL b JOIN status s
            ON b.status=s.statusID";

        // get status data for data graphic (in script tag below)
        try {
            if (!$statusData = $link->query($statusSql)[0])
                throw new mysqli_sql_exception("There was a problem retrieving status data");
        } catch (Exception $e) {
            echo "<p style='border: 1px solid var(--grey); background-color: var(--yellow); color: white'>{$e->getMessage()}</p>";
        }

        printInfoBox($role, 'newBartDef.php', 1);
        
        try {
            $res_dispCase = '(CASE WHEN resolution_disputed = 1 THEN "yes" ELSE "no" END) AS yesNoName';
            $structCase = '(CASE WHEN structural = 1 THEN "yes" ELSE "no" END) AS yesNoName';
            
            $filterSelects = [
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
                    'fields' => ['resolution_disputed', $res_dispCase], // res_disp and structural use CASES to map 0 + 1 to 'no' + 'yes' b/c they don't line up nicely with our bool table, yesNo
                    'groupBy' => 'resolution_disputed'
                ],
                'structural' => [
                    'table' => 'BARTDL',
                    'fields' => ['structural', $structCase], // res_disp and structural use CASES to map 0 + 1 to 'no' + 'yes' b/c they don't line up nicely with our bool table, yesNo
                    'groupBy' => 'structural'
                ]
            ];
            
            $filterOptions = getFilterOptions($link, $filterSelects);
            
            $twig->display('bartDefsFilter.html.twig', [
                'selectOptions' => $filterOptions,
                'values' => $get,
                'collapse' => empty($get)
            ]);
        } catch (Exception $e) {
            echo "<p class='pad' style='border: 1px solid var(--gray); background-color: var(--yellow); color: var(--gray)'>"
                . "There was a problem retrieving filter parameters: "
                . $e->getMessage() . "</p>";
        }

        try {
            $fields = [
                'ID',
                's.statusName as status',
                'date_created',
                'SUBSTR(descriptive_title_vta, 1, 132) AS descriptive_title_vta',
                'SUBSTR(resolution_vta, 1, 132) AS resolution_vta',
                'n.nextStepName AS next_step'
            ];
            $joins = [
                'status s' => 'b.status = s.statusID',
                'bdNextStep n' => 'b.next_step = n.bdNextStepID'
            ];

            foreach ($joins as $tableName => $on) {
                $link->join($tableName, $on, 'LEFT');
            }
            
            if ($get) {
                foreach ($get as $param => $val) {
                    $link->where($param, $val);
                }
            }
            $link->where('status', '3', '<>');
            $link->orderBy('ID', 'ASC');
            $res = $link->get('BARTDL b', null, $fields);
            printBartDefsTable($res, $bartPermit);
        } catch (Exception $e) {
            echo "<h1 style='color: #b82;'>{$e->getMessage()}</h1>";
        }
    }
    echo "</main>";
    // script tags will eventually go elsewhere once Twig is fully implemented
    echo "
        <script src='https://d3js.org/d3.v5.js'></script>
        <script src='js/pie_chart.js'></script>
        <script>
            function resetSearch(ev) {
                ev.target.form.reset();
                ev.target.form.submit();
            }";
        if ($view === 'BART' && $bartPermit) {
            echo "
                const openCloseChart = new PieChart(
                    window.d3,
                    'dataContainer',
                    { open: '{$statusData['statusOpen']}', closed: '{$statusData['statusClosed']}' },
                    { red: 'var(--red)', green: 'var(--green)' });
                openCloseChart.draw();";
        }
    echo "</script>";

$link->disconnect();

include 'fileend.php';
