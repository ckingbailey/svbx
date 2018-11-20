<?php
require 'vendor/autoload.php';
require 'session.php';

use SVBX\Deficiency;

if ($_SESSION['role'] <= 10) {
    header("This is not for you", true, 403);
    exit;
}

$get = !empty($_GET) ? filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS) : null;

// project def params
$projectFields = [
    'defID as ID',
    'yesNoName as safetyCert',
    'sys.systemName as systemAffected',
    'locationName as location',
    'specLoc',
    'statusName as status',
    'closureRequested',
    'severityName as severity',
    'dueDate',
    'grp.systemName as groupToResolve',
    'req.requiredBy',
    'contractName as contract',
    'identifiedBy',
    'defTypeName as defType',
    'description',
    'spec',
    'actionOwner',
    'oldID',
    'comments as moreInfo',
    'eviTypeName as evidenceType',
    'repoName as repo',
    'evidenceLink',
    'closureComments',
    'c.dateCreated',
    "CONCAT(cre.firstname, ' ', cre.lastName) as created_by",
    'c.lastUpdated',
    "CONCAT(upd.firstname, ' ', upd.lastName) as updated_by",
    'dateClosed',
    "CONCAT(close.firstname, ' ', close.lastName) as closureRequestedBy"
];

$projectJoins = [
    'yesNo' => 'c.safetyCert = yesNo.yesNoID',
    'system sys' => 'c.systemAffected = sys.systemID',
    'location' => 'c.location = location.locationID',
    'status' => 'c.status = status.statusID',
    'severity' => 'c.severity = severity.severityID',
    'system grp' => 'c.groupToResolve = grp.systemID',
    'requiredBy req' => 'c.requiredBy = req.reqByID',
    'contract' => 'c.contractID = contract.contractID',
    'defType' => 'c.defType = defType.defTypeID',
    'evidenceType' => 'c.evidenceType = evidenceType.eviTypeID',
    'repo' => 'c.repo = repo.repoID',
    'users_enc cre' => 'c.created_by = cre.username',
    'users_enc upd' => 'c.updated_by = upd.username',
    'users_enc close' => 'c.closureRequestedBy = close.username'
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

$projectTableName = 'CDL';
$projectTableAlias = 'c';
$projectIdField = 'defID';
$projectCommentsTable = 'cdlComments';
$projectComments = 'cdlCommText';
$projectAttachmentsTable = 'CDL_pics';
$projectPathField = 'pathToFile';
$projectTemplate = 'defForm.html.twig';

// bart def params
$bartFields = [
    'ID',
    'creator.partyName as creator',
    'nextStepName as next_step',
    'bic.partyName as bic',
    'statusName as status',
    'descriptive_title_vta',
    'root_prob_vta',
    'resolution_vta',
    'priority_vta',
    'agreeDisagreeName as agree_vta',
    'yesNoName as safety_cert_vta',
    'resolution_disputed',
    'structural',
    'id_bart',
    'description_bart',
    'cat1_bart',
    'cat2_bart',
    'cat3_bart',
    'level_bart',
    'dateOpen_bart',
    'dateClose_bart',
    'date_created',
    "CONCAT(cre.firstname, ' ', cre.lastname)",
    'Form_Modified',
    "CONCAT(upd.firstname, ' ', upd.lastname)"
];

$bartJoins = [
    'yesNo' => 'b.safety_cert_vta = yesNo.yesNoID',
    'users_enc upd' => 'b.updated_by = upd.userID',
    'bdNextStep' => 'b.next_step = bdNextStep.bdNextStepID',
    'bdParties creator' => 'b.creator = creator.partyID',
    'bdParties bic' => 'b.bic = bic.partyID',
    'status' => 'b.status = status.statusID',
    'agreeDisagree' => 'b.agree_vta = agreeDisagree.agreeDisagreeID',
    'users_enc cre' => 'b.created_by = cre.userID'
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

$bartTableName = 'bartDL';
$bartTableAlias = 'b';
$bartIdField = 'ID';
$bartCommentTable = 'bartdlComments';
$bartComments = 'bdCommText';
$bartAttachmentsTable = 'bartdlAttachments';
$bartPathField = 'bdaFilepath';
$bartTemplate = 'bartForm.html.twig';

list(
    $id,
    $idField,
    $tableName,
    $tableAlias,
    $fields,
    $joins,
    $commentTable,
    $commentTextField,
    $attachmentsTable,
    $pathField,
    $templatePath
) = (!empty($get['defID'])
    ? [
        $get['defID'],
        $projectIdField,
        $projectTableName,
        $projectTableAlias,
        $projectFields,
        $projectJoins,
        $projectCommentsTable,
        $projectComments,
        $projectAttachmentsTable,
        $projectPathField,
        $projectTemplate
        ]
        : (!empty($get['bartDefID'])
        ? [
            $get['bartDefID'],
            $bartIdField,
            $bartTableName,
            $bartTableAlias,
            $bartFields,
            $bartJoins,
            $bartCommentTable,
            $bartComments,
            $bartAttachmentsTable,
            $bartPathField,
            $bartTemplate
          ]
        : array_fill(0, 11, null)));

    $context = [
        'session' => $_SESSION,
        'pageHeading' => "Deficiency No. $id",
    ];

    if (!empty($_SESSION['errorMsg']))
        unset($_SESSION['errorMsg']);

try {
    $context['options'] = Deficiency::getLookUpOptions();

    $link = new MySqliDB(DB_CREDENTIALS);

    // TODO: show special contractor options
    // $defStatus = $elements['status']['value'];
    // // special options for Contractor level when Def is Open
    // if ($role === 15 && $defStatus === 1) {
    //     $elements['status']['query'] = [ 1 => 'Open', 4 => 'Request closure' ];    
    // }

    $link = new MySqliDB(DB_CREDENTIALS);

    foreach ($joins as $joinTable => $onCondition) {
        $link->join($joinTable, $onCondition, 'LEFT');
    }    

    $link->where('statusName', 'deleted', '<>');
    $link->where($idField, $id);

    $context['data'] = $link->getOne("$tableName $tableAlias", $fields);

    // query for comments associated with this Def
    $link->join('users_enc u', "$commentTable.userID = u.userID");
    $link->orderBy("$commentTable.date_created", 'DESC');
    $link->where(($idField === 'defID' ?: 'bartdlID'), $id);
    $context['data']['comments'] = $link->get($commentTable, null, [ "$commentTextField as commentText", 'date_created', "CONCAT(firstname, ' ', lastname) as userFullName" ]);

    // query for photos linked to this Def
    // keep BART | Project, photos | attachments separate for now
    // to leave room for giving photos or attachments to either of those data types in the future
    if ($tableName === 'CDL') {
        $link->where($idField, $id);
        $photos = $link->get($attachmentsTable, null, "$pathField as filepath");
        $context['data']['photos'] = array_chunk($photos, 3);
    }
    if ($tableName === 'BARTLDL') {
        $link->where('bartdlID', $id);
        $context['data']['attachments'] = $link->get($attachmentsTable, null, "$pathField as filepath");
    }

    // instantiate Twig
    $loader = new Twig_Loader_Filesystem('./templates');
    $twig = new Twig_Environment($loader, [ 'debug' => $_ENV['PHP_ENV'] === 'dev' ]);
    $twig->addExtension(new Twig_Extension_Debug());
    // $template = $twig->load();

    $html_sanitize_decode = new Twig_Filter('html_sanitize_decode', function($str) {
        $decoded = html_entity_decode($str, ENT_QUOTES);
        return filter_var($decoded, FILTER_SANITIZE_SPECIAL_CHARS);
    });    
    $filter_stripslashes = new Twig_Filter('unescape', function($str) {
        return stripcslashes($str);
    });    
    $twig->addFilter($html_sanitize_decode);
    $twig->addFilter($filter_stripslashes);

    $twig->display($templatePath, $context);
} catch (Twig_Error $e) {
    echo "Unable to render template";
    error_log($e);
} catch (Exception $e) {
    echo "Unable to retrieve record";
    error_log($e);
} finally {
    if (!empty($link) && is_a($link, 'MySqliDB')) $link->disconnect();
    exit;
}
